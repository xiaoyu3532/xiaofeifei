<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/11 0011
 * Time: 15:56
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberExplainMissException;
use app\lib\exception\NothingMissException;

class User extends BaseController
{
    //总平台获取机构信息
    public static function getpcUserData($page = '1')
    {
        $data = input();
        $where = [
            'type' => 1,
            'is_del' => 1,
        ];
        if (isset($data['time']) && !empty($data['time'])) {
            $start_time = $data['time'][0] / 1000;
            $end_time = $data['time'][1] / 1000;
            $where = [
                'create_time' => ['between', [$start_time, $end_time]]
            ];
        }
        (isset($data['name']) && !empty($data['name'])) && $where['name'] = ['like', '%' . str_replace(" ", '', $data['name']) . '%'];
        (isset($data['cname']) && !empty($data['cname'])) && $where['sign_mem_name'] = ['like', '%' . str_replace(" ", '', $data['cname']) . '%'];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'name,phone,img,cumulative_price,cumulative_retreat_price,aclass,create_time,id,remarks,sign_mem_name',$order = 'create_time desc',$page, $pageSize = '16');
        if(!$info){
            throw new NothingMissException();
        }else{
            $num = Crud::getCounts($table, $where);
                $info = self::getUserMemberCname($info);
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            return jsonResponse('1000','成功获取活动图',$info);
        }


    }






    public static function getpcUserDatas($page = '1')
    {
        $data = input();
        $where = [
            'type' => 1,
            'is_del' => 1,
        ];
        if (isset($data['time']) && !empty($data['time'])) {
            $start_time = $data['time'][0] / 1000;
            $end_time = $data['time'][1] / 1000;
            $where = [
                'create_time' => ['between', [$start_time, $end_time]]
            ];
        }
        (isset($data['name']) && !empty($data['name'])) && $where['name'] = ['like', '%' . str_replace(" ", '', $data['name']) . '%'];
        if (isset($data['cname']) && !empty($data['cname'])) {

            $where1 = [
                'm.is_del' => 1,
                'm.status' => 1,
                'o.is_del' => 1,
                'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            ];
            $where1['m.cname'] = ['like', '%' . str_replace(" ", '', $data['cname']) . '%'];
            $join = [
                ['yx_member m', 'o.mid = m.uid', 'left'],
            ];
            $alias = 'o';
            $table1 = 'order';
            $cname_data = Crud::getRelationData($table1, $type = 2, $where1, $join, $alias, $order = '', $field = 'o.uid', $page, $pageSize = '16');
            $cname_data = Many_One($cname_data);
            $cname_data = array_unique($cname_data);
            $num = count($cname_data);
            //取用户信息
            $info = [];
            foreach ($cname_data as $k => $v) {
                $where['id'] = $v;
                $table = request()->controller();
                $field = 'name,phone,img,cumulative_price,cumulative_retreat_price,aclass,create_time,id,remarks';
                $infos = Crud::getData($table, $type = 1, $where, $field, $order = 'create_time desc', $page, $pageSize = '16');
                if ($infos != null) {
                    $info[] = $infos;
                }
            }
            $cname_data = self::getUserMemberCname($info);
            $info_data = [
                'info' => $cname_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            $table = request()->controller();
            $field = 'name,phone,img,cumulative_price,cumulative_retreat_price,aclass,create_time,id,remarks';
            $info = Crud::getData($table, $type = 2, $where, $field, $order = 'create_time desc', $page, $pageSize = '16');
            $num = Crud::getCounts($table, $where);
            if ($info) {
                $info = self::getUserMemberCname($info);
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new MemberExplainMissException();
            }
        }

    }

    //获取学生详情
    public static function getpcUserdetails($user_id)
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
    public static function setpcremarks(){
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

    //获取用户所在机构名称
    public static function getUserMemberCname($info)
    {
        $table1 = 'order';
        foreach ($info as $k => $v) {
            if ($v != null) {
                $where1 = [
                    'o.uid' => $v['id'],
                    'o.is_del' => 1,
                    'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                ];
                $join = [
                    ['yx_member m', 'o.mid = m.uid', 'left'],
                ];
                $alias = 'o';
                $cname_data = Crud::getRelationData($table1, $type = 2, $where1, $join, $alias, $order = '', $field = 'm.cname', $page = '1', $pageSize = '1000');
                if ($cname_data) {
                    $cname_data = Many_One($cname_data);
                    $cname_data = array_unique($cname_data);
                    $info[$k]['cname'] = implode(",", $cname_data);
                }
            }
        }
        return $info;
    }

}