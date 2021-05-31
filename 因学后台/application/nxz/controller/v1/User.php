<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 15:41
 */

namespace app\nxz\controller\v1;


use app\lib\exception\NothingMissException;
use app\common\model\Crud;
use think\Db;

class User extends Base
{
    //获取用户信息
    public static function getUserInfo($user_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $user_id
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,name,img,integral');
        if (!$info) {
            throw new NothingMissException();
        } else {
            //获取用户累计消费
            $where1 = [
                'status' => 8,//1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'is_del' => 1,
                'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
                'uid' => $user_id
            ];
            $sum = Db::name('order')->where($where1)->count();
            $student_data = Db::name('student')->where(['uid' => $user_id])->field('age')->find();
            if($student_data['age']==null){
                $age = '-';
            }else{
                $age = $student_data['age'];
            }
//            $sum_price=Db::name('order')->where($where1)->field('price')->find();
            $info['cou_num'] = $sum;
            $info['age'] = $age;
            return jsonResponseSuccess($info);
        }
    }




}