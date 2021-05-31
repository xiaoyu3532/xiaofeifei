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
use app\validate\CatCourseMustBePostiveInt;
use app\xcx\controller\v1\Course;
use app\common\model\Crud;
use think\Db;


class Order extends BaseController
{
    //添加订单
    //传值 cat_id 购物车ID 为数组
    //传值 cou_id 课程ID 直接购买使用
    //传值 status 课程类型  直接购买使用
    //传值 user_id 用户ID
    //传值 Pass_order_status 1直接购买，2从购物车购买
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
        if ($data['status'] == 2) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            //添加体验课程
            $res = self::directExperienceCourse($data);
            return $res;
        } else {
            //添加其他课程
            $res = self::directCourse($data);
            return $res;
        }


    }

    //直接购买体验课下订单
    public static function directExperienceCourse($data)
    {
        $price_sum = 0;
        $order_num = time() . rand(10, 99);
        $create_time = time();
        //计算小订单
        $where = [
            'id' => $data['cou_id'],
            'type' => 1,
            'is_del' => 1,
        ];
        $table = 'experience_course';
        //体验课将获取机构ID
        $experience_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'mid', $order = '', $group = 'mem_id', $pagetype = 1,1,1000);

        if (!$experience_data) {
            throw new OrderMissExceptionFind();
        } else {
            //根据机构ID查询报有的课程
            $where1 = [
                'ec.mid' => ['in', $experience_data['mid']],
                'ec.type' => 1,
                'ec.is_del' => 1,
            ];

            $join = [
                ['yx_curriculum c', 'ec.curriculum_id = c.id', 'left'],
            ];
            $alias = 'ec';
            $field = 'ec.id,ec.present_price,c.name';
            //查询出机构所有的体验课
            $experience_couser = Crud::getRelationData($table, $type = 2, $where1, $join, $alias, $order = '', $field);
            Db::startTrans();
            try {
                //添加小订单
                foreach ($experience_couser as $k => $v) {
                    //小订单判断体验课程是否付费
                    if ($v['present_price'] > 0) {
                        $status = 1;
                        $paytype = 4;
                    } else {
                        $status = 8;
                        $paytype = 3;
                    }
                    $adddata = [
                        'order_id' => time() . rand(10, 99), //订单号
                        'order_num' => $order_num, //大订单号
                        'mid' => $experience_data['mid'], //机构id
                        'cid' => $v['id'], //课程id
                        'name' => $v['name'], //课程名称
                        'status' => $status, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                        'paytype' => $paytype, //支付方式 1支付宝，2微信，3免费,4未知
                        'price' => $v['present_price'], //课程价格
                        'uid' => $data['user_id'], //用户ID
                        'student_id' => $data['student_id'], //学生信息ID
                        'cou_status' => 2, //1普通课程，2体验课程，3活动课程，4秒杀课程
                    ];
                    $price_sum = $price_sum + $v['present_price'];
                    $table = request()->controller();
                    $order_data = Crud::setAdd($table, $adddata);
                }
                if (!$order_data) {
                    Db::rollback();
                    return jsonResponse('3000', '体验课添加小订单失败', 2001);
                }
                //小订单判断体验课程是否付费
                if ($price_sum > 0) {
                    $status = 1;
                    $paytype = 4;
                    $price_status = 2; //1为不需要支付，2需要支付
                } else {
                    $status = 8;
                    $paytype = 3;
                    $price_status = 1; //1为不需要支付，2需要支付
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
                    $plus_course = Db::name('member')->where(['uid' => ['in', $experience_data['mid']]])->setInc('enroll_num');
                    if (!$plus_course) {
                        Db::rollback();
                        return jsonResponse('3000', '体验课机构加名额(报名人数)失败', 2003);
                    }
                    //获取机构信息
                    $where2 = [
                        'uid' => ['in', $experience_data['mid']],
                        'is_del' => 1,
                        'status' => 1,
                    ];
                    $field = 'balance,give_type,uid,ismember';
                    $table = 'member';
                    //查询出机构信息
                    $experience_couser = Crud::getDataunpage($table, $type = 2, $where2, $field, $order = '');
                    if ($experience_couser) {
                        //循环判断机构余额与是否有赠送名额
                        foreach ($experience_couser as $kk => $vv) {
                            $quota_data = self::setQuota($vv);
                        }
                        if ($quota_data != 1000) {
                            Db::rollback();
                            return $quota_data;
                        } elseif ($quota_data == 1000) {
                            Db::commit();
                            $success_array = [
                                'order_num' => $order_num,
                                'price_status' => $price_status //1为不需要支付，2需要支付
                            ];
                            return jsonResponse('1000', '体验课下单成功', $success_array);
                        }
                    } else {
                        Db::rollback();
                        return jsonResponse('3000', '机构信息有误', 2008);
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
    }

    //直接购买其他课程下订单
    public static function directCourse($data)
    {
        //查看课程类型
        if ($data['status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            //yx_course
            $table = 'course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cou_id']
            ];
            $Course_data = Crud::getData($table, $type = 1, $where, $field = 'id,name cou_name,present_price price,surplus_num,mid mem_id');
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '普通课程没有库存', 1003);
            }
        } elseif ($data['status'] == 3) {
            $table = 'community_course';
            $where = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'c.id' => $data['cou_id'],
                'c.type' => 1,
                'c.is_del' => 1,
            ];
            $join = [
                ['yx_course c', 'cc.cou_id = c.id', 'left'],
            ];
            $alias = 'cc';
            $field = 'cc.price,cc.num,cc.by_time,c.name cou_name,cc.mem_id';
            //查询活动课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断截至时间
            if ($Course_data['by_time'] < time()) {
                return jsonResponse('2000', '社区活动课程截至时间已过', 1004);
            }
            //判断库存
            if ($Course_data['num'] == 0) {
                return jsonResponse('2000', '社区活动课程无库存', 1005);
            }
        } elseif ($data['status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'c.id' => $data['cou_id'],
                'c.type' => 1,
                'c.is_del' => 1,
                'st.is_del' => 1,
                'st.type' => 1,
            ];
            $join = [
                ['yx_course c', 'sc.cou_id = c.id', 'left'],
                ['yx_seckill_theme st', 'sc.st_id = st.id', 'left'],
            ];
            $alias = 'sc';
            $field = 'sc.present_price,sc.surplus_num,st.start_time,st.end_time,c.name cou_name,sc.mid';
            //查询秒杀课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '秒杀课程无库存', 1006);
            }
            //判断秒杀时间 小于开始时间
            if ($Course_data['start_time'] > time()) {
                return jsonResponse('2000', '秒杀课程开始时间没到', 1007);
            }
            //大于结束时间
            if ($Course_data['end_time'] < time()) {
                return jsonResponse('2000', '秒杀课程已过结束时间', 1008);
            }

        }
        $price_sum = $Course_data['price'];
        $order_num = time() . rand(10, 99);
        $create_time = time();
        $data1 = [
            'order_num' => $order_num,
            'status' => 1, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'price' => $price_sum,//总价
            'create_time' => $create_time,
            'uid' => $data['user_id'],
            'student_id' => $data['student_id'], //学生信息ID
//            'sname' => $data['sname'],
//            'phone' => $data['phone'],
//            'sex' => $data['sex'],
//            'age' => $data['age'],
        ];

        //获取小订单信息
        $adddata = [
            'order_id' => time() . rand(10, 99), //订单号
            'order_num' => $order_num, //大订单号
            'mid' => $Course_data['mid'], //机构id
            'cid' => $data['cid'], //课程id
            'name' => $Course_data['cou_name'], //课程名称
            'status' => 1, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'price' => $Course_data['price'], //课程价格
            'uid' => $data['user_id'], //用户ID
            'student_id' => $data['student_id'], //学生信息ID
//            'phone' => $data['phone'], //手机号
//            'sname' => $data['sname'], //学生姓名
//            'sex' => $data['sex'],     //学生性别
//            'age' => $data['age'],     //学生年龄
            'cou_status' => $data['status'], //1普通课程，2体验课程，3活动课程，4秒杀课程
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
                Db::commit();
                $success_array = [
                    'order_num' => $order_num,
                    'price_status' => 2 //1为不需要支付，2需要支付
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
                    $arrayOther[] = self::manyCourse($v);
                    //判断是否是数组，如果不是直接返回
                    if (!is_array($arrayOther)) {
                        return $arrayOther;
                    }
                }
            }

            //购物车体验课程下单操作
            if (isset($arrayexp) && !empty($arrayexp)) {
                $setCatExperienceCourse = self::setCatExperienceCourse($arrayexp, $order_num, $data);
                if ($setCatExperienceCourse != 1000) {
                    return $setCatExperienceCourse;
                } else {
                    foreach ($arrayOther as $kp => $vp) {
                        $price_sum = (int)$vp['price'] + $price_sum;
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

            if (isset($arrayOther) && !empty($arrayOther)) {
                foreach ($arrayOther as $kp => $vp) {
                    $price_sum = (int)$vp['price'] + $price_sum;
                }
                //购物车其他课程下单操作
                $setCatOtherCourse = self::setCatOtherCourse($arrayOther, $order_num, $data);
                if ($setCatOtherCourse != 1000) {
                    return $setCatOtherCourse;
                }
                $paytype = 4; //支付方式 1支付宝，2微信，3免费,4未知
                $status = 1;//大订单支付状态  默认8为免费（体验课）
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
                if ($price_sum >= 0.1) {
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
        $where = [
            'cou_id' => $cou_id,
            'type' => 1,
            'is_del' => 1,
        ];
        $table = 'experience_course';
        //如果为体验课将获取机构ID
        $experience_data = Crud::getData($table, $type = 1, $where, $field = 'mem_id');
        if (!$experience_data) {
            throw new OrderMissExceptionFind();
        }
        $where1 = [
            'ex.mem_id' => ['in', $experience_data['mem_id']],
            'ex.type' => 1,
            'ex.is_del' => 1,
            'c.is_del' => 1,
            'c.type' => 1,
        ];
        $join = [
            ['yx_course c', 'ex.cou_id = c.id', 'left'],
        ];
        $alias = 'ex';
        $field = 'ex.mem_id,ex.cou_id,ex.present_price,c.name cou_name';  //
        //查询出机构所有的体验课
        $experience_couser = Crud::getRelationData($table, $type = 2, $where1, $join, $alias, $order = '', $field);
        if ($experience_couser) {
            foreach ($experience_couser as $k => $v) {
                $experience_couser[$k]['cou_status'] = 2;
                if ($v['price'] > 0) {
                    $status = 1;
                } else {
                    $status = 8;
                }
                $experience_couser[$k]['status'] = $status;
//                $experience_couser[$k]['price'] = 0;
            }
        }
        return $experience_couser;
    }

    //购物车体验课程下单操作
    public static function setCatExperienceCourse($arrayexp, $order_num, $data)
    {
        //三维数组变二维数组
        Db::startTrans();
        $price_sum = 0;
        $except_mem_id = [];
        $arrayexp = Three_Two_array($arrayexp);
        foreach ($arrayexp as $kk => $vv) {
            //获取小订单信息
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'mid' => $vv['mem_id'], //机构id
                'cid' => $vv['cou_id'], //课程id
                'name' => $vv['cou_name'], //课程名称
                'status' => $vv['status'], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'price' => $vv['price'], //课程价格
                'uid' => $data['user_id'], //用户ID
                'student_id' => $data['student_id'], //学生信息ID
//                'phone' => $data['phone'], //手机号
//                'sname' => $data['sname'], //学生姓名
//                'sex' => $data['sex'],     //学生性别
//                'age' => $data['age'],     //学生年龄
                'cou_status' => 2, //1普通课程，2体验课程，3活动课程，4秒杀课程
                'paytype' => 3, //3免费
            ];
            if ($vv['price'] > 0) {
                $except_mem_id[] = $vv['mem_id'];
            }
            $table = request()->controller();
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下体验课订单失败', 2011);
            }
        }

        //去重多余的机构ID
        $mem_ids = assoc_unique($arrayexp, 'mem_id');
        //除去金额不为0的机构ID
        $mem_ids = array_diff($mem_ids, $except_mem_id);
        if ($mem_ids) {
            foreach ($mem_ids as $kq => $vq) {
                //获取机构信息
                $where2 = [
                    'uid' => $vq['mem_id'],
                    'is_del' => 1,
                    'status' => 1,
                ];
                $field = 'balance,give_type,uid,ismember';
                $table = 'member';
                //查询出机构信息
                $experience_couser = Crud::getDataunpage($table, $type = 1, $where2, $field, $order = '');
                if ($experience_couser) {
                    //循环判断机构余额与是否有赠送名额
                    $quota_data = self::setQuota($experience_couser);
                    if (!$quota_data) {
                        Db::rollback();
                        return jsonResponse('3000', '购物车下体验课大订单失败', 2012);
                    }
                }
            }
            if ($quota_data) {
                Db::commit();
                return 1000;
            }
        } else {
            if ($order_data) {
                Db::commit();
                return 1000;
            }
        }
    }

    //购物车下单组合其他课程
    public static function manyCourse($vat_v)
    {
        if ($vat_v['status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            $table = 'course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $vat_v['cou_id']
            ];
            $Course_data = Crud::getData($table, $type = 1, $where, $field = 'id cou_id,name cou_name,present_price,surplus_num,mid mem_id');
            if ($Course_data['surplus_num'] == 0) {
                return jsonResponse('2000', '普通课程没有库存', 1003);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            $Course_data['status'] = 1;
        } elseif ($vat_v['status'] == 3) {
            $table = 'community_course';
            $where = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.cou_id' => $vat_v['cou_id'],
                'c.type' => 1,
                'c.is_del' => 1,
            ];
            $join = [
                ['yx_course c', 'cc.cou_id = c.id', 'left'],
            ];
            $alias = 'cc';
            $field = 'cc.present_price,cc.num,cc.by_time,cc.mem_id,cc.cou_id,c.name cou_name';
            //查询活动课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断截至时间
            if ($Course_data['by_time'] < time()) {
                return jsonResponse('2000', '社区活动课程截至时间已过', 1004);

            }
            //判断库存
            if ($Course_data['num'] == 0) {
                return jsonResponse('2000', '社区活动课程无库存', 1005);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            $Course_data['status'] = 1;
            unset($Course_data['num']);
            unset($Course_data['by_time']);
        } elseif ($vat_v['status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.cou_id' => $vat_v['cou_id'],
                'c.type' => 1,
                'c.is_del' => 1,
                'st.is_del' => 1,
                'st.type' => 1,
            ];
            $join = [
                ['yx_course c', 'sc.cou_id = c.id', 'left'],
                ['yx_seckill_theme st', 'sc.st_id = st.id', 'left'],
            ];
            $alias = 'sc';
            $field = 'sc.price,sc.num,st.start_time,st.end_time,c.name cou_name,sc.cou_id,c.mid mem_id';
            //查询秒杀课程
            $Course_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
            //判断库存
            if ($Course_data['num'] <= 0) {
                return jsonResponse('2000', '秒杀课程无库存', 1006);
            }
            //判断秒杀时间 小于开始时间
            if ($Course_data['start_time'] > time()) {
                return jsonResponse('2000', '秒杀开始时间未到', 1007);
            }
            //大于结束时间
            if ($Course_data['end_time'] < time()) {
                return jsonResponse('2000', '秒杀活动已结束', 1008);
            }
            $Course_data['cou_status'] = $vat_v['status'];
            $Course_data['status'] = 1;
            unset($Course_data['num']);
            unset($Course_data['start_time']);
            unset($Course_data['end_time']);
        }
        return $Course_data;
    }

    //购物车其他课程下单操作
    public static function setCatOtherCourse($arrayOther, $order_num, $data)
    {
        Db::startTrans();
        foreach ($arrayOther as $kk => $vv) {
            //获取小订单信息
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'mid' => $vv['mem_id'], //机构id
                'cid' => $vv['cou_id'], //课程id
                'name' => $vv['cou_name'], //课程名称
                'status' => 1, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'price' => $vv['price'], //课程价格
                'student_id' => $data['student_id'], //学生信息ID
                'uid' => $data['user_id'], //用户ID
//                'phone' => $data['phone'], //手机号
//                'sname' => $data['sname'], //学生姓名
//                'sex' => $data['sex'],     //学生性别
//                'age' => $data['age'],     //学生年龄
                'cou_status' => $vv['cou_status'], //1普通课程，2体验课程，3活动课程，4秒杀课程
            ];
            //添加订单号
            $table = request()->controller();
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下其他课程订单失败', 2013);
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
    public static function getOrder($user_id, $status, $page = '1', $pageSize = '16')
    {
        $where = [
            'o.uid' => $user_id,
//            'o.status'=>['in',[2,5,8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'o.status' => ['in', $status], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'o.is_del' => 1
        ];
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_course c', 'o.cid = c.id', 'left'],
            ['yx_category ca', 'c.cid = ca.id', 'left'],
            ['yx_member m', 'o.mid = m.uid', 'left'],
            ['yx_student s', 'o.student_id = s.id', 'left'],
        ];
        $alias = 'o';
        $field = 'c.id,c.img,ca.name caname,m.cname,m.remarks,c.name,c.c_num,o.already_num,o.price,c.original_price,o.cou_status,s.name sname';
        $table = request()->controller();
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field, $page, $pageSize);
        if (!$info) {
            throw new OrderMissExceptionFind();
        } else {
            return jsonResponse('1000', '成功获取课程', $info);
        }

    }


}