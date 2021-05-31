<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:35
 */

namespace app\jg\controller\v1;

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

    //机构账号注册
    public static function setjgLoginAccount()
    {
        $data = input();
        (new JGLoginupMustBePostiveInt())->goCheck();
        if ($data['password'] != $data['tow_password']) {
            return jsonResponse('2000', '密码不一样');
        }
        //查看在用户登录关联表中
        $where = [
            'username' => $data['username'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'mem_id');
        if (!$info) {
            $salt = get_rand_char(4);
            $password = splice_password($data['password'], $salt);
            $token = md5(time() . rand(111111, 999999));
            $data1 = [
                'cname' => $data['cname'], //机构名称
                'nickname' => $data['nickname'], //联系人
                'phone' => $data['phone'], //联系方式
            ];
            $table1 = 'member';
            $mem_id = Crud::setAdd($table1, $data1, 2);
            if ($mem_id) {
                $data2 = [
                    'mem_id' => $mem_id,
                    'last_login_time' => time(),
                    'update_time' => time(),
                    'username' => $data['username'],
                    'password' => $password,
                    'salt' => $salt,
                    'type' => 2,//1用户，2机构
                    'token' => $token,
                ];
                $res = Crud::setAdd($table, $data2, 2);
                //添加赠送名额
                $give_num = Crud::setAdd('give_num', ['mid' => $mem_id]);
                if ($res) {
                    //添加财务密码
                    $finance_data = [
                        'mem_id' => $mem_id,
                        'password' => $password,
                        'salt' => $salt,
                    ];
                    $finance_info = Crud::setAdd('login_finance', $finance_data);
                    return jsonResponse('1000', '注册成功');
                }
            }
        } else {
            return jsonResponse('2000', '此账号已被使用');
        }

    }

    //用户账号登录
    public static function getjgLoginAccount()
    {
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
                    'type' => 2,
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
    public static function editjgLoginAccount()
    {
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