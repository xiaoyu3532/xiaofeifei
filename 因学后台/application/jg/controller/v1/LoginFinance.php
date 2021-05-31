<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 16:21
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\UserMemberMissException;

class LoginFinance extends BaseController
{

    //修改账务密码
    public static function editjgLoginFinance()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] != 2) {
            throw new ISUserMissException();
        }
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