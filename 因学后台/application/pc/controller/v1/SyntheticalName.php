<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/18 0018
 * Time: 10:20
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\validate\LoginupMustBePostiveInt;

class SyntheticalName extends BaseController
{
    //获取综合体列表
    public static function getpcSyntheticalName($page = '1', $name = '')
    {
        $where = [
            's.is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['s.name'] = ['like', '%' . $name . '%'];
        $table = request()->controller();
//        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page);

        $join = [
            ['yx_login_account la', 's.mem_id =la.mem_id ', 'left'],
        ];
        $alias = 's';
        $table = request()->controller();
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 's.create_time desc', $field = 's.id,s.name,s.kf_phone,la.username', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*');;
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //添加综合体列表
    public static function addpcSyntheticalName()
    {
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();
        $where = [
            'username' => $data['username'],
        ];
        $table = 'login_account';
        $info = Crud::getData($table, $type = 1, $where, $field = 'mem_id');
        if (!$info) {
            $salt = get_rand_char(4);
            $password = splice_password($data['password'], $salt);
            $token = md5(time() . rand(111111, 999999));
            $data1 = [
                'cname' => $data['name'], //机构名称
                'type' => 4, //成长中心
                'is_verification' => 1, //直接成功
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
                    'type' => 4,//1用户，2机构 ,成长中心
                    'token' => $token,
                ];
                $res = Crud::setAdd($table, $data2, 2);
                if (!$res) {
                    throw new AddMissException();
                }
                $data3 = [
                    'name' => $data['name'],
                    'kf_phone' => $data['phone'],
                    'mem_id' => $mem_id,
                ];
                $table2 = request()->controller();
                $info = Crud::setAdd($table2, $data3);
                if ($info) {
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

    //修改综合体列表
    public static function setpcSyntheticalName()
    {
        $data = input();
        $where = [
            'id' => $data['synthetical_id']
        ];
        $table = request()->controller();
        $synthetical_data = Crud::getData($table, 1, $where, '*');
        if (!$synthetical_data) {
            throw  new  NothingMissException();
        }

        if (isset($data['username']) && !empty($data['username'])) {
            $where1 = [
                'mem_id' => $synthetical_data['mem_id'],
                'username' => $data['username'],
            ];
            $login_account_data = Crud::getData('login_account', 1, $where1, 'id');
            if (!$login_account_data) {
                //验证这个账号是否被其他占用
                $login_account_data = Crud::getData('login_account', 1, ['username' => $data['username']], 'id');
                if ($login_account_data) {
                    return jsonResponse('2000', '此账号已被占用');
                } else {
                    $username_update = Crud::setUpdate('login_account', ['mem_id' => $synthetical_data['mem_id']], ['username' => $data['username']]);
                    if (!$username_update) {
                        throw new UpdateMissException();
                    }
                }
            }

            unset($data['username']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $login_account_data = Crud::getData('login_account', 1, ['mem_id' => $synthetical_data['mem_id']], '*');
            if (!$login_account_data) {
                throw new NothingMissException();
            }
            $password = splice_password($data['password'], $login_account_data['salt']);
            $where1 = [
                'mem_id' => $synthetical_data['mem_id'],
                'password' => $password,
            ];
            $login_account_data = Crud::getData('login_account', 1, $where1, 'id');
            if (!$login_account_data) {
                $salt = get_rand_char(4);
                $password = splice_password($data['password'], $salt);
                $token = md5(time() . rand(111111, 999999));
                $data2 = [
                    'update_time' => time(),
                    'password' => $password,
                    'salt' => $salt,
                    'token' => $token,
                ];
                $password_update = Crud::setUpdate('login_account', ['mem_id' => $synthetical_data['mem_id']], $data2);
                if (!$password_update) {
                    throw new UpdateMissException();
                }
            }
            unset($data['password']);
        }
        unset($data['synthetical_id']);
        $data['kf_phone'] = $data['phone'];
        $data['update_time'] = time();
        if(isset($data['name'])&&!empty($data['name'])){
            $member_data = [
                'cname'=>$data['name'],
                'update_time'=>time(),
                'kf_phone'=>$data['kf_phone']
            ];
            //更改机构表
            $member_update = Crud::setUpdate('member', ['uid'=>$synthetical_data['mem_id']], $member_data);
            if(!$member_update){
                throw new UpdateMissException();
            }
        }
        unset($data['phone']);
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除综合体列表
    public static function delpcSyntheticalName()
    {
        $data = input();
        $where = [
            'id' => $data['synthetical_id']
        ];

        $table = request()->controller();
        $synthetical_data = Crud::getData($table, 1, $where, '*');
        if (!$synthetical_data) {
            throw  new  NothingMissException();
        }


        $data = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //获取综合体列表
    public static function getpcSyntheticalNameType()
    {
        $where = [
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name', $order = 'sort desc', 1, 1000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //获取综合体下面分类的教室
    public static function getpcSyntheticalTypesearch()
    {
        $where = [
//            'type' => 1,
            'is_del' => 1,
        ];
        $table = request()->controller();
        $syntheticalName = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
        if ($syntheticalName) {
            $table1 = 'classroom_type';
            $table2 = 'synthetical_classroom';
            foreach ($syntheticalName as $k => $v) {
                $where1 = [
                    'is_del' => 1,
                    'type' => 1
                ];
                $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id value,name label', $order = 'sort desc', 1, 10000);
                $syntheticalName[$k]['children'] = $type_name_list;
                foreach ($type_name_list as $kk => $vv) {
                    $where2 = [
                        'is_del' => 1,
                        'type_id' => $vv['value'],
                    ];
                    $syntheticalName_data = Crud::getData($table2, $type = 2, $where2, $field = 'id value,name label', $order = '', 1, 10000);
                    $syntheticalName[$k]['children'][$kk]['children'] = $syntheticalName_data;
                }
            }
            if ($syntheticalName) {
                return jsonResponseSuccess($syntheticalName);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new NothingMissException();
        }

    }


}