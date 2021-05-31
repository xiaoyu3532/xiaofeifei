<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/2 0002
 * Time: 9:42
 */

namespace app\xcx\controller\v1;


use app\lib\exception\CourseMissException;
use app\lib\exception\OrderMissExceptionFind;
use app\lib\exception\OrderMissExceptionStock;
use app\validate\CatCourseMustBePostiveInt;
use app\common\model\Crud;
use app\validate\CourseIDSMustBePostiveInt;
use think\Db;


class Order extends BaseController
{
    //添加订单
    //传值 cat_id 购物车ID 为数组
    //传值 cou_id 课程ID 直接购买使用
    //传值 status 课程类型
    //传值 user_id 用户ID
    //传值 Pass_order_status 1直接购买，2从购物车购买
    //传值 student_id 用户学生ID
    //返回值 1000开头是正确的 2000开头是有误 3000给后台人员看
    //1008 2013

    public static function setAddOrder()
    {
        $data = input();
        (new CatCourseMustBePostiveInt())->goCheck();
        //判断用户报名体验课是否超过6节
        $experience_num = self::isExperienceCourseNum($data);
        if ($experience_num != 1000) {
            return $experience_num;
        }
        //验证客户是否购买了此课程
        $payExperienceCourse = self::payExperienceCourse($data);
        if ($payExperienceCourse != 1000) {
            return $payExperienceCourse;
        }
        //判断用户是从购物加入订单，还是直接购买加入订单 Pass_order_status 1直接购买，2从购物车购买
        if ($data['Pass_order_status'] == 1) {
            //直接下订单
            $experience_course = self::directOrder($data);
            if ($experience_course) {  //有值表示课程是
                return $experience_course;
            }
        } elseif ($data['Pass_order_status'] == 2) {
            //因为是从购物车下订单，传值是cou_id，先获取课程信息
            $where = [
                'id' => ['in', $data['cat_id']],
                'user_id' => $data['user_id'],
                'is_del' => 1,
            ];
            $table = 'cat_course';
            //获取购物车课程信息
            $cat_data = Crud::getData($table, $type = 2, $where, $field = 'cou_id,status,num');
            if (!$cat_data) {
                throw new CourseMissException();
            } else {
                //购物车下订单
                $catOrder = self::catOrder($cat_data, $data);
                if ($catOrder) {
                    return $catOrder;
                }
            }
        }
    }

