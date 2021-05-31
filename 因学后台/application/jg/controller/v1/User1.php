<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/11 0011
 * Time: 10:48
 */

namespace app\jg\controller\v1;

use app\common\controller\BaseController;
use app\common\model\Crud;
use app\lib\exception\MemberExplainMissException;

class User extends BaseController
{
    //获取报名此机构学生
    public static function getjgUserOrder($page = '1')
    {
        $data = input();
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'u.type' => 1,
                'u.is_del' => 1,
                'o.mid' => $mem_data['mem_id'],
                'o.is_del' => 1,
                'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            ];
        }
        (isset($data['name']) && !empty($data['name'])) && $where['u.name'] = ['like', '%' . $data['name'] . '%'];
        if (isset($data['time']) && !empty($data['time'])) {
            $start_time = $data['time'][0] / 1000;
            $end_time = $data['time'][1] / 1000;
            $where['o.create_time'] = ['between', [$start_time, $end_time]];
        }
        $table = request()->controller();
        $join = [
            ['yx_order o', 'u.id = o.uid', 'left'],
        ];
        $alias = 'u';
        $field = 'u.name,u.phone,u.img,u.cumulative_price,u.cumulative_retreat_price,u.aclass,o.create_time,u.id,u.remarks';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time', $field, $page, $pageSize = '1000', $group = 'o.uid');

        $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*', $group = 'o.uid');
        if ($info) {
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new MemberExplainMissException();
        }

    }

    //获取学生详情
    public static function getjgUserdetails($user_id)
    {
        $where = [
            'id' => $user_id,
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'img,name,phone,first_enroll_time,cumulative_price,cumulative_retreat_price,total_aclass,aclass');
        if($info){
            //求年龄
            $where1 = [
                'uid'=>$user_id
            ];
            $table1 = 'student';
            $sex_data = Crud::getData($table1, $type = 2, $where1, $field = 'sex');
            $sex_data = Many_One($sex_data);
            $sex_data = array_unique($sex_data);
            if($sex_data){
                $info['sex'] = implode(",", $sex_data);
            }
            //求最近报课时间
            $where2 = [
                'uid'=>$user_id,
                'status' =>['in',[2,5,6,8]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'is_del' =>1
            ];
            $table2 = 'order';
            $order_create_time = Crud::getData($table2, $type = 1, $where2, $field = 'create_time',$order = 'create_time desc');
            if($order_create_time){
                $info['order_create_time'] = $order_create_time['create_time'];
            }else{
                $info['order_create_time'] = null;
            }
            return jsonResponseSuccess($info);
        }
    }

    //学生备注
    public static function setjgremarks(){
        $data = input();
        $where = [
            'id'=>$data['user_id']
        ];
        $upData = [
            'remarks'=>$data['remarks']
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $upData);
        if($info){
            return jsonResponseSuccess($info);
        }
    }

}