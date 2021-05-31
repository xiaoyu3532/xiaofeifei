<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:35
 */

namespace app\czzx\controller\v1;

use app\lib\exception\ISUserMissException;
use app\lib\exception\UserMemberMissException;
use app\validate\JGLoginupMustBePostiveInt;
use app\validate\LoginupMustBePostiveInt;
use app\common\model\Crud;
use think\Request;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class LoginAccount
{

    //用户账号登录editczzxLoginAccount
    public static function getczzxLoginAccount()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();
        $where = [
            'username' => $data['username'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'type,user_id,mem_id,salt,password,token');
        if (!$info) {
            throw new UserMemberMissException();
        } else {
            $password = splice_password($data['password'], $info['salt']);
            if ($password != $info['password']) {
                return jsonResponse('2000', '密码不正确');
            } else {
                $where3 = [
                    'username' => $data['username'],
                    'password' => $password,
                    'type' => 4,
                ];
                $info_data = Crud::getData($table, $type = 1, $where3, $field = 'id');
                if (!$info_data) {
                    throw new UserMemberMissException();
                }
            }
            //更新时间
            $where2 = [
                'username' => $data['username']
            ];
            Crud::setUpdate($table, $where2, ['last_login_time' => time()]);
            $member_data = Crud::getData('member', 1, ['uid' => $info['mem_id']], 'is_verification');
            if ($member_data) {
                $res_info = [
                    'token' => $info['token'],
                    'is_verification' => $member_data['is_verification'],
                ];
                return jsonResponse('1000', '登录成功', $res_info);
            } else {
                throw new UserMemberMissException();
            }
        }

    }

    //修改密码
    public static function editczzxLoginAccount()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

        $token = Request::instance()->header('Authorization');
        if (empty($token)) {
            throw new ISUserMissException();
        }
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();
        //查看在用户登录关联表中
        $where = [
            'token' => $token,
            'is_del' => 1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'type,user_id,mem_id,salt,password,token');;
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