    //直接下订单(判断是否是体验课程于其他课程)
    public static function directOrder($data)
    {
        if ($data['status'] == 2) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5社区课程
            //添加体验课程
            $res = self::directExperienceCourse($data);
            return $res;
        } else {
            //添加其他课程
            $res = self::directCourse($data);
            return $res;
        }


    }

    //直接购买体验课下订单  修改完成
    public static function directExperienceCourse($data)
    {
        $price_sum = 0;
        $order_num = time() . rand(10, 99);
        $create_time = time();
        //计算小订单
        $table = 'experience_course';
        //根据机构ID查询报有的课程
        $where1 = [
            'ec.id' => $data['cou_id'],
//            'ec.type' => 1,
            'ec.is_del' => 1,
        ];
        $join = [
            ['yx_curriculum c', 'ec.curriculum_id = c.id', 'left'],
        ];
        $alias = 'ec';
        $field = 'ec.id,ec.present_price,c.name,ec.mid';
        //验证体验课程
        $experience_couser = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        Db::startTrans();
        try {
            //添加小订单
            //小订单判断体验课程是否付费
            if ($experience_couser['present_price'] > 0) {
                $status = 1;
                $paytype = 4;
                $price_status = 2; //1为不需要支付，2需要支付
            } else {
                $status = 8;
                $paytype = 3;
                $price_status = 1; //1为不需要支付，2需要支付
                //删除购物车
                $where2 = [
                    'user_id' => $data['user_id'],
                    'cou_id' => $experience_couser['id'],
                    'status' => 2,
                    'is_del' => 1,
                ];
                $cat_data = Crud::getCount('cat_course', $where2);
                if ($cat_data != 0) {
                    $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
                    if (!$del_cat) {
                        Db::rollback();
                        return jsonResponse('3000', '删除购物车失败', 2001);
                    }
                }
            }
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'mid' => $experience_couser['mid'], //机构id
                'cid' => $experience_couser['id'], //课程id
                'name' => $experience_couser['name'], //课程名称
                'status' => $status, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'paytype' => $paytype, //支付方式 1支付宝，2微信，3免费,4未知
                'price' => $experience_couser['present_price'], //课程价格
                'uid' => $data['user_id'], //用户ID
                'student_id' => $data['student_id'], //学生信息ID
                'cou_status' => 2, //1普通课程，2体验课程，3活动课程，4秒杀课程
            ];
            $price_sum = $price_sum + $experience_couser['present_price'];
            $table = request()->controller();
            //添加小订单
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '体验课添加小订单失败', 2001);
            }
            $data1 = [
                'order_num' => $order_num,
                'status' => $status,
                'price' => $price_sum,
                'create_time' => $create_time,
                'uid' => $data['user_id'],
                'paytype' => $paytype,
                'student_id' => $data['student_id'],//学生信息ID
            ];
            //添加大订单
            $table = 'order_num';
            $order_num_data = Crud::setAdd($table, $data1);

            if (!$order_num_data) {
                Db::rollback();
                return jsonResponse('3000', '体验课添加大订单失败', 2002);
            }
            //机构加名额(报名人数)
            if ($price_sum <= 0) {
                $plus_course = Db::name('member')->where(['uid' => $experience_couser['mid']])->setInc('enroll_num');
                if (!$plus_course) {
                    Db::rollback();
                    return jsonResponse('3000', '体验课机构加名额(报名人数)失败', 2003);
                }
                //减课程库存
                $table = 'experience_course';
                $delStock = Crud::setDecs($table, ['id' => $data['cou_id']], 'surplus_num');
                if (!$delStock) {
                    Db::rollback();
                    return jsonResponse('3000', '减库存失败', 2003);
                }
                //加课程销量 enroll_num
                $delStock = Crud::setIncs('experience_course', ['id' => $data['cou_id']], 'enroll_num');
                if (!$delStock) {
                    Db::rollback();
                    return jsonResponse('3000', '课程增加销量操失败', 2003);
                } else {
                    Db::commit();
                    $success_array = [
                        'order_num' => $order_num,
                        'price_status' => $price_status //1为不需要支付，2需要支付
                    ];
                    return jsonResponse('1000', '体验课下单成11功', $success_array);
                }
            } else {
                Db::commit();
                $success_array = [
                    'order_num' => $order_num,
                    'price_status' => $price_status //1为不需要支付，2需要支付
                ];
                return jsonResponse('1000', '体验课下单成功', $success_array);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }

    }

    //直接购买其他课程下订单 修改成功
    public static function directCourse($data)
    {

        //查看课程类型
        if ($data['status'] == 1) { //1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课程
            //yx_course
            $table = 'course';
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $data['cou_id']
            ];
            $join = [
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'c';
            $field = 'c.id,c.present_price,cu.name,c.surplus_num,c.mid,c.start_time,c.c_num,c.teacher_id,c.classroom_id';
            //查询出机构所有的课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '普通课程没有库存', 1003);
            }
        } elseif ($data['status'] == 3) {
            $table = 'community_course';
            $where = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'cc';
            $field = 'cc.present_price,cc.surplus_num,cc.by_time,cu.name,cc.mid,cc.id,cc.start_time,cc.c_num,cc.teacher_id,cc.classroom_id,cc.community_id';
            //查询活动课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断截至时间
//            if ($Course_data['by_time'] < time()) {
//                return jsonResponse('2000', '社区活动课程截至时间已过', 1004);
//            }
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                throw new OrderMissExceptionStock();
//                return jsonResponse('2000', '社区活动课程无库存', 1005);
            }
            $community_id = $Course_data['community_id'];
        } elseif ($data['status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
                'st.is_del' => 1,
                'st.type' => 1,
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
                ['yx_seckill_theme st', 'sc.seckill_theme_id = st.id', 'left'],
            ];
            $alias = 'sc';
            $field = 'sc.present_price,sc.surplus_num,st.start_time start_timest,st.end_time end_timest,cu.name,sc.mid,sc.id,sc.start_time,sc.c_num,sc.teacher_id,sc.classroom_id';
            //查询秒杀课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                throw new OrderMissExceptionStock();
//                return jsonResponse('2000', '秒杀课程无库存', 1006);
            }
            //判断秒杀时间 小于开始时间
            if ($Course_data['start_timest'] > time()) {
                return jsonResponse('2000', '秒杀课程开始时间没到', 1007);
            }
            //大于结束时间
            if ($Course_data['end_timest'] < time()) {
                return jsonResponse('2000', '秒杀课程已过结束时间', 1008);
            }
        } elseif ($data['status'] == 5) {
            $table = 'synthetical_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'sc';
            $field = 'sc.present_price,sc.surplus_num,cu.name,sc.mid,sc.start_time,sc.c_num,sc.teacher_id,sc.classroom_id,sc.syntheticalcn_id';
            //查询综合体课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                throw new OrderMissExceptionStock();
//                return jsonResponse('2000', '综合体课程无库存', 1005);
            }
            $Course_data['cou_status'] = $data['status'];
            $Course_data['status'] = 1;
            unset($Course_data['surplus_num']);
            $syntheticalcn_id = $Course_data['syntheticalcn_id'];
        }
        $order_num = time() . rand(10, 99);
        $create_time = time();
        if ($Course_data['present_price'] <= 0) {
            $status = 2;
            $price_status = 1; //1为不需要支付，2需要支付
        } else {
            $status = 1;
            $price_status = 2; //1为不需要支付，2需要支付
        }

        $data1 = [
            'order_num' => $order_num,
            'status' => $status, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'price' => $Course_data['present_price'],//总价
            'create_time' => $create_time,
            'uid' => $data['user_id'],
            'student_id' => $data['student_id'], //学生信息ID
        ];
        if (!isset($community_id) && empty($community_id)) {
            $community_id = 0;
        }
        if (!isset($syntheticalcn_id) && empty($syntheticalcn_id)) {
            $syntheticalcn_id = 0;
        }
        //获取小订单信息
        $adddata = [
            'order_id' => time() . rand(10, 99), //订单号
            'order_num' => $order_num, //大订单号
            'community_id' => $community_id, //社区id
            'syntheticalcn_id' => $syntheticalcn_id, //综合体id
            'mid' => $Course_data['mid'], //机构id
            'cid' => $data['cou_id'], //课程id
            'name' => $Course_data['name'], //课程名称
            'status' => $status, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'price' => $Course_data['present_price'], //课程价格
            'uid' => $data['user_id'], //用户ID
            'student_id' => $data['student_id'], //学生信息ID
            'cou_status' => $data['status'], //1普通课程，2体验课程，3活动课程，4秒杀课程
            'start_time' => $Course_data['start_time'], //开课时间
            'c_num' => $Course_data['c_num'], //课时
            'teacher_id' => $Course_data['teacher_id'], //老师ID
            'classroom_id' => $Course_data['classroom_id'], //教室id
        ];
        Db::startTrans();
        try {
            //添加小订单
            $table = request()->controller();
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '直接下单添加小订单失败', 2009);
            }
            //添加大订单
            $table = 'order_num';
            $order_num_data = Crud::setAdd($table, $data1);
            if (!$order_num_data) {
                Db::rollback();
                return jsonResponse('3000', '直接下单添加大订单失败', 2010);
            } else {
                if ($price_status == 1) {
                    $data['cou_status'] = $data['status'];
                    $data['cid'] = $data['cou_id'];
                    //增销量，减库存
                    $setIncsAndDelnum = self::setIncsAndDelnum($data);
                    if (!$setIncsAndDelnum) {
                        Db::rollback();
                    }
                }
//
                Db::commit();
                $success_array = [
                    'order_num' => $order_num,
                    'price_status' => $price_status
                ];
                return jsonResponse('1000', '课程下单成功', $success_array);
            }
