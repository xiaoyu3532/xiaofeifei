<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 16:21
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\UpdateMissException;
use app\lib\exception\UserMemberMissException;

class LoginFinance extends BaseController
{
    //财务密码登录
    public static function getpcLoginFinance($data)
    {
        $mem_data = self::isuserData();
        $table = 'login_finance';
        $where = [
            'mem_id' => $mem_data['mem_id'],
            'is_del' => 1,
        ];
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if (!$info) {
            throw new UserMemberMissException();
        } else {
            $password = splice_password($data['password'], $info['salt']);
            if ($password != $info['password']) {
                return jsonResponse('2000', '密码不正确');
            }

            $where1 = [
                'mem_id' => $mem_data['mem_id'],
                'is_del' => 1,
                'password' => $password,
            ];
            $list_info = Crud::getData($table, $type = 1, $where1, $field = '*');
            if(!$list_info){
                throw new ISUserMissException();
            }

            //更新时间
            $where2 = [
                'mem_id' => $mem_data['mem_id'],
            ];
            Crud::setUpdate($table, $where2, ['last_login_time' => time()]);
            return 1000;
        }

    }

    //修改账务密码
    public static function editpcLoginFinance()
    {
        $mem_data = self::isuserData();
        $where = [
            'mem_id' => $mem_data['mem_id'],
            'is_del' => 1,
        ];
        $data = input();

        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');;
        if (!$info) {
            throw new UserMemberMissException();
        } else {
            $password = splice_password($data['password'], $info['salt']);
            if ($password != $info['password']) {
                return jsonResponse('2000', '密码不正确');
            } else {
                $new_password = splice_password($data['new_password'], $info['salt']);
                $updata_pass = Crud::setUpdate($table, $where, ['password' => $new_password, 'last_login_time' => time(), 'update_time' => time()]);
                if ($updata_pass) {
                    return jsonResponse('1000', '修改成功');
                }
            }
        }

    }
}