<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 15:41
 */

namespace app\xcx\controller\v1;


use app\lib\exception\NothingMissException;
use app\common\model\Crud;
use think\Db;
class User
{
    //获取用户信息
    public static function getUserInfo($user_id){
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id'=>$user_id
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,integral,aclass,is_member');
        if (!$info) {
            throw new NothingMissException();
        } else {
            //获取用户累计消费
            $where1 = [
                'status'=>['in',[2,5,6]],//1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'is_del'=>1,
                'uid'=>$user_id
            ];

            $sum_price=Db::name('order')->where($where1)->sum('price');
//            $sum_price=Db::name('order')->where($where1)->field('price')->find();
            $info['sum_price'] = $sum_price;
            return jsonResponseSuccess($info);
        }
    }

}