//            //减库存
//            $setDelStock = self::setOtherStock($data);
//            if(!$setDelStock){
//                Db::rollback();
//            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    //购物车下订单
    public static function catOrder($cat_data, $data)
    {
        Db::startTrans();
        try {
            //计算大订单价格
            $price_sum = 0;
            $order_num = time() . rand(10, 99);
            foreach ($cat_data as $k => $v) {
                if ($v['status'] == 2) {//1普通课程，2体验课程，3活动课程，4秒杀课程
                    //体验课数据组合完成
                    $arrayexp[] = self::getExperienceCourse($v['cou_id']);
                } else {
                    //其他课数据组合成功
                    $Other = self::manyCourse($v);
                    if (!is_array($Other)) {
                        return $Other;
                    } else {
                        $arrayOther[] = self::manyCourse($v);
                    }
                }
            }

            //购物车体验课程下单操作
            if (isset($arrayexp) && !empty($arrayexp)) {
                $setCatExperienceCourse = self::setCatExperienceCourse($arrayexp, $order_num, $data);
                if ($setCatExperienceCourse != 1000) {
                    return $setCatExperienceCourse;
                } else {
                    foreach ($arrayexp as $kp => $vp) {
                        $price_sum = (float)$vp['present_price'] + $price_sum;
                    }
                    if ($price_sum > 0) {
                        $paytype = 4; //支付方式 1支付宝，2微信，3免费,4未知
                        $status = 1;//大订单支付状态  默认8为免费（体验课）
                    } else {
                        $paytype = 3; //支付方式 1支付宝，2微信，3免费,4未知
                        $status = 8;//大订单支付状态  默认8为免费（体验课）
                    }
                }
            }
            //购物车其他课程下单操作
            if (isset($arrayOther) && !empty($arrayOther)) {
                foreach ($arrayOther as $kp => $vp) {
//                    $price_sum += (int)$vp['present_price'] + $price_sum;
                    $price_sum += (float)$vp['present_price'] + $price_sum;

                }
                //购物车其他课程下单操作
                $setCatOtherCourse = self::setCatOtherCourse($arrayOther, $order_num, $data);
                if ($setCatOtherCourse != 1000) {
                    return $setCatOtherCourse;
                }
                if ($price_sum > 0) {
                    $paytype = 4; //支付方式 1支付宝，2微信，3免费,4未知
                    $status = 1;//大订单支付状态  默认8为免费（体验课）
                } else {
                    $paytype = 3; //支付方式 1支付宝，2微信，3免费,4未知
                    $status = 2;//大订单支付状态  默认8为免费（体验课）
                }
            }

            //大订单
            $create_time = time();
            $data1 = [
                'order_num' => $order_num,
                'status' => $status, //要修改
                'price' => $price_sum,
                'create_time' => $create_time,
                'uid' => $data['user_id'],
                'paytype' => $paytype,
                'student_id' => $data['student_id'], //学生信息ID
//                'sname' => $data['sname'],
//                'phone' => $data['phone'],
//                'sex' => $data['sex'],
//                'age' => $data['age'],
            ];
            //添加大订单
            $table = 'order_num';
            $order_num_data = Crud::setAdd($table, $data1);

            if (!$order_num_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下大订单失败', 2013);
            } else {
                Db::commit();
                if ($price_sum >0) {
                    $price_status = 2;
                } else {
                    $price_status = 1;
                }
                $success_array = [
                    'order_num' => $order_num,
                    'price_status' => $price_status //1为不需要支付，2需要支付
                ];
                return jsonResponse('1000', '下单成功', $success_array);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    //购物车下单组合体验课数据
    public static function getExperienceCourse($cou_id)
    {
        //计算小订单
        $table = 'experience_course';
        $where1 = [
            'ec.id' => $cou_id,
            'ec.type' => 1,
            'ec.is_del' => 1,
        ];
        $join = [
            ['yx_curriculum c', 'ec.curriculum_id = c.id', 'left'],
        ];
        $alias = 'ec';
        $field = 'ec.id,ec.present_price,c.name,ec.mid';
        //查询出机构所有的体验课
        $experience_couser = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        if ($experience_couser) {
            $experience_couser['cou_status'] = 2;
            if ($experience_couser['present_price'] > 0) {
                $status = 1;
            } else {
                $status = 8;
            }
            $experience_couser['status'] = $status;
        }
        return $experience_couser;
    }

    //购物车体验课程下单操作
    public static function setCatExperienceCourse($arrayexp, $order_num, $data)
    {
        //三维数组变二维数组
        Db::startTrans();
        foreach ($arrayexp as $kk => $vv) {
            //获取小订单信息
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'mid' => $vv['mid'], //机构id
                'cid' => $vv['id'], //课程id
                'name' => $vv['name'], //课程名称
                'status' => $vv['status'], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'price' => $vv['present_price'], //课程价格
                'uid' => $data['user_id'], //用户ID
                'student_id' => $data['student_id'], //学生信息ID
                'cou_status' => 2, //1普通课程，2体验课程，3活动课程，4秒杀课程
                'paytype' => 3, //3免费
            ];
            $table = request()->controller();
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下体验课订单失败', 2011);
            }
            if ($vv['present_price'] == 0) {
                //删除购物车
                $where2 = [
                    'user_id' => $data['user_id'],
                    'cou_id' => $vv['id'],
                    'status' => 2,
                    'is_del' => 1
                ];
                $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
                if (!$del_cat) {
                    Db::rollback();
                    return jsonResponse('3000', '删除购物车失败', 2001);
                }
                //加销量减库存
                $vv['cid'] = $vv['id'];
                $setIncsAndDelnum = self::setIncsAndDelnum($vv);
                if ($setIncsAndDelnum != 1000) {
                    Db::rollback();
                    return $setIncsAndDelnum;
                }
            }
        }
        if ($order_data) {
            Db::commit();
            return 1000;
        }

    }

    //购物车下单组合其他课程 修改完成
    public static function manyCourse($vat_v)
    {
        $where['cu.is_del'] = 1;
        $where['cu.type'] = 1;
        if ($vat_v['status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体
            $table = 'course';
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $vat_v['cou_id']
            ];
            $join = [
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'c';
            $field = 'c.id,cu.name,c.present_price,c.surplus_num,c.mid,c.start_time,c.c_num,c.teacher_id,c.classroom_id';
            //查询出机构所有的体验课
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '普通课程没有库存', 1003);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            //验证是否是0元免费课
            $Course_data = self::isPrice($Course_data);
            unset($Course_data['surplus_num']);
        } elseif ($vat_v['status'] == 3) {
            $table = 'community_course';
            $where = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $vat_v['cou_id'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'cc';
            $field = 'cc.present_price,cc.surplus_num,cc.by_time,cu.name,cc.mid,cc.id,cc.start_time,cc.c_num,cc.teacher_id,cc.classroom_id,cc.community_id';
            //查询活动课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);

            //判断截至时间
//            if ($Course_data['by_time'] < time()) {
//                return jsonResponse('2000', '社区活动课程截至时间已过', 1004);
//
//            }
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '社区活动课程无库存', 1005);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            //验证是否是0元免费课
            $Course_data = self::isPrice($Course_data);
            $community_id = $Course_data['community_id'];
            unset($Course_data['surplus_num']);
            unset($Course_data['by_time']);
        } elseif ($vat_v['status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $vat_v['cou_id'],
                'st.is_del' => 1,
                'st.type' => 1,
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
                ['yx_seckill_theme st', 'sc.seckill_theme_id = st.id', 'left'],
            ];
            $alias = 'sc';
            $field = 'sc.present_price,sc.surplus_num,st.start_time start_timest,st.end_time end_timest,cu.name,sc.mid,sc.id,sc.start_time,sc.c_num,sc.teacher_id,sc.classroom_id';
            //查询秒杀课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['surplus_num'] <= 0) {
                return jsonResponse('2000', '秒杀课程无库存', 1006);
            }
            //判断秒杀时间 小于开始时间
            if ($Course_data['start_timest'] > time()) {
                return jsonResponse('2000', '秒杀开始时间未到', 1007);
            }
            //大于结束时间
            if ($Course_data['end_timest'] < time()) {
                return jsonResponse('2000', '秒杀活动已结束', 1008);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            //验证是否是0元免费课
            $Course_data = self::isPrice($Course_data);
            unset($Course_data['surplus_num']);
//            unset($Course_data['start_time']);
//            unset($Course_data['end_time']);
        } elseif ($vat_v['status'] == 5) {
            $table = 'synthetical_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $vat_v['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
            ];
            $alias = 'sc';

            $field = 'sc.present_price,sc.surplus_num,cu.name,sc.mid,sc.id,sc.start_time,sc.c_num,sc.teacher_id,sc.classroom_id,sc.syntheticalcn_id';
            //查询综合体课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '综合体课程无库存', 1005);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            //验证是否是0元免费课
            $Course_data = self::isPrice($Course_data);
            $syntheticalcn_id = $Course_data['syntheticalcn_id'];
            unset($Course_data['surplus_num']);
        }
        return $Course_data;
    }

    //购物车其他课程下单操作 修改完成
    public static function setCatOtherCourse($arrayOther, $order_num, $data)
    {

        Db::startTrans();
        foreach ($arrayOther as $kk => $vv) {
            //社区id
            if (isset($vv['community_id']) && !empty($vv['community_id'])) {

                $community_id = $vv['community_id'];
            } else {
                $community_id = 0;
            }
//            //综合体id
            if (isset($vv['syntheticalcn_id']) && !empty($vv['syntheticalcn_id'])) {
                $syntheticalcn_id = $vv['syntheticalcn_id'];
            } else {
                $syntheticalcn_id = 0;
            }
            //获取小订单信息
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'community_id' => $community_id, //社区id
                'syntheticalcn_id' => $syntheticalcn_id, //综合体id
                'mid' => $vv['mid'], //机构id
                'cid' => $vv['id'], //课程id
                'name' => $vv['name'], //课程名称
                'status' => $vv['status'], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'price' => $vv['present_price'], //课程价格
                'student_id' => $data['student_id'], //学生信息ID
                'uid' => $data['user_id'], //用户ID
                'cou_status' => $vv['cou_status'], //1普通课程，2体验课程，3活动课程，4秒杀课程
//                'start_time' => $vv['start_time'], //开课时间
                'c_num' => $vv['c_num'], //课时
                'teacher_id' => $vv['teacher_id'], //老师ID
                'classroom_id' => $vv['classroom_id'], //教室ID
            ];

//            dump($adddata);
            //添加订单号
            $table = request()->controller();
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下其他课程订单失败', 2013);
            }
            if ($vv['present_price'] == 0) {
                $where2 = [
                    'user_id' => $data['user_id'],
                    'cou_id' => $vv['id'],
                    'status' => $vv['cou_status'],
                    'is_del' => 1
                ];
                $cat_data = Crud::getData('cat_course', 1, $where2, 'id');
                //购物车
                if ($cat_data) {
                    $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
                    if (!$del_cat) {
                        Db::rollback();
                        return jsonResponse('3000', '删除购物车失败', 2001);
                    }
                }
                //加销量减库存
                $vv['cid'] = $vv['id'];
                $setIncsAndDelnum = self::setIncsAndDelnum($vv);
                if ($setIncsAndDelnum != 1000) {
                    Db::rollback();
                    return $setIncsAndDelnum;
                }
            }
        }
        if ($order_data) {
            Db::commit();
            return 1000;
        }
    }

    //获取机构是否有赠送名额于是否是会员(体验课程减名额)
    public static function setQuota($member_vv)
    {
        Db::startTrans();
        //判断是否有赠送名额
        if ($member_vv['give_type'] == 1) { //1有赠送名额
            //查询赠送名额
            $give_num = Db::name('give_num')->where(['mid' => $member_vv['uid'], 'is_del' => 1])->field('num')->find();
            if (!$give_num) {
                Db::rollback();
                return jsonResponse('3000', '查询赠送名额有误', 2004);
            }
            if ($give_num['num'] > 0) {
                $nums = Db::name('give_num')->where(['mid' => $member_vv['uid'], 'is_del' => 1])->setDec('num', 1);
                if (!$nums) {
                    Db::rollback();
                    return jsonResponse('3000', '减名额操作失败', 2005);
                }
//                else{
//                    $member_update = Db::name('member')->where(['uid' => $member_vv['uid']])->update(['give_type' => 2]);
//                    if (!$member_update) {
//                        Db::rollback();
//                        return 2006;//修改赠送名额有误
//                    }else{
//                        Db::rollback();
//                        return 1001; //机构赠送名额为空
//                    }
//                }

                //查询赠送名额是否为0
                $numt = Db::name('give_num')->where(['mid' => $member_vv['uid'], 'is_del' => 1])->field('num')->find();
                if (!$numt) {
                    Db::rollback();
                    return jsonResponse('3000', '查询赠送名额有误', 2004);
                }
                if ($numt['num'] <= 0) {//如果用户赠送名额为0时，修改机构赠送状态
                    $member_update = Db::name('member')->where(['uid' => $member_vv['uid']])->update(['give_type' => 2]);
                    if (!$member_update) {
                        Db::rollback();
                        return jsonResponse('3000', '修改赠送名额有误', 2006);
                    }
                }
                if ($nums) {
                    Db::commit();
                    return 1000;//减机构赠送名额成功
                }
            }
        } elseif ($member_vv['give_type'] == 2) {
            //查询机构是否是会员 1是会员，2不是会员
            //计算名额单价
            $user = Db::name('user_price')->where(['is_del' => 1])->field('price')->find();
            //计算优惠
            $discount = Db::name('discount')->where(['is_del' => 1])->field('discount')->find();
            if ($member_vv['ismember'] == 1) {
                $Dec_price = $user['price'] * $discount['discount'];
            } elseif ($member_vv['ismember'] == 2) {
                $Dec_price = $user['price'];
            }
            //判读余额是否大于名额金额
            //查询会员余额是否满足
            $price = $member_vv['balance'] - $Dec_price;
            if ($price < 0) {
                Db::rollback();
                return jsonResponse('2000', '机构余额不足', 1002);
//                return 1002;//机构余额不足
            }
            //减会员余额
            $member_data = Db::name('member')->where(['uid' => $member_vv['uid']])->setDec('balance', $Dec_price);
            if ($member_data) {
                Db::commit();
                return 1000;//减机构余额成功
            } else {
                Db::rollback();
                return jsonResponse('3000', '减机构余额失败', 2007);
            }
        }
    }

    //验证客户报体验课是否超过6家机构
    public static function isExperienceCourseNum($data)
    {
        //获取用户当前报体验课程
        $order_where = [
            'uid' => $data['user_id'],
            'status' => 8, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'cou_status' => 2, //1普通课程，2体验课程，3活动课程，4秒杀课程
        ];
        $table = request()->controller();
        //当前用户报体验课数量
        $experience_coures_num = Crud::getGroupCount($table, $order_where, 'mid');
        if ($experience_coures_num >= 6) {
            return jsonResponse('2000', '你报体验课程已满');
        }
//        if ($data['Pass_order_status'] == 1) {
        //直接下订单
//          return jsonResponse('2000','你报体验课程已满');


//        } elseif ($data['Pass_order_status'] == 2) {
        if ($data['Pass_order_status'] == 2) {
            //因为是从购物车下订单，传值是cou_id，先获取课程信息
            $where = [
                'id' => ['in', $data['cat_id']],
                'user_id' => $data['user_id'],
                'is_del' => 1,
            ];
            $table = 'cat_course';
            //获取购物车课程信息
            $cat_data = Crud::getData($table, $type = 2, $where, $field = 'cou_id,status,num');
            if (!$cat_data) {
                throw new CourseMissException();
            } else {
                //获取购物车体验课数量
                $count_num = 0;
                foreach ($cat_data as $k => $v) {
                    if ($v['status'] == 2) {
                        $count_num = $count_num + 1;
                    }
                }
                $the_num = $experience_coures_num + $count_num;
                if ($the_num >= 6) {
                    return jsonResponse('2000', '你报体验课程已满');
                } else {
                    return 1000;
                }
            }
        } else {
            return 1000;
        }
    }

    //获取订单列表
    public static function getOrder($user_id, $status, $page = '1')
    {
        $where = [
            'o.uid' => $user_id,
//            'o.status'=>['in',[2,5,8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'status' => ['in', $status], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'o.is_del' => 1,
            's.is_del' => 1
        ];
        //先把进行中的订单查询出来
        $table = request()->controller();
        $join = [
            ['yx_student s', 'o.student_id = s.id', 'left'],
        ];
        $alias = 'o';
        $field1 = 'o.mid,o.cid cou_id,o.cou_status,o.status,o.price,o.already_num,s.name sname,o.create_time';
        $order_info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field1, $page);
        if ($order_info) {
            foreach ($order_info as $k => $v) {
                if ($v['cou_status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
                    $table1 = 'course';
                    $where1 = [
                        'c.id' => $v['cou_id'],
                        'c.is_del' => 1,
                        'c.type' => 1,
                        'm.is_del' => 1,
                        'm.status' => 1,
                        'cu.is_del' => 1,
                        'cu.type' => 1,
                    ];
                    $join = [
                        ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                        ['yx_member m', 'c.mid = m.uid', 'left'],
                    ];
                    $alias = 'c';
                    $field1 = 'c.img,m.cname,m.remarks,cu.name,c.c_num,c.original_price';
                    $course = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field1, 1, 1000);
                    if ($course) {
                        $order_info[$k]['img'] = $course['img'];
                        $order_info[$k]['cname'] = $course['cname'];
                        $order_info[$k]['remarks'] = $course['remarks'];
                        $order_info[$k]['name'] = $course['name'];
                        $order_info[$k]['c_num'] = $course['c_num'];
                        $order_info[$k]['original_price'] = $course['original_price'];
                    }

                } elseif ($v['cou_status'] == 2) {
                    $table1 = 'experience_course';
                    $where1 = [
                        'ex.id' => $v['cou_id'],
                        'ex.is_del' => 1,
                        'ex.type' => 1,
                        'm.is_del' => 1,
                        'm.status' => 1,
                        'cu.is_del' => 1,
                        'cu.type' => 1,
                    ];
                    $join = [
                        ['yx_curriculum cu', 'ex.curriculum_id = cu.id', 'left'],
                        ['yx_member m', 'ex.mid = m.uid', 'left'],
                    ];
                    $alias = 'ex';
                    $field1 = 'ex.img,m.cname,m.remarks,cu.name,ex.c_num,ex.original_price';
                    $course = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field1, 1, 1000);
                    if ($course) {
                        $order_info[$k]['img'] = $course['img'];
                        $order_info[$k]['cname'] = $course['cname'];
                        $order_info[$k]['remarks'] = $course['remarks'];
                        $order_info[$k]['name'] = $course['name'];
                        $order_info[$k]['c_num'] = $course['c_num'];
                        $order_info[$k]['original_price'] = $course['original_price'];
                    }

                } elseif ($v['cou_status'] == 3) {
                    $table1 = 'community_course';
                    $where1 = [
                        'cc.id' => $v['cou_id'],
                        'cc.is_del' => 1,
                        'cc.type' => 1,
                        'cn.is_del' => 1,
                        'cn.type' => 1,
                        'cu.is_del' => 1,
                        'cu.type' => 1,
                    ];
                    $join = [
                        ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
                        ['yx_community_name cn', 'cc.community_id = cn.id', 'left'],
                    ];
                    $alias = 'cc';
                    $field1 = 'cc.img,cn.name cname,cu.name,cc.c_num,cc.original_price';
                    $course = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field1, 1, 1000);
                    if ($course) {
                        $order_info[$k]['img'] = $course['img'];
                        $order_info[$k]['cname'] = $course['cname'];
                        $order_info[$k]['name'] = $course['name'];
                        $order_info[$k]['c_num'] = $course['c_num'];
                        $order_info[$k]['original_price'] = $course['original_price'];
                    }
                } elseif ($v['cou_status'] == 4) {
                    $table1 = 'seckill_course';
                    $where1 = [
                        'sc.id' => $v['cou_id'],
                        'sc.is_del' => 1,
                        'sc.type' => 1,
                        'cu.is_del' => 1,
                        'cu.type' => 1,
                    ];
                    $join = [
                        ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
                        ['yx_member m', 'sc.mid = m.uid', 'left'],
                        ['yx_student s', 'sc.mid = m.uid', 'left'],
                    ];
                    $alias = 'sc';
                    $field1 = 'sc.img,m.cname,m.remarks,cu.name,sc.c_num,sc.original_price,s.name sname';
                    $course = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field1, 1, 1000);
                    if ($course) {
                        $order_info[$k]['img'] = $course['img'];
                        $order_info[$k]['cname'] = $course['cname'];
                        $order_info[$k]['remarks'] = $course['remarks'];
                        $order_info[$k]['name'] = $course['name'];
                        $order_info[$k]['c_num'] = $course['c_num'];
                        $order_info[$k]['original_price'] = $course['original_price'];
                        $order_info[$k]['sname'] = $course['sname'];
                    }

                } elseif ($v['cou_status'] == 5) {
                    $table1 = 'synthetical_course';
                    $where1 = [
                        'sc.id' => $v['cou_id'],
                        'sc.is_del' => 1,
                        'sc.type' => 1,
                        'cu.is_del' => 1,
                        'cu.type' => 1,
                    ];
                    $join = [
                        ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
                        ['yx_synthetical_name sn', 'sc.syntheticalcn_id = sn.id', 'left'],
                    ];
                    $alias = 'sc';
                    $field1 = 'sc.img,sn.name cname,cu.name,sc.c_num,sc.original_price';
                    $course = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field1, 1, 1000);
                    if ($course) {
                        $order_info[$k]['img'] = $course['img'];
                        $order_info[$k]['cname'] = $course['cname'];
                        $order_info[$k]['name'] = $course['name'];
                        $order_info[$k]['c_num'] = $course['c_num'];
                        $order_info[$k]['original_price'] = $course['original_price'];
                    }
                }
            }
        }
        if (!$order_info) {
            throw new OrderMissExceptionFind();
        } else {
            foreach ($order_info as $k => $v) {
                if (!empty($v['img'])) {
                    $order_info[$k]['img'] = get_take_img($v['img']);
                }
            }
            return jsonResponse('1000', '成功获取课程', $order_info);
        }

    }


    //其他课程支付失败加库存
    public static function setOtherStock($data)
    {
        if ($data['cou_status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            //yx_course
            $table = 'course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid']
            ];
            //查年当库存
            $Course_num_incs = Crud::setIncs($table, $where, 'surplus_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '加库存失败');
            }
        } elseif ($data['cou_status'] == 3) {
            $table = 'community_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            $Course_num_incs = Crud::setIncs($table, $where, 'surplus_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '加库存失败');
            }
        } elseif ($data['cou_status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            $Course_num_incs = Crud::setIncs($table, $where, 'surplus_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '加库存失败');
            }
        } elseif ($data['cou_status'] == 5) {
            $table = 'synthetical_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            $Course_num_incs = Crud::setIncs($table, $where, 'surplus_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '加库存失败');
            }
        }
        return 1000;
    }

    //增减库存
    public static function setIncsAndDelnum($data)
    {
        if ($data['cou_status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            //yx_course
            $table = 'course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid']
            ];
            //减库存
            $Course_num_Del = Crud::setDecs($table, $where, 'surplus_num', 1);
            if (!$Course_num_Del) {
                return jsonResponse('3000', '减库存失败');
            }
            //加销量
            $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '增销量失败');
            }
        }elseif ($data['cou_status'] == 2) {
            $table = 'experience_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //减库存
            $Course_num_Del = Crud::setDecs($table, $where, 'surplus_num', 1);
            if (!$Course_num_Del) {
                return jsonResponse('3000', '减库存失败');
            }
            //加销量
            $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '增销量失败');
            }
        } elseif ($data['cou_status'] == 3) {
            $table = 'community_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //减库存
            $Course_num_Del = Crud::setDecs($table, $where, 'surplus_num', 1);
            if (!$Course_num_Del) {
                return jsonResponse('3000', '减库存失败');
            }
            //加销量
            $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '增销量失败');
            }
        } elseif ($data['cou_status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //减库存
            $Course_num_Del = Crud::setDecs($table, $where, 'surplus_num', 1);
            if (!$Course_num_Del) {
                return jsonResponse('3000', '减库存失败');
            }
            //加销量
            $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '增销量失败');
            }
        } elseif ($data['cou_status'] == 5) {
            $table = 'synthetical_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //减库存
            $Course_num_Del = Crud::setDecs($table, $where, 'surplus_num', 1);
            if (!$Course_num_Del) {
                return jsonResponse('3000', '减库存失败');
            }
            //加销量
            $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '增销量失败');
            }
        }
        return 1000;
    }

    //验证是否是0元免费课
    public static function isPrice($Course_data)
    {
        if ($Course_data['present_price'] > 0) {
            $Course_data['status'] = 1;
        } else {
            $Course_data['status'] = 2;
        }
        return $Course_data;
    }

    //验证用户是否购买过此体验课
    //添加订单
    //传值 cat_id 购物车ID 为数组
    //传值 cou_id 课程ID 直接购买使用
    //传值 status 课程类型  直接购买使用
    //传值 user_id 用户ID
    //传值 Pass_order_status 1直接购买，2从购物车购买
    //传值 student_id 用户学生ID
    //传值 start_time 课程开始时间
    public static function payExperienceCourse($data)
    {
        if ($data['Pass_order_status'] == 1) {
            $CourseDate = self::getCourseDate($data);
            if ($CourseDate['present_price'] == 0 || $CourseDate['present_price'] == null) {
                $where = [
                    'is_del' => 1,
//                'status'=>['in',[2,8]],
                    'status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'uid' => $data['user_id'],
                    'price' => ['=', 0],
                    'cid' => $CourseDate['cou_id'],
//                    'start_time' => $data['start_time'], //判断用户开始时间
                ];
                $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
                if ($ExperienceCourse) {
                    return jsonResponse('3000', '你已购买此课程', 2013);
                } else {
                    return 1000;
                }
            } elseif ($CourseDate['present_price'] > 0) {
                return 1000;
            }
        } elseif ($data['Pass_order_status'] == 2) {
            $where = [
                'id' => ['in', $data['cat_id']],
                'user_id' => $data['user_id'],
                'is_del' => 1,
            ];
            $table = 'cat_course';
            //获取购物车课程信息
            $cat_data = Crud::getData($table, $type = 2, $where, $field = 'cou_id,status,num');
            if ($cat_data) {
                foreach ($cat_data as $k => $v) {
                    $CourseDate = self::getCourseDate($v);
                    if ($CourseDate['present_price'] == 0 || $CourseDate['present_price'] == null) {
                        $where = [
                            'is_del' => 1,
                            'status' => ['in', [2,5,6,8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                            'uid' => $data['user_id'],
                            'price' => ['=', 0],
                            'cid' => $v['cou_id'],
                        ];
                        $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
                        if ($ExperienceCourse) {
                            return jsonResponse('3000', '你已购买此课程', 2013);
                        } else {
                            return 1000;
                        }
                    }elseif ($CourseDate['present_price'] > 0){
                        return 1000;
                    }
                }
            } else {
                return jsonResponse('3000', '你已购买此课程', 2013);
            }

        }

    }

    //查询课程信息
    public static function getCourseDate($data)
    {
        if ($data['status'] == 1) {
            $where1 = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
            ];
            $field = ['c.id cou_id,cu.name,c.start_time,c.end_time,c.present_price'];
//            dump('普通课程');
//            (new CourseIDSMustBePostiveInt())->goCheck();
            $alias = 'c';
            $table = 'course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        } elseif ($data['status'] == 2) {
            $where1 = [
                'ec.is_del' => 1,
                'ec.type' => 1,
                'ec.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
            ];
            $field = ['ec.id cou_id,cu.name,ec.start_time,ec.end_time,ec.present_price'];
            $alias = 'ec';
            $table = 'experience_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump($info_course);exit;
//            dump('体验课程进入详情');
            //这是体验课程进入详情
        } elseif ($data['status'] == 3) {
            $where1 = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],//社区课目
            ];
            $field = 'cc.id cou_id,cu.name,cc.start_time,cc.end_time,cc.present_price';
            $alias = 'cc';
            $table = 'community_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('活动课程进入详情');
            //这是活动课程进入详情
        } elseif ($data['status'] == 4) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
            ];
            $field = ['sc.id cou_id,start_time,sc.end_time,cu.name,sc.present_price'];
//            (new CourseIDSMustBePostiveInt())->goCheck();
            $alias = 'sc';
            $table = 'seckill_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

//            dump('秒杀课程进入详情');
            //这是秒杀课程进入详情
        } elseif ($data['status'] == 5) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
            ];
            $field = ['sc.id cou_id,cu.name,sc.start_time,sc.end_time,sc.present_price'];
            $alias = 'sc';
            $table = 'synthetical_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        }
        return $info_course;

    }


}