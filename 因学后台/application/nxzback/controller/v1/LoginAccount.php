<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:35
 */

namespace app\nxzback\controller\v1;

use app\lib\exception\UserMemberMissException;
use app\validate\LoginupMustBePostiveInt;
use app\common\model\Crud;
use think\Cache;
use think\Db;
use app\validate\PhoneMustBePostiveInt;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class LoginAccount
{
    //机构注册
    public static function setregister()
    {
        $data = input();
        //验证码判断
        $code1 = str_replace(" ", '', $data['code']);
        $code = Cache::get($data['phone']);
        if ($code1 != 3536) { //测试验证码 3536
            if ($code != $code1) {
                return jsonResponse('1003', '验证码错误');
            }
        }
        unset($data['code']);

        //验证手机号
        $where = [
            'is_del' => 1,
            'phone' => $data['phone']
        ];
        $table = request()->controller();
        $phone_data = Crud::getData($table, 1, $where, $field = 'id');
        if ($phone_data) {
            return jsonResponse('1001', '手机号已注册');
        }
        //验证机构是否入驻
        $where1 = [
            'is_del' => 1,
            'cname' => $data['cname'],
            'status' => 1, //1禁用，2启用
        ];
        $mem_data_id = Crud::getData('member', 1, $where1, $field = 'uid');
        if ($mem_data_id) {
            return jsonResponse('1002', '本机构已注册');
        }

        $salt = get_rand_char(4);
        //生成加密的密码
        $password = splice_password($data['password'], $salt);
        unset($data['password']);
        $token = md5(time() . rand(111111, 999999));

        //添加机构
        $data['user_type'] = 3; //1客户添加机构，2为后台添加，3逆行者活动添加
        $data['kf_phone'] = $data['phone']; //客服电话
        Db::startTrans();
        $mem_id = Crud::setAdd('member', $data, 2);
        if (!$mem_id) {
            Db::rollback();
            return jsonResponse('2000', '机构注册失败', 2001);
        }

        //添加登录账号
        $login_data = [
            'mem_id' => $mem_id,
            'username' => $data['cname'],
            'password' => $password,
            'phone' => $data['phone'],
            'salt' => $salt,
            'type' => 2, //1用户，2机构，4综合体，5社区，6总平台
            'token' => $token,
        ];
        $login_id = Crud::setAdd($table, $login_data, 2);
        if (!$login_id) {
            Db::rollback();
            return jsonResponse('2000', '机构注册失败', 2002);
        }
        //添加赠送名额
        $give_num = Crud::setAdd('give_num', ['mid' => $mem_id]);
        if (!$give_num) {
            Db::rollback();
            return jsonResponse('2000', '机构注册失败', 2002);
        }
        Db::commit();
        return jsonResponseSuccess($mem_id);

    }

    //用户账号登录editczzxLoginAccount
    public static function getLoginAccount()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
        $data = input();
        (new LoginupMustBePostiveInt())->goCheck();


        //验证帐户是手机号，还是机构名称
        if (is_mobile($data['username'])) {
            $where = [
                'phone' => $data['username'],
            ];
            $where2 = [
                'phone' => $data['username']
            ];
            $where3 = [
                'phone' => $data['username'],
                'type' => 2,
            ];

        } else {
            $where = [
                'username' => $data['username'],
            ];
            $where2 = [
                'username' => $data['username']
            ];
            $where3 = [
                'username' => $data['username'],
                'type' => 2,
            ];
        }


        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'type,user_id,mem_id,salt,password,token');
        if (!$info) {
            throw new UserMemberMissException();
        }

        $password = splice_password($data['password'], $info['salt']);
        if ($password != $info['password']) {
            return jsonResponse('2000', '密码不正确');
        }
        $where['password'] = $password;
        $info_data = Crud::getData($table, $type = 1, $where3, $field = 'id');
        if (!$info_data) {
            throw new UserMemberMissException();
        }

        //更新时间
        Crud::setUpdate($table, $where2, ['last_login_time' => time()]);
        $member_data = Crud::getData('member', 1, ['uid' => $info['mem_id']], 'is_verification');
        if ($member_data) {
            $res_info = [
                'mem_id'=>$info['mem_id'],
                'token' => $info['token'],
                'is_verification' => $member_data['is_verification'],
            ];
            return jsonResponse('1000', '登录成功', $res_info);
        } else {
            throw new UserMemberMissException();
        }
    }

    //修改密码
    public static function edLoginAccount()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
//        $token = Request::instance()->header('Authorization');
//        if (empty($token)) {
//            throw new ISUserMissException();
//        }
        $data = input();
        $code1 = str_replace(" ", '', $data['code']);
        $code = Cache::get($data['phone']);
        if ($code1 != 3536) { //测试验证码 3536
            if ($code != $code1) {
                return jsonResponse('1001', '验证码错误');
            }
        }
        if ($data['password'] != $data['new_password']) {
            return jsonResponse('1002', '密码不一致');
        }
        //查看在用户登录关联表中
        $where = [
            'phone' => $data['phone'],
            'is_del' => 1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,salt');
        if (!$info) {
            throw new UserMemberMissException();
        } else {
            $new_password = splice_password($data['new_password'], $info['salt']);
            $updata_pass = Crud::setUpdate($table, $where, ['password' => $new_password, 'last_login_time' => time(), 'update_time' => time()]);
            if ($updata_pass) {
                return jsonResponse('1000', '修改成功');
            }

        }

    }

    public static function getPhoneCode($phone) {
        (new PhoneMustBePostiveInt())->goCheck();
        $str = '1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr, 0, 4);//substr(string,start,length);返回字符串的一部分
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($phone, $content);
        if(!empty($response)){
            Cache::set($phone,$code,900);
            return jsonResponse('1000',$response,'验证码发送成功');
        }
    }


}