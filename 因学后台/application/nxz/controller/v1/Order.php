<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/2 0002
 * Time: 9:42
 */

namespace app\nxz\controller\v1;


use app\lib\exception\CourseMissException;
use app\lib\exception\OrderMissExceptionFind;
use app\common\model\Crud;
use think\Db;


class Order extends Base
{
    //添加订单
    //传值 cat_id 购物车ID 为数组
    //传值 cou_id 课程ID 直接购买使用
    //传值 user_id 用户ID
    //传值 Pass_order_status 1直接购买，2从购物车购买
    //传值 student_id 用户学生ID

    public static function setAddOrder()
    {
        $data = input();
//        (new CatCourseMustBePostiveInt())->goCheck();
        //验证客户是否购买了此课程
        $payExperienceCourse = self::payExperienceCourse($data);
        if ($payExperienceCourse != 1000) {
            return $payExperienceCourse;
        }

        if($data['student_type'] ==1){
            if(is_int($data['age'])){
                return jsonResponse('1004','请正确年龄');
            }
            $student_data=self::addstudent($data);
            if(!is_int($student_data)){
                return $student_data;
            }else{
                $data['student_id'] = $student_data;
            }
        }else{
            //判断用户是否绑定了学生
            $isStudent = self::isStudent($data);
            if (!is_array($isStudent)) {
                return $isStudent;
            } else {
                $data['student_id'] = $isStudent['id'];
            }
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
                'id' => ['in',$data['cat_id']],
                'user_id' => $data['user_id'],
                'is_del' => 1,
                'status' => 6, //1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课，6逆行者课程
            ];
            $table = 'cat_course';
            //获取购物车课程信息
            $cat_data = Crud::getData($table, $type = 2, $where, $field = '*');
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

    //直接下订单
    public static function directOrder($data)
    {
        $price_sum = 0;
        $order_num = time() . rand(10, 99);
        $create_time = time();
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $data['cou_id']
        ];
        $couser = Crud::getData('contrarian_course', 1, $where, $field = '*');
        if (!$couser) {
            return jsonResponse('3000', '课程有误');
        }
        Db::startTrans();
        try {
            //添加小订单
            $adddata = [
                'order_id' => time() . rand(10, 99), //订单号
                'order_num' => $order_num, //大订单号
                'mid' => $couser['mem_id'], //机构id
                'cid' => $couser['id'], //课程id
                'name' => $couser['name'], //课程名称
                'status' => 8, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'paytype' => 3, //支付方式 1支付宝，2微信，3免费,4未知
                'price' => 0, //课程价格
                'uid' => $data['user_id'], //用户ID
                'student_id' => $data['student_id'], //学生信息ID
                'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
            ];
            $table = request()->controller();
            //添加小订单
            $order_data = Crud::setAdd($table, $adddata);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '添加小订单失败');
            }
            $data1 = [
                'order_num' => $order_num,
                'status' => 8,
                'price' => $price_sum,
                'create_time' => $create_time,
                'uid' => $data['user_id'],
                'paytype' => 3,
                'student_id' => $data['student_id'],//学生信息ID
            ];
            //添加大订单
            $table = 'order_num';
            $order_num_data = Crud::setAdd($table, $data1);
            if (!$order_num_data) {
                Db::rollback();
                return jsonResponse('3000', '添加大订单失败');
            }
            Db::commit();

