<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:35
 */

namespace app\pc\controller\v1;

use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UserMemberMissException;
use app\validate\CodeMustBePostiveInt;
use app\validate\LoginupMustBePostiveInt;
use app\validate\PhoneMustBePostiveInt;
use think\Cache;
use app\common\model\Crud;
use think\Request;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class LoginAccount
{
    //用户账号登录
    public static function getLoginAccount()
    {
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();
        if (isset($data['username']) && !empty($data['username'])) {
            $where = [
                'username' => $data['username'],
            ];
        }
        if (isset($data['phone']) && !empty($data['phone'])) {
            $where = [
                'phone' => $data['phone'],
            ];
        }
        $table = request()->controller();
        //type 1用户，2机构，3综合体
        $info = Crud::getData($table, $type = 1, $where, $field = 'type,user_id,mem_id,salt,password,token');
        if (!$info) {
            throw new UserMemberMissException();
        } else {
            $password = splice_password($data['password'], $info['salt']);
            if ($password != $info['password']) {
                return jsonResponse('2000', '密码不正确');
            }
            //更新时间
            $where2 = [
                'username' => $data['username']
            ];
            Crud::setUpdate($table, $where2, ['last_login_time' => time()]);
            if ($info['type'] == 1) {
                $where1 = [
                    'user_id' => $info['user_id'],
                    'type' => 1,
                    'is_del' => 1,
                ];
                //判断此用户是否绑定过机构
                $table1 = 'user';
                $user_bindingtype = Crud::getData($table1, $type = 1, $where1, $field = 'bindingtype');
                if ($user_bindingtype['bindingtype'] == 1) {
                    //获取用户绑定机构
                    $table2 = 'user_member';
                    $where2 = [
                        'um.uid' => $info['user_id'],
                        'um.is_del' => 1,
                    ];
                    $join = [
                        ['yx_member m', 'um.mid = m.uid', 'left'],
                    ];
                    $alias = 'um';
                    $bindMiduser = Crud::getRelationData($table2, $type = 2, $where2, $join, $alias, $order = '', $field = 'um.mid,m.cname', 1, 10000);
                    if ($bindMiduser) {
                        //获取mid机构token
                        $res_info = [];
                        foreach ($bindMiduser as $k => $v) {
                            $where3 = [
                                'mem_id' => $v['mid'],
                                'is_del' => 1,
                            ];
                            $token_info = Crud::getData($table, $type = 2, $where3, $field = 'token');
                            if ($token_info) {
                                $res_info[] = [
                                    'token' => $token_info['token'],
                                    'cname' => $v['cname'],
                                ];
                            }
                        }
                        if ($bindMiduser) {
                            return jsonResponse('1000', '登录成功', $res_info);
                        }
                    } else {
                        throw new NothingMissException();
                    }
                } else {
                    throw new NothingMissException();
                }
            } else {
                $where4 = [
                    'uid' => $info['mem_id'],
                    'is_del' => 1,
                    'status' => 1,
                ];
                $table3 = '';
                $member_cname = Crud::getData($table3, $type = 1, $where4, $field = 'cname');
                $res_info[] = [
                    'token' => $info['token'],
                    'cname' => $member_cname['cname'],
                ];
            }
            return jsonResponse('1000', '登录成功', $res_info);
        }

    }

    //修改密码
    public static function editLoginAccount()
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

    //用户账号注册
    public static function setLoginAccount()
    {
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();
        //查看在用户登录关联表中
        $where = [
            'username' => $data['username'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'user_id');
        if (!$info) {
            $salt = get_rand_char(4);
            $password = splice_password($data['password'], $salt);
            $token = md5(time() . rand(111111, 999999));
            $data1 = [
                'salt' => $salt,
                'password' => $password,
                'token' => $token,
            ];

            $table1 = 'user';
            $user_id = Crud::setAdd($table1, $data1, 2);
            if ($user_id) {
                $data2 = [
                    'user_id' => $user_id,
                    'last_login_time' => time(),
                    'update_time' => time(),
                    'username' => $data['username']
                ];
                $user_id = Crud::setAdd($table, $data2);
                if ($user_id) {
//                    $res = [
//                        'user_id' => $user_id,
//                        'token' => $token,
//                    ];
                    return jsonResponse('1000', '注册成功');
                }
            }
        } else {
            return jsonResponse('2000', '此账号已被使用');
        }

    }

    //用户登录手机号登录
    public static function getLoginAccountPhone()
    {
        $data = input();
        (new CodeMustBePostiveInt())->goCheck();
        (new PhoneMustBePostiveInt())->goCheck();
        $code = Cache::get('phone');
        $code = '1234';
        if ($data['code'] != $code) {
            return jsonResponse('2001', '验证码不正确');
        }
        //查看在用户登录关联表中
        $where = [
            'phone' => $data['phone']
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'user_id');
        if (!$info) {
            //系统此用户，进行注册
            $token = md5(time() . rand(111111, 999999));
            $data1 = [
                'token' => $token,
                'phone' => $data['phone']
            ];
            $table1 = 'user';
            $user_id = Crud::setAdd($table1, $data1, 2);
            if ($user_id) {
                $data2 = [
                    'user_id' => $user_id,
                    'last_login_time' => time(),
                    'phone' => $data['phone']
                ];
                $user_id = Crud::setAdd($table, $data2);
                if ($user_id) {
                    $res = [
                        'user_id' => $user_id,
                        'token' => $token,
                    ];
                    return jsonResponse('1000', '获取成功', $res);
                }
            }
        } else {
            $where1 = [
                'is_del' => 1,
                'type' => 1,
                'phone' => $data['phone']
            ];
            $table1 = 'user';
            $res = Crud::getData($table1, $type = 1, $where1, $field = 'id user_id,token');
            if ($res) {
                //更新时间
                $where2 = [
                    'phone' => $data['phone']
                ];
                Crud::setUpdate($table, $where2, ['last_login_time' => time()]);
                return jsonResponse('1000', '获取成功', $res);
            } else {
                return jsonResponse('2000', '此用户信息有误请联系管理员');
            }
        }

    }

}