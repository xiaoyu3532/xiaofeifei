<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 19:40
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;
use app\lib\exception\UserMemberMissException;

class UserMemberTime
{
    //获取用户会员及会员时间
    public static function getUserMemberTime($user_id){
        $where = [
            'um.user_id'=>$user_id,
            'um.is_del'=>1,
            'um.type'=>1,
            'u.is_del'=>1,
            'u.type'=>1,
        ];
        $join = [
            ['yx_user u','um.user_id = u.id','left'],
        ];
        $alias = 'um';
        $field = 'u.is_member,um.start_time,end_time';
        $table = request()->controller();
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field);
        if(!$info){
            $table = 'user';
            $where1 = [
                'id'=>$user_id,
                'is_del'=>1,
                'type'=>1,
            ];
            $info = Crud::getData($table, $type = 1, $where1, $field = 'is_member');
            if($info){
                $info['start_time'] = '';
                $info['end_time'] = '';
                return jsonResponse('1000','获取成功',$info);
            }else{
                throw new UserMemberMissException();
            }
        }else{
            return jsonResponse('1000','获取成功',$info);
        }


    }

}