            //删除购物车
            $where2 = [
                'user_id' => $data['user_id'],
                'cou_id' => $couser['id'],
                'status' => 2,
                'is_del' => 1,
            ];
            $cat_data = Crud::getCount('cat_course', $where2);
            if ($cat_data != 0) {
                $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
                if (!$del_cat) {
                    Db::rollback();
                    return jsonResponse('3000', '删除购物车失败');
                }
            }
            $success_array = [
                'order_num' => $order_num,
            ];
            return jsonResponseSuccess($success_array);

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }


    //购物车下订单
    public static function catOrder($cat_data, $data)
    {
        //验证课程
        foreach ($cat_data as $k => $v) {
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $v['cou_id']
            ];
            $couser = Crud::getData('contrarian_course', 1, $where, $field = '*');
            if (!$couser) {
                return jsonResponse('3000', $v['name'] . '课程已下架');
            }
        }
        Db::startTrans();
        try {
            //计算大订单价格
            $order_num = time() . rand(10, 99);
            foreach ($cat_data as $kk => $vv) {
                $curriculum = Crud::getData('contrarian_course', 1, ['id' => $vv['cou_id']], 'name');
                //获取小订单信息
                $adddata = [
                    'order_id' => time() . rand(10, 99), //订单号
                    'order_num' => $order_num, //大订单号
                    'mid' => $vv['mem_id'], //机构id
                    'cid' => $vv['cou_id'], //课程id
                    'name' => $curriculum['name'], //课程名称
                    'status' => 8, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                    'price' => 0, //课程价格
                    'uid' => $data['user_id'], //用户ID
                    'student_id' => $data['student_id'], //学生信息ID
                    'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体课程，6逆行者课程
                    'paytype' => 3, //3免费
                ];
                $table = request()->controller();
                $order_data = Crud::setAdd($table, $adddata);
                if (!$order_data) {
                    Db::rollback();
                    return jsonResponse('3000', '购物车下订单失败');
                }
                //删除购物车
                $where2 = [
                    'id' => $vv['id']
                ];
                $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
                if (!$del_cat) {
                    Db::rollback();
                    return jsonResponse('3000', '删除购物车失败', 2001);
                }

            }

            //大订单
            $create_time = time();
            $data1 = [
                'order_num' => $order_num,
                'status' => 8,
                'price' => 0,
                'create_time' => $create_time,
                'uid' => $data['user_id'],
                'paytype' => 3,
                'student_id' => $data['student_id'], //学生信息ID
            ];
            //添加大订单
            $table = 'order_num';
            $order_num_data = Crud::setAdd($table, $data1);

            if (!$order_num_data) {
                Db::rollback();
                return jsonResponse('3000', '购物车下大订单失败', 2013);
            } else {
                Db::commit();
                $success_array = [
                    'order_num' => $order_num,
                ];
                return jsonResponse('1000', '下单成功', $success_array);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }


    //验证用户是否购买过此课程
    //传值 cat_id 购物车ID 为数组
    //传值 cou_id 课程ID 直接购买使用
    //传值 status 课程类型  直接购买使用
    //传值 user_id 用户ID
    //传值 Pass_order_status 1直接购买，2从购物车购买
    public static function payExperienceCourse($data)
    {
        if ($data['Pass_order_status'] == 1) {
            $where = [
                'is_del' => 1,
                'status' => 8, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'uid' => $data['user_id'],
                'mid' => $data['mem_id'],
                'cid' => $data['cou_id'],
                'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
            ];
            $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
            if ($ExperienceCourse) {
                return jsonResponse('1003', '你已报名此课程', 2013);
            } else {
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
            $cat_data = Crud::getData($table, $type = 2, $where, $field = 'cou_id,status,num,mem_id');
            if ($cat_data) {
                foreach ($cat_data as $k => $v) {
                    $where = [
                        'is_del' => 1,
                        'status' => 8, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                        'uid' => $data['user_id'],
                        'cid'=>$v['cou_id'],
                        'mid' => $v['mem_id'],
                        'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
                    ];
                    $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
                    if ($ExperienceCourse) {
                        return jsonResponse('1003', '你已购买此课程', 2013);
                    } else {
                        return 1000;
                    }
                }
            } else {
                return jsonResponse('1003', '你已购买此课程', 2013);
            }

        }

    }

    //验证用户是否绑定了学生
    public static function isStudent($data)
    {
        $where = [
            'is_del' => 1,
            'uid' => $data['user_id'],
        ];
        $Student = Crud::getData('student', 1, $where, 'id');
        if (!$Student) {
            return jsonResponse('1001', '请添加用户信息');
        } else {
            return $Student;
        }
    }


    //订单展示
    public static function getOrder()
    {
        $data = input();
        $where = [
            'o.uid' => $data['user_id'],
            'o.cou_status' => 6,
            'o.is_del' => 1,
        ];
        isset($data['status']) && !empty($data['status']) && $where['o.status'] = $data['status'];
        $table = request()->controller();
        $join = [
            ['yx_contrarian_course co', 'o.cid = co.id', 'left'],
        ];
        $alias = 'o';
        $field = ['o.name,co.title,co.img,o.id,o.cid'];
        $order = 'o.create_time';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order, $field, $page);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new OrderMissExceptionFind();
        }
    }

    //添加学生信息
    public static function addstudent($data){
        $data1 = [
            'uid' => $data['user_id'],
            'name' => $data['name'],
//            'sex' => $data['sex'],
            'age' => $data['age'],
            'phone' => $data['phone'],
        ];
        isset($data['recom']) && !empty($data['recom']) && $data1['recom'] = $data['recom'];
        $Student_info = Crud::setAdd('student', $data1, 2);
        if ($Student_info) {
            return (int)$Student_info;
        }else{
            return jsonResponse('1002','添加信息失败');
        }
    }

     //删除购物车
    public static function delOrder($order_id)
    {
        $where = [
            'id' => ['in',$order_id]
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new ActivityMissException();
        } else {
            return jsonResponse('1000', '删除成功', $info);
        }
    }
}