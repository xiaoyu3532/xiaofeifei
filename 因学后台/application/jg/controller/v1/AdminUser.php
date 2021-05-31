<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/9 0009
 * Time: 15:54
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\jg\controller\v2\LoginAccount as LoginAccount;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Db;

class AdminUser extends BaseController
{
    //获取管理员列表
    public static function getAdminUserList($page = 1, $real_member_name = '', $mem_id = '', $role_id = '', $pageSize = 8)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'a.is_del' => 1,
                'm.is_del' => 1,
                'aur.is_del' => 1,
                'm.status' => 1,
                'aur.mem_id' => $account_data['mem_id'],
            ];
        }
        (isset($real_member_name) && !empty($real_member_name)) && $where['a.real_member_name'] = ['like', '%' . $real_member_name . '%'];//管理员名称
        (isset($real_member_name) && !empty($real_member_name)) && $where['a.staff_phone'] = ['like', '%' . $real_member_name . '%'];//管理员手机号
        (isset($cname) && !empty($cname)) && $where['m.uid'] = ['like', '%' . $mem_id . '%'];//机构名称
        (isset($role_id) && !empty($role_id)) && $where['a.role_id'] = $role_id;//权限名
        $join = [
            ['yx_member m', 'aur.mem_id = m.uid', 'left'], //right
            ['yx_admin_user a', 'aur.admin_user_id = a.id', 'left'], //right
            ['yx_teacher t', 'a.teacher_id = t.id', 'left'], //right
//            ['yx_admin_user_role aur', 'a.id = aur.admin_user_id', 'left'], //right
            ['yx_zht_role zr', 'aur.role_id = zr.id', 'left'], //right
            ['yx_login_account la', 'a.id = la.admin_user_id', 'left'], //right
        ];
        $alias = 'aur';
        $table = 'admin_user_role';
        $admin_user_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'aur.create_time desc', $field = 'a.id,a.real_member_name,a.mem_id,a.staff_identifier,a.staff_phone,a.id_card,a.sex,a.urgent_name,a.urgent_phone,a.email,a.qq,a.we_chat,a.province,a.city,a.area,a.address,a.role_name,a.role_id,a.teacher_id,a.certificate_img,a.remarks,a.contract_img,a.contract_start_time,a.contract_end_time,a.contract_type,a.create_time,m.cname,m.province,m.city,m.area,m.address,t.teacher_nickname relation_teacher_name,zr.role_name role_name_exhibition,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,la.username,aur.id admin_user_role_id', $page, $pageSize);
        if ($admin_user_data) {
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'aur.id');
            foreach ($admin_user_data as $k => $v) {

                $admin_user_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                $admin_user_data[$k]['user_address'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                if ($v['sex'] == 1) {
                    $admin_user_data[$k]['sex_name'] = '男';
                } else if ($v['sex'] == 2) {
                    $admin_user_data[$k]['sex_name'] = '女';
                }
                if (isset($v['contract_img']) && !empty($v['contract_img'])) { //合同照片
                    $admin_user_data[$k]['contract_img'] = handle_img_take($v['contract_img']);
                } else {
                    $admin_user_data[$k]['contract_img'] = [];
                }
                if (isset($v['certificate_img']) && !empty($v['certificate_img'])) { //证书
                    $admin_user_data[$k]['certificate_img'] = handle_img_take($v['certificate_img']);
                } else {
                    $admin_user_data[$k]['certificate_img'] = [];
                }
                $admin_user_data[$k]['contract_time'] = date('Y-m-d H:i:s', $v['contract_start_time']) . '至' . date('Y-m-d H:i:s', $v['contract_end_time']);
                $admin_user_data[$k]['create_time_Exhibition'] = conversion_time($v['create_time']);
                $admin_user_data[$k]['contract_time'] = [
                    [$v['contract_start_time'] * 1000],
                    [$v['contract_end_time'] * 1000],
                ];
            }
            $info_data = [
                'info' => $admin_user_data,
                'num' => $num,
                'pageSize' => 8,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取机构人员列表字段
    public static function getjgAdminUserListField()
    {
        $data = [
            ['prop' => 'real_member_name', 'name' => '人员名称', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'staff_phone', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'id_card', 'name' => '身份证', 'width' => '', 'state' => ''],
            ['prop' => 'create_time_Exhibition', 'name' => '添加时间', 'width' => '100', 'state' => ''],
            ['prop' => 'role_name_exhibition', 'name' => '权限', 'width' => '100', 'state' => ''], //role_name_exhibition
            ['prop' => 'role_name', 'name' => '人员角色', 'width' => '', 'state' => ''], //role_name
            ['prop' => 'relation_teacher_name', 'name' => '关联老师', 'width' => '320', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //添加管理员 role_name 员工角色  role_id 关联权限 username 管理账号  teacher_id 绑定老师ID contract_img 合同照片 contract_start_time 合同开始时间
    //contract_end_time 合同结束时间 remarks备注 contract_type 合同类型
    public static function addAdminUser()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            if (isset($data['mem_id']) && !empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }

            if (isset($data['contract_time']) && !empty($data['contract_time']) && is_array($data['contract_time'])) {
                $data['contract_start_time'] = $data['contract_time'][0] / 1000;
                $data['contract_end_time'] = $data['contract_time'][1] / 1000;
            } else {
                $data['contract_start_time'] = time();
                $data['contract_end_time'] = strtotime('2030-1-1');
            }
            if (isset($data['contract_img']) && !empty($data['contract_img'])) { //合同图片
                $data['contract_img'] = handle_img_deposit($data['contract_img']);
            }

            //验证员工是否存在
            $admin_user = LoginAccount::isUserName($data['username'], 2);
            if ($admin_user == 1) {
                return jsonResponse('3000', '员工用户有误请重新输入');
            }
            if (isset($data['role_id']) && !empty($data['role_id'])) {
                //将所有的角色删除
                $del_user_role = Crud::setUpdate('admin_user_role', ['admin_user_id' => $admin_user['admin_user_id'], 'mem_id' => $data['mem_id']], ['is_del' => 2]);
                $role_data = [
                    'admin_user_id' => $admin_user['admin_user_id'],
                    'role_id' => $data['role_id'],
                    'mem_id' => $data['mem_id'],
                ];
                $admin_user_role = Crud::setAdd('admin_user_role', $role_data);
                if (!$admin_user_role) {
                    throw  new AddMissException();
                }

            }
//            $data['updata_time'] = time();
            $where = [
                'id' => $admin_user['admin_user_id'],
            ];
            //添加管员人员
            $table = request()->controller();
            $data['update_time'] = time();
            unset($data['mem_id']);
            $AdminUser = Crud::setUpdate($table, $where, $data);
            //将用户绑定到机构
            if ($AdminUser) {
                return jsonResponseSuccess($AdminUser);
            } else {
                throw new UpdateMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //修改管理员
    public static function setAdminUser()
    {
        $data = input();
        $table = request()->controller();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            $admin_user_id = $data['admin_user_id'];
            unset($data['admin_user_id']);
            $where = [
                'id' => $admin_user_id,
            ];

            if (isset($data['contract_img']) && !empty($data['contract_img'])) { //合同图片
                $data['contract_img'] = handle_img_deposit($data['contract_img']);
            }

            if (isset($data['certificate_img']) && !empty($data['certificate_img'])) { //合同图片
                $data['certificate_img'] = handle_img_deposit($data['certificate_img']);
            }

            if (isset($data['contract_time']) && !empty($data['contract_time']) && is_array($data['contract_time'])) {
                $data['contract_start_time'] = $data['contract_time'][0][0] / 1000;
                $data['contract_end_time'] = $data['contract_time'][1][0] / 1000;
            }

            //验证员工是否存在
            $admin_user = LoginAccount::isUserName($data['username'], 2);
//            if ($admin_user != 1) {
            if (!is_array($admin_user)) {
                return jsonResponse('3000', '员工用户有误请重新输入');
            }
            if (isset($data['role_id']) && !empty($data['role_id'])) {
                //将所有的角色删除
                $del_user_role = Crud::setUpdate('admin_user_role', ['id' => $admin_user_id, 'mem_id' => $data['mem_id']], ['is_del' => 2]);
                if (!is_array($data['role_id']) && empty($data['role_id'])) {
                    throw new  AddMissException();
                }
                $role_data = [
                    'admin_user_id' => $admin_user_id,
                    'role_id' => $data['role_id'],
                    'mem_id' => $data['mem_id'],
                ];
                $admin_user_role = Crud::setAdd('admin_user_role', $role_data);
                if (!$admin_user_role) {
                    throw  new AddMissException();
                }
            }
            //将用户绑定到机构
            $account_update = Crud::setUpdate('login_account', ['id' => $admin_user['id']], ['mem_id' => $data['mem_id'], 'role_id' => $data['role_id'], 'update_time' => time()]);
            if (!$account_update) {
                throw new UpdateMissException();
            }

            $data['updata_time'] = time();

            //添加管员人员
            $AdminUser = Crud::setUpdate($table, $where, $data);
            if ($AdminUser) {
                return jsonResponseSuccess($AdminUser);
            } else {
                throw new UpdateMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取管理员详情
    public static function getAdminUserDetails()
    {
        $account_data = self::isuserData();

        $where = [
            'ad.id' => $account_data['admin_user_id'],
            'aur.mem_id' => $account_data['mem_id'],
            'ad.is_del' => 1,
            'm.is_del' => 1,
            'm.status' => 1,
            'zr.is_del' => 1,
        ];


        $joinm = [
            ['yx_admin_user_role aur', 'ad.id = aur.admin_user_id', 'left'], //right
            ['yx_member m', 'aur.mem_id = m.uid', 'left'], //right
            ['yx_teacher t', 'ad.teacher_id = t.id', 'left'], //right
            ['yx_zht_role zr', 'ad.role_id = zr.id', 'left'], //right

        ];
        $aliasm = 'ad';
        $table = request()->controller();
        $admin_user_data = Crud::getRelationData($table, 1, $where, $joinm, $aliasm, $order = '', $field = 'ad.*,m.cname,m.province,m.city,m.area,m.address,zr.role_name,t.teacher_nickname');
        if ($admin_user_data) {
            //求权限
            $admin_user_where = [
                'zj.role_id' => $admin_user_data['role_id'],
                'zj.is_del' => 1,
                'zm.is_del' => 1
            ];

            $join = [
                ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'left'], //right
            ];
            $alias = 'zj';
            $jurisdiction_data = Crud::getRelationData('zht_jurisdiction', $type = 2, $admin_user_where, $join, $alias, $order = '', $field = 'zm.name', 1, 1000);
            if ($jurisdiction_data) {
                $jurisdiction_data = Many_One($jurisdiction_data);
                $admin_user_data['jurisdiction'] = $jurisdiction_data;
            } else {

                $admin_user_data['jurisdiction'] = [];
            }

            $admin_user_data['address_code'] = [$admin_user_data['province_num'], $admin_user_data['city_num'], $admin_user_data['area_num']];
            $admin_user_data['certificate_img'] = handle_img_take($admin_user_data['certificate_img']);
            $admin_user_data['contract_img'] = handle_img_take($admin_user_data['contract_img']);
            $admin_user_data['contract'] = conversion_time_year($admin_user_data['contract_start_time']) . '-' . conversion_time_year($admin_user_data['contract_end_time']);
            $admin_user_data['create_time_Exhibition'] = conversion_time_year($admin_user_data['create_time']);
            $admin_user_data['maddress'] = $admin_user_data['province'] . $admin_user_data['city'] . $admin_user_data['area'] . $admin_user_data['address'];
            if ($admin_user_data['sex'] == 1) {
                $admin_user_data['sex_name'] = '男';
            } elseif ($admin_user_data['sex'] == 2) {
                $admin_user_data['sex_name'] = '女';
            }
            return jsonResponseSuccess($admin_user_data);
        } else {
            throw new NothingMissException();
        }
    }

    //修改内容
    public static function editAdminUser()
    {

        $data = input();
        $table = request()->controller();
        $account_data = self::isuserData();
        if ($account_data['type'] == 7) { //1用户，2机构
            if (isset($data['certificate_img']) && !empty($data['certificate_img'])) { //证书图片
                $data['certificate_img'] = handle_img_deposit($data['certificate_img']);
            }
            if (isset($data['contract_img']) && !empty($data['contract_img'])) { //合同图片
                $data['contract_img'] = handle_img_deposit($data['contract_img']);
            }
            //地址标号
            if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
                $data['province_num'] = $data['address_code'][0];
                $data['city_num'] = $data['address_code'][1];
                $data['area_num'] = $data['address_code'][2];
            }

            $admin_user_id = $data['admin_user_id'];
            unset($data['admin_user_id']);
            $data['update_time'] = time();
            $where = [
                'id' => $admin_user_id,
            ];
            $edit_admin_user = Crud::setUpdate($table, $where, $data);
            if ($edit_admin_user) {
                return jsonResponseSuccess($edit_admin_user);
            } else {
                throw new UpdateMissException();
            }
        }
    }

    //删除管理员
    public static function delAdminUser($admin_user_role_id)
    {
        $account_data = self::isuserData();
        $table = 'admin_user_role';
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'mem_id' => $account_data['mem_id'],
                'id' => ['in', $admin_user_role_id]
            ];
            //验证管理员是否是本机构
            $AdminUser = Crud::getData($table, 1, $where, 'id');
            if (!$AdminUser) {
                throw new NothingMissException();
            }

            //删除管事员信息
//            $del_admin_user = Crud::setUpdate($table, $where, ['mem_id' => '']);
            $del_admin_user = Crud::setUpdate($table, $where, ['is_del' => 2]);
            if (!$del_admin_user) {
                throw new DelMissException();
            } else {
                return jsonResponseSuccess($del_admin_user);
            }

        }

    }

    //删除管理员 备用
    public static function delAdminUsers($admin_user_id)
    {
        $account_data = self::isuserData();
        $table = request()->controller();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'mem_id' => $account_data['mem_id'],
                'id' => ['in', $admin_user_id]
            ];
            //验证管理员是否是本机构
            $AdminUser = Crud::getData($table, 1, $where, 'id');
            if (!$AdminUser) {
                throw new NothingMissException();
            }

            //删除管事员信息
            Db::startTrans();
            $del_admin_user = Crud::setUpdate($table, $where, ['is_del' => 2]);
            if (!$del_admin_user) {
                Db::rollback();
                throw new DelMissException();
            }
            $accout_where = [
                'mem_id' => $account_data['mem_id'],
                'admin_user_id' => ['in', $admin_user_id],
            ];
            $del_accout = Crud::setUpdate('login_account', $accout_where, ['is_del' => 2]);
            if (!$del_accout) {
                Db::rollback();
                throw new DelMissException();
            } else {
                Db::commit();
                return jsonResponseSuccess($del_accout);
            }
        }

    }


}