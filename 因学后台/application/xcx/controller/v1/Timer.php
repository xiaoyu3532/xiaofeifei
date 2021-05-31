<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/25 0025
 * Time: 21:03
 */

namespace app\xcx\controller\v1;

use app\lib\exception\NothingMissException;
use app\common\model\Crud;

class Timer
{
    //订单定时间器
    public static function Ordertiming()
    {

        $time = time() - 900;
        $where = [
            'status' => 9,
            'is_del' => 1,
            'update_time' => ['<=', $time]
        ];
//        dump($where);
        $table = 'order';
        $cat_data = Crud::getData($table, $type = 2, $where, $field = 'id,order_id,order_num,cid,cou_status');
        if ($cat_data) {
            foreach ($cat_data as $k => $v) {
                //修改订单状态
                $where1 = [
                    'id' => $v['id']
                ];
                $upData = [
                    'status' => 1,
                    'update_time' => time()
                ];
                $order_update = Crud::setUpdate($table, $where1, $upData);
                if (!$order_update) {
                    throw new NothingMissException();
                }
                //课程加库存
                $data = [
                    'cou_status' => $v['cou_status'],
                    'cid' => $v['cid'],
                ];
                $setIncs = self::setOtherStock($data);
                if ($setIncs != 1000) {
                    return $setIncs;
                }
            }
            //修改大订单状态
            $table1 = 'order_num';
            $where1 = [
                'order_num' => $cat_data[0]['order_num'],
                'status' => 9
            ];
            $order_num_data = Crud::getData($table1, $type = 1, $where1, $field = 'id');
            if ($order_num_data) {
                $upData1 = [
                    'update_time' => time(),
                    'status' => 1
                ];
                $order_update = Crud::setUpdate($table1, $where1, $upData1);
                if (!$order_update) {
                    return jsonResponse('3000', '修改大订单状态');
                }
            } else {
                return jsonResponse('3000', '无大订单数据');
            }
        } else {
            return jsonResponse('3000', '无修改状态');
        }
        file_put_contents('time.log', print_r(date('Y-m-d H:i:s', time())) . PHP_EOL);
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
            $Course_num_Decs = Crud::setDecs($table, $where, 'enroll_num', 1);
            if (!$Course_num_Decs) {
                return jsonResponse('3000', '减销量失败');
            }
        } elseif ($data['cou_status'] == 2) {
            $table = 'experience_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            $Course_num_incs = Crud::setIncs($table, $where, 'surplus_num', 1);
            if (!$Course_num_incs) {
                return jsonResponse('3000', '加库存失败');
            }
            $Course_num_Decs = Crud::setDecs($table, $where, 'enroll_num', 1);
            if (!$Course_num_Decs) {
                return jsonResponse('3000', '减销量失败');
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
            $Course_num_Decs = Crud::setDecs($table, $where, 'enroll_num', 1);
            if (!$Course_num_Decs) {
                return jsonResponse('3000', '减销量失败');
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
            $Course_num_Decs = Crud::setDecs($table, $where, 'enroll_num', 1);
            if (!$Course_num_Decs) {
                return jsonResponse('3000', '减销量失败');
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
            $Course_num_Decs = Crud::setDecs($table, $where, 'enroll_num', 1);
            if (!$Course_num_Decs) {
                return jsonResponse('3000', '减销量失败');
            }
        }
        return 1000;
    }

    //课程结束时更改
    public static function updateOrderTime1()
    {
        $time = time();
        $table = 'order';
        //修改课程开始
        $where = [
            'status' => 2,
            'is_del' => 1,
            'start_time' => ['<=', $time]
        ];
        $start_time_data = Crud::getData($table, $type = 2, $where, $field = 'id');
        if ($start_time_data) {
            //修改为课程开始
            foreach ($start_time_data as $k => $v) {
                $where1 = [
                    'id' => $v['id']
                ];
                $order_start_time = Crud::setUpdate($table, $where1, ['status' => 5]);
                dump(123);
                dump($order_start_time);
            }
        }

        $where = [
            'status' => ['in', [2, 5]],
            'is_del' => 1,
            'create_time' => ['<', $time]  //修改成为结束时间
        ];
        $order_time = Crud::getData($table, $type = 2, $where, $field = 'id');
        if ($order_time) {
            foreach ($order_time as $kk => $vv) {
                $where2 = [
                    'id' => $vv['id']
                ];
                $order_create_time=Crud::setUpdate($table, $where2, ['status' => 6]); //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                dump(456);
                dump($order_create_time);
            }
        }


    }

    //课程自动加名额
    public static function addSurplusNum(){
        //查询所有为0的课程
        //1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课
        $table1 ='course';
        $table2 ='experience_course';
        $table3 ='community_course';
        $table4 ='seckill_course';
        $table5 ='synthetical_course';
        $where = [
            'type' => 1,
            'is_del' => 1,
            'num_type' => 1, //1为0时自动增长，2为用户自己输入 （用户不输入库存时，为自动增长）
        ];
        $course_data = Crud::getData($table1, $type = 2, $where, $field = 'id,surplus_num');
//        dump($course_data);
        $experience_course_data = Crud::getData($table2, $type = 2, $where, $field = 'id,surplus_num');
//        dump($experience_course_data);
        $community_course_data = Crud::getData($table3, $type = 2, $where, $field = 'id,surplus_num');
//        dump($community_course_data);
        $seckill_course_data = Crud::getData($table4, $type = 2, $where, $field = 'id,surplus_num');
//        dump($seckill_course_data);
        $synthetical_course_data = Crud::getData($table5, $type = 2, $where, $field = 'id,surplus_num');
//        dump($synthetical_course_data);exit;
        if($course_data){
            foreach ($course_data as $k=>$v){
                if($v['surplus_num'] ==0){
                    Crud::setIncs($table1,['id'=>$v['id']],'surplus_num',5);
                }
            }
        }
        if($experience_course_data){
            foreach ($experience_course_data as $ek=>$ev){
                if($ev['surplus_num'] ==0){
                    Crud::setIncs($table2,['id'=>$ev['id']],'surplus_num',5);
                }
            }
        }
        if($community_course_data){
            foreach ($community_course_data as $ck=>$cv){
                if($cv['surplus_num'] ==0){
                    Crud::setIncs($table3,['id'=>$cv['id']],'surplus_num',5);
                }
            }
        }
        if($seckill_course_data){
            foreach ($seckill_course_data as $sk=>$sv){
                if($sv['surplus_num'] ==0){
                    Crud::setIncs($table4,['id'=>$sv['id']],'surplus_num',5);
                }
            }
        }
        if($synthetical_course_data){
            foreach ($synthetical_course_data as $yk=>$yv){
                if($yv['surplus_num'] ==0){
                    Crud::setIncs($table5,['id'=>$yv['id']],'surplus_num',5);
                }
            }
        }
    }
}