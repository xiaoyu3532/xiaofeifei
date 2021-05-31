<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:35
 */

namespace app\jg\controller\v2;

use app\lib\exception\AddMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\lib\exception\UserMemberMissException;
use app\validate\AdminUserIDMustBePostiveInt;
use app\validate\JGLoginupMustBePostiveInt;
use app\validate\JGUserAdminRegister;
use app\validate\LoginupMustBePostiveInt;
use app\common\model\Crud;
use app\validate\PhoneMustBePostiveInt;
use think\console\command\make\Controller;
use think\Request;
use think\Cache;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class LoginAccount extends Controller
{

    //机构账号注册 username账户 password密码 confirm_password确认密码 cname机构名称 logo
    // nickname负责人名称 phone机构联系电话 province省 city市 area区 address详情地址 wheel_img机构轮波图
    //organization组织编号（营业执照编号） mlicense 机构营业执照与其他证书 title简介   remarks备注
    public static function setjgLoginAccount()
    {
        $data = input();
        (new JGLoginupMustBePostiveInt())->goCheck();
        if ($data['password'] != $data['confirm_password']) {
            return jsonResponse('2000', '密码不一样');
        }
        $code = Cache::get($data['phone']);
        if ($data['code'] != $code) {
            return jsonResponse('2001', '验证码不正确');
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
                'organization' => $data['organization'], //组织编号（营业执照编号)
                'title' => $data['title'], //简介
                'address' => $data['address'], //详细地址
                'province' => $data['province'],
                'city' => $data['city'],
                'area' => $data['area'],
                'member_identifier' => time() . rand(10, 99),
            ];
            if ($data['logo']) {
                $data1['logo'] = $data['logo'];
            }
            if ($data['wheel_img']) { //机构轮波图
                $data1['wheel_img'] = handle_img_deposit($data['wheel_img']);
            }
            //地址标号
            if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
                $data1['province_num'] = $data['address_code'][0];
                $data1['city_num'] = $data['address_code'][1];
                $data1['area_num'] = $data['address_code'][2];
            }

            if ($data['mlicense']) { //机构营业执照与其他证书
                $data1['mlicense'] = handle_img_deposit($data['mlicense']);
            }

            if (isset($data['remarks']) && !empty($data['remarks'])) { //备注
                $data1['remarks'] = $data['remarks'];
            }

            if (isset($data['longitude']) && !empty($data['longitude'])) { //经度
                $data1['longitude'] = $data['longitude'];
            }

            if (isset($data['latitude']) && !empty($data['latitude'])) { //纬度
                $data1['latitude'] = $data['latitude'];
            }
            $table1 = 'member';
            $mem_id = Crud::setAdd($table1, $data1, 2);
            if ($mem_id) {
                $data2 = [
                    'mem_id' => $mem_id,
                    'phone' => $data['phone'],
                    'last_login_time' => time(),
                    'update_time' => time(),
                    'username' => $data['username'],
                    'password' => $password,
                    'salt' => $salt,
                    'type' => 2,//1用户，2机构
                    'token' => $token,
                    'role_id' => '1',//机构注册角色写死
                ];
                $res = Crud::setAdd($table, $data2, 2);
                //添加赠送名额
//                $give_num = Crud::setAdd('give_num', ['mid' => $mem_id]);
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
        $table = request()->controller();  //1用户，2机构，4综合体，5社区，6总平台，7管理人员
        $info = Crud::getData($table, $type = 1, $where, $field = 'type,user_id,admin_user_id,mem_id,salt,password,token,role_id');
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
//                    'type' => 2,
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
            //1用户，2机构，4综合体，5社区，6总平台，7管理人员
            if ($info['type'] == 2) {
                $member_data = Crud::getData('member', 1, ['uid' => $info['mem_id']], 'is_verification,cname');
                if ($member_data) {
                    $men_data = self::getjgPersonnelRole($info['role_id'], $type = 2, $account_type = 2);
                    $res_info = [
                        'token' => $info['token'],
                        'role_id' => $info['role_id'],
                        'cname' => $member_data['cname'],
                        'is_verification' => $member_data['is_verification'],
                        'men_data' => $men_data
                    ];
                    return jsonResponse('1000', '登录成功', $res_info);
                } else {
                    throw new UserMemberMissException();
                }
            } elseif ($info['type'] == 7) {
                //return jsonResponse('2000', '没有此机构或你没有绑定');
                $admin_user_data = Crud::getData('admin_user_role', 1, ['admin_user_id' => $info['admin_user_id'], 'is_del' => 1], 'admin_user_id,mem_id');
                if ($admin_user_data) {
                    $men_data = self::getjgbindingMember($admin_user_data);
                    if (!is_array($men_data)) {
                        return $men_data;
                    }
                    $member_data = Crud::getData('member', 1, ['uid' => $admin_user_data['mem_id'], 'is_del' => 1], 'cname');
                    $res_info = [
                        'token' => $info['token'],
                        'role_id' => $info['role_id'],
                        'cname' => $member_data['cname'],
                        'is_verification' => 1,
                        'men_data' => $men_data
                    ];
                    return jsonResponse('1000', '登录成功', $res_info);
                } else {
                    return jsonResponse('3000', '未绑定机构或用户使用账户到期');
                }
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
                $new_password = splice_password($data['confirm_password'], $info['salt']);
                $updata_pass = Crud::setUpdate($table, $where, ['password' => $new_password, 'last_login_time' => time(), 'update_time' => time()]);
                if ($updata_pass) {
                    return jsonResponse('1000', '修改成功');
                }
            }
        }

    }

    //获取本角色名已拥有值
    public static function getjgPersonnelRole($role_id, $type = 2, $account_type = 1)
    {
        //1用户，2机构,3机构人员 //yx_admin_jurisdiction
        $where = [
            'r.id' => $role_id,
            'r.is_del' => 1,
//            'r.type' => 3, //1为总平台，2为机构角色，3机构人员角色名
//            'r.start_time' => ['<', time()],
//            'r.end_time' => ['>', time()],
            'zj.is_del' => 1,
            'zm.is_del' => 1,
        ];
        if($type==2){
            $where['zm.pid'] = 0;
        }
        $join = [
            //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
            ['yx_zht_jurisdiction zj', 'zj.role_id = r.id', 'left'], //right
            ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'right'], //right
        ];
        $alias = 'r';
        $table = 'zht_role';
        $cname_data = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zm.sort asp', $field = 'zm.id,zm.pid,zm.name,zm.name_en,zm.router,zm.icon,zj.operation_type', 1, 1000000);
        if ($account_type == 2) {
            foreach ($cname_data as $k => $v) { //个人信息
                if ($v['name'] == '个人信息') {
                    unset($cname_data[$k]);
                }
            }
        }

        if ($cname_data) {
            if ($type == 1) {
                return $cname_data;
            } else {
                $array = [];
                $cname_data = Role::setRegroupheavy($cname_data,$account_type);
                foreach ($cname_data as $k => $v) {
                    $array[] = $v;
                }
                return $array;
//                    return jsonResponseSuccess($cname_data);
            }
        } else {
            return [];
        }


    }

    //获取员绑定的机构
    public static function getjgbindingMember($admin_user_id)
    {

        //1用户，2机构,3机构人员 //yx_admin_jurisdiction
        $where = [
            'aur.admin_user_id' => $admin_user_id['admin_user_id'],
            'aur.is_del' => 1,
            'm.is_del' => 1,
            'm.status' => 1,
        ];
        $join = [
            ['yx_member m', 'aur.mem_id = m.uid', 'left'], //right
        ];
        $alias = 'aur';
        $table = 'admin_user_role';
        $cname_data = Crud::getRelationData($table, 2, $where, $join, $alias, $order = '', $field = 'aur.*,m.cname', 1, 1000000);
        if ($cname_data) {
            if (count($cname_data) > 1) {  //type 1 绑定多机构，2绑定一个机构
                $mem_data = [
                    'mem_data' => $cname_data,
                    'type' => 1,
                ];
                return jsonResponseSuccess($mem_data);
            } else {
                $PersonnelRole = self::getjgPersonnelRole($cname_data[0]['role_id'], $type = 2, $account_type = 1);
                return $PersonnelRole;
            }
        } else {
            throw new NothingMissException();
        }
    }


    //人员管理登录（选机构后）
    public static function getjgbindingMemberLogin()
    {
        $data = input();
        (new AdminUserIDMustBePostiveInt())->goCheck();
        $where = [
            'aur.id' => $data['admin_user_id'],
            'aur.is_del' => 1,
            'au.is_del' => 1,
            'm.is_del' => 1,
            'm.status' => 1,
        ];
        $join = [
            ['admin_user au', 'aur.admin_user_id = au.id', 'left'], //right
            ['yx_login_account la', 'aur.admin_user_id = la.admin_user_id', 'left'], //right
            ['yx_member m', 'aur.mem_id = m.uid', 'left'], //right
        ];
        $alias = 'aur';
        $table = 'admin_user_role';
        $admin_user_data = Crud::getRelationData($table, 1, $where, $join, $alias, $order = '', $field = 'au.id,la.token,aur.role_id,m.cname');
        if ($admin_user_data) {
            $men_data = self::getjgPersonnelRole($admin_user_data['role_id'], $type = 2, $account_type = 1);
            if (!is_array($men_data)) {
                return $men_data;
            }
            $res_info = [
                'token' => $admin_user_data['token'],
                'role_id' => $admin_user_data['role_id'],
                'cname' => $admin_user_data['cname'],
                'is_verification' => 1,
                'men_data' => $men_data
            ];
            return jsonResponse('1000', '登录成功', $res_info);
        } else {
            return jsonResponse('3000', '未绑定机构或用户使用账户到期');
        }
    }


    //验证用户名是否被注册
    public static function isUserName($username, $type = 1)
    {

        $username_data = Crud::getData('login_account', 1, ['username' => $username], 'id,admin_user_id');
        if ($username_data) {
            if ($type == 1) {
                return jsonResponse('2000', '此用户已注册');
            } else {
                //有此用户
                return $username_data;
//                return 1;
            }
        } else {
            return 1;
        }
    }


    //用户注册  username 账号  password 密码 confirm_password 确认密码 real_member_name真实名称 sex id_card身份证 province省 city 市 area 区 address详情地址
    //qq we_chat微信号 email urgent_name 紧急联系人 urgent_phone 联系人电话 certificate_img 证书照片(资质证明) remarks 备注
    public static function setjgUserAdmin()
    {
        $data = input();
        (new JGUserAdminRegister())->goCheck();
        if ($data['password'] != $data['confirm_password']) {
            return jsonResponse('2000', '密码不一样');
        }
        $code = Cache::get($data['staff_phone']);
        if ($data['code'] != $code) {
            return jsonResponse('2001', '验证码不正确');
        }
        //验证用户名是否存在
        $isuserData = self::isUserName($data['username']);
        if ($isuserData != 1) {
            return $isuserData;
        }
        //组合员人信息
//        $addmin_user_add = [
//            'real_member_name' => $data['real_member_name'],
//            'sex' => $data['sex'],
//            'id_card' => $data['id_card'],
//            'province' => $data['province'],
//            'city' => $data['city'],
//            'area' => $data['area'],
//            'address' => $data['address'],
//            'urgent_name' => $data['urgent_name'],
//            'urgent_phone' => $data['urgent_phone'],
//            'remarks' => $data['remarks'],
//            'staff_phone' => $data['staff_phone'],
//        ];
        //地址标号
        if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
            $data['province_num'] = $data['address_code'][0];
            $data['city_num'] = $data['address_code'][1];
            $data['area_num'] = $data['address_code'][2];
        }
        if ($data['certificate_img']) { //资质证明
            $data['certificate_img'] = handle_img_deposit($data['certificate_img']);
        }
        $data['staff_identifier'] = time() . rand(10, 99);
        $admin_user_id = Crud::setAdd('admin_user', $data, 2);
        if (!$admin_user_id) {
            throw new AddMissException();
        }

        //添加关联表
        $salt = get_rand_char(4);
        $password = splice_password($data['password'], $salt);
        $token = md5(time() . rand(111111, 999999));
        $account_add = [
            'username' => $data['username'],
            'phone' => $data['staff_phone'],
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

    //获取验证码
    public static function getPhoneCode()
    {
        $data = input();
        (new PhoneMustBePostiveInt())->goCheck();
//        (new JGLoginupMustBePostiveInt())->goCheck();
        $str = '1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr, 0, 4);//substr(string,start,length);返回字符串的一部分
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($data['phone'], $content);
        if (!empty($response)) {
            Cache::set($data['phone'], $code, 900);
            return jsonResponse('1000', $response, '验证码发送成功');
        }
    }

    //获取用户账号
    public static function getUsername()
    {
        $data = input();
        $table = request()->controller();
        (new PhoneMustBePostiveInt())->goCheck();
//        $code = Cache::get($data['phone']);
//        if ($data['code'] != $code) {
//            return jsonResponse('2001', '验证码不正确');
//        }
        $where = [
            'phone' => $data['phone']
        ];
        $username_data = Crud::getData($table, 2, $where, 'username');
        if ($username_data) {
            return jsonResponseSuccess($username_data);
        }
    }

    //修改密码 username 账号  password 密码 confirm_password 确认密码
    public static function editPassword()
    {
        $data = input();
        (new PhoneMustBePostiveInt())->goCheck();
        $code = Cache::get($data['phone']);
        if ($data['code'] != $code) {
            return jsonResponse('2001', '验证码不正确');
        }
        if($data['new_password'] !=$data['confirm_password']){
            return jsonResponse('3000','密码不一致');
        }

        $salt = get_rand_char(4);
        $password = splice_password($data['new_password'], $salt);
        $token = md5(time() . rand(111111, 999999));
        $where =[
            'username' => $data['username'],
            'phone' => $data['phone'],
            'is_del'=>1
        ];
        $account_add = [
            'phone' => $data['phone'],
            'salt' => $salt,
            'password' => $password,
            'token' => $token,
        ];
        $login_account = Crud::setUpdate('login_account', $where,$account_add);
        if ($login_account){
            return  jsonResponseSuccess($login_account);
        }else{
            throw new UpdateMissException();
        }
    }
}