<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27 0027
 * Time: 19:16
 */

namespace app\jg\controller\v2;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');


class StaffRegister
{
    //用户注册  username 账号  password 密码 confirm_password 确认密码 real_member_name真实名称 sex id_card身份证 province省 city 市 area 区 address详情地址
    //qq we_chat微信号 email urgent_name 紧急联系人 urgent_phone 联系人电话 certificate_img 证书照片(资质证明) remarks 备注
    public static function setjgStaffRegister()
    {
        $data = input();
        if ($data['password'] != $data['confirm_password']) {
            return jsonResponse('2000', '密码不一样');
        }
//        验证用户名是否存在
        $isuserData = self::isUserName($data['username']);
        if ($isuserData != 1) {
            return $isuserData;
        }
//        组合员人信息
        $addmin_user_add = [
            'real_member_name' => $data['real_member_name'],
            'sex' => $data['sex'],
            'id_card' => $data['id_card'],
            'province' => $data['province'],
            'city' => $data['city'],
            'area' => $data['area'],
            'address' => $data['address'],
            'qq' => $data['qq'],
            'we_chat' => $data['we_chat'],
            'email' => $data['email'],
            'urgent_name' => $data['urgent_name'],
            'urgent_phone' => $data['urgent_phone'],
            'remarks' => $data['remarks'],
        ];
//        地址标号
        if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
            $addmin_user_add['province_num'] = $data['address_code'][0];
            $addmin_user_add['city_num'] = $data['address_code'][1];
            $addmin_user_add['area_num'] = $data['address_code'][2];
        }
        if ($data['certificate_img']) { //资质证明
            $addmin_user_add['certificate_img'] = handle_img_deposit($data['certificate_img']);
        }
        $admin_user_id = Crud::setAdd('admin_user', $addmin_user_add, 2);
        if (!$admin_user_id) {
            throw new AddMissException();
        }

//        添加关联表
        $salt = get_rand_char(4);
        $password = splice_password($data['password'], $salt);
        $token = md5(time() . rand(111111, 999999));
        $account_add = [
            'username' => $data['username'],
            'admin_user_id' => $admin_user_id,
            'salt' => $salt,
            'password' => $password,
            'token' => $token,
            'type' => 7
        ];
        $login_account = Crud::setAdd('login_account', $account_add);
        if (!$login_account) {
            throw new AddMissException();
        } else {
            return jsonResponseSuccess($login_account);
        }
    }

}