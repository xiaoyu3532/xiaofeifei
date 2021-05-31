<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/8 0008
 * Time: 14:16
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\EditRecoMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;
use think\Db;

class Role extends BaseController
{
    //获取角色列表（权限名）
    public static function getjgRoleList($page = 1, $pageSize = 8, $role_name = '', $mem_id = '', $time_data = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'zr.is_del' => 1,
                'zr.type' => 3, //1为总平台，2为机构角色，3机构人员角色名
                'm.is_del' => 1,
                'm.status' => 1,
            ];
            (isset($mem_id) && !empty($mem_id)) && $where['zr.mem_id'] = $mem_id;//机构
            (isset($role_name) && !empty($role_name)) && $where['zr.role_name'] = ['like', '%' . $role_name . '%'];//管理员名称
            if (isset($time_data) && !empty($time_data)) {
                $start_time = $time_data[0] / 1000;
                $end_time = $time_data[1] / 1000;
                $where['zr.create_time'] = ['between', [$start_time, $end_time]];
            }
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $where['zr.mem_id'] = ['in', $mem_ids];
            } else {
                $where['zr.mem_id'] = $mem_id;
            }
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_member m', 'zr.mem_id = m.uid', 'left'], //right
            ];
            $alias = 'zr';
            $table = 'zht_role';
            $Role_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zr.id desc', $field = 'zr.*,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress', $page, $pageSize);
            if ($Role_data) {
                foreach ($Role_data as $k => $v) {
                    $Role_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                    $Role_data[$k]['power'] = unserialize($v['power_exhibition']);
                    $Role_data[$k]['time_datas'] = conversion_time_year($v['start_time']) . '-' . conversion_time_year($v['end_time']);
                    $Role_data[$k]['time_data'] = [$v['start_time'] * 1000, $v['end_time'] * 1000];
                    if (empty($v['role_describe'])) {
                        $Role_data[$k]['role_describe'] = '-';
                    }
                }
                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zr.id');
                $info_data = [
                    'info' => $Role_data,
                    'pageSize' => 8,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }

        } else {
            throw new ISUserMissException();
        }
    }

    public static function getjgRoleListField()
    {
        $data = [
            ['prop' => 'role_name', 'name' => '权限名称', 'width' => '', 'state' => ''],
            ['prop' => 'role_describe', 'name' => '权限描述', 'width' => '', 'state' => ''],
            ['prop' => 'time_datas', 'name' => '权限有效期', 'width' => '160', 'state' => '1'],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '100', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }


    //添加角色列表（权限名）权限做认证
    public static function addjgRole()
    {
        $account_data = self::isuserData();
        $data = input();
        if (isset($data['time_data']) && !empty($data['time_data'])) {
            $data['start_time'] = $data['time_data'][0] / 1000;
            $data['end_time'] = $data['time_data'][1] / 1000;
        } else {
            $data['start_time'] = time();
            $data['end_time'] = strtotime('2030-1-1');
        }
        unset($data['time_data']);
        $power = $data['power'];
        unset($data['power']);
        if (isset($data['mem_id']) || !empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $data['power_exhibition'] = serialize($power);
        $data['type'] = 3;
        $table = 'zht_role';
        Db::startTrans();
        $role_id = Crud::setAdd($table, $data, 2);
        if (!$role_id) {
            Db::rollback();
            throw new AddMissException();
        }
        if (!empty($power) && is_array($power)) {
            $data_power = self::getjgPowerPid($role_id, $power);
            if (!$data_power) {
                Db::rollback();
                throw new AddMissException();
            } else {
                Db::commit();
                return jsonResponseSuccess($data_power);
            }
        } else {
            Db::rollback();
            return jsonResponse('3000', '权限分类有误，请重新选择');
        }
    }

    //验证权限
    public static function isjgPower($power)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构  //yx_admin_jurisdiction
            $where = [
                'r.mem_id' => $account_data['mem_id'],
//                'r.mem_id' => 18,
                'r.is_del' => 1,
                'r.type' => 2, //1为总平台，2为机构角色，3机构人员角色名
//                'r.start_time' => ['<', time()],
//                'r.end_time' => ['>', time()],
                'zj.is_del' => 1
            ];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_zht_jurisdiction zj', 'zj.role_id = r.id', 'left'], //right
            ];
            $alias = 'r';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'zj.menu_id', 1, 1000000);
            if (empty($cname_data)) {
                return jsonResponse('3000', '此用户没有');
            } else {
                $cname_data = Many_One($cname_data);
                $diff_data = array_diff($power, $cname_data);
                if ($diff_data) {
                    return jsonResponse('3000', '权限参数有误，请重新选择');
                } else {
                    return 1000;
                }
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取本机构权限名（机构名）列表
    public static function getjgPowerList()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'r.mem_id' => $account_data['mem_id'],
                'r.is_del' => 1,
                'r.type' => 2, //1为总平台，2为机构角色，3机构人员角色名
                'r.start_time' => ['<', time()],
                'r.end_time' => ['>', time()],
                'zj.is_del' => 1,
                'm.is_delete' => 0,//0为正常
            ];
            $join = [

                ['yx_menu m', 'zj.menu_id = m.id', 'left'], //right
                ['yx_zht_jurisdiction zj', 'r.id = zj.role_id', 'left'], //right  yx_admin_jurisdiction
            ];
            $alias = 'r';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.*', 1, 10000);
            if ($cname_data) {
//                dump($cname_data);
                $menu_new_data = $cname_data;
                $menu_list = [];
                foreach ($cname_data as $k => $v) {
                    foreach ($menu_new_data as $kk => $vv) {
                        if ($v['id'] == $vv['parent_id']) {
                            $cname_data[$k]['children'][] = $vv;
                            $menu_list[$k] = $cname_data[$k];
                        }
                    }
                }
                return jsonResponseSuccess($menu_list);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取本机构所有的权限
    public static function getjgRoleData()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构,3机构人员 //yx_admin_jurisdiction
            $where = [
                'r.mem_id' => $account_data['mem_id'],
                'r.is_del' => 1,
                'r.type' => 2, //1为总平台，2为机构角色，3机构人员角色名
//                'r.start_time' => ['<', time()],
//                'r.end_time' => ['>', time()],
                'zj.is_del' => 1
            ];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_zht_jurisdiction zj', 'zj.role_id = r.id', 'left'], //right
                ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'right'], //right
            ];
            $alias = 'r';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'zm.*,zj.operation_type', 1, 1000000);
            if ($cname_data) {
                return $cname_data;
            } else {
                throw new NothingMissException();
            }
        }
    }

    //详情
    public static function getjgRoleDetails($role_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构,3机构人员
            $where = [
                'm.is_del' => 1,
                'm.status' => 1,
                'zr.is_del' => 1,
                'zr.id' => $role_id,
            ];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_member m', 'zr.mem_id = m.uid', 'left'], //right
            ];
            $alias = 'zr';
            $table = 'zht_role';
            $role_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = 'zr.dateline desc', $field = 'zr.*,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone');
            if ($role_data) {
                $role_data['maddress'] = $role_data['mprovince'] . $role_data['mcity'] . $role_data['marea'] . $role_data['msaddress'];
                $role_data['time_data'] = conversion_time_year($role_data['start_time']) . '-' . conversion_time_year($role_data['end_time']);
                $role_data['maddress'] = $role_data['mprovince'] . $role_data['mcity'] . $role_data['marea'] . $role_data['msaddress'];
                $role_data['real_member_name'] = self::getRoleUsers($role_data['id']);  //获取关联名称
                $power = unserialize($role_data['power_exhibition']);

                $role_data['power_name'] = self::getjgPowerName($power);  //获取关联名称
                return jsonResponseSuccess($role_data);
            } else {
                throw new NothingMissException();
            }

        } else {
            throw new ISUserMissException();
        }
    }

    public static function getRoleUsers($role_id)
    {

        $admin_user_ids = Crud::getData('login_account', 2, ['role_id' => $role_id, 'is_del' => 1], 'admin_user_id');
        $user_name = [];
        foreach ($admin_user_ids as $k => $v) {
            $real_member_name = Crud::getData('admin_user', 1, ['id' => $v['admin_user_id'], 'is_del' => 1], 'real_member_name');
            $user_name[] = $real_member_name['real_member_name'];
        }
        return $user_name;
    }


    //修改权限名称 后期做验证权限 role_id
    public static function editjgRole()
    {
        $data = input();
        if (isset($data['time_data']) && !empty($data['time_data'])) {
            $data['start_time'] = $data['time_data'][0] / 1000;
            $data['end_time'] = $data['time_data'][1] / 1000;
        }
        $data['power_exhibition'] = serialize($data['power']);
        $data['update_time'] = time();
        //修改权限名信息
        $role_data = Crud::setUpdate('zht_role', ['id' => $data['role_id']], $data);
        if (!$role_data) {
            throw new EditRecoMissException();
        }
        if (!empty($data['power']) && is_array($data['power'])) {
            //将本权限名所有的权限删除
            $del_role = Crud::setUpdate('zht_jurisdiction', ['role_id' => $data['role_id']], ['is_del' => 2]);
            if (!$del_role) {
                throw new EditRecoMissException();
            }
            $power_data = self::getjgPowerPid($data['role_id'], $data['power']);
            if (!$power_data) {
                throw new EditRecoMissException();
            }
            return jsonResponseSuccess($role_data);
        }


    }

    //获取本角色名已拥有值
    public static function getjgPersonnelRole($role_id, $type = 2)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构,3机构人员 //yx_admin_jurisdiction
            $where = [
                'r.id' => $role_id,
                'r.is_del' => 1,
                // 'r.type' => 3, //1为总平台，2为机构角色，3机构人员角色名
//                'r.start_time' => ['<', time()],
//                'r.end_time' => ['>', time()],
                'zj.is_del' => 1
            ];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_zht_jurisdiction zj', 'zj.role_id = r.id', 'left'], //right
                ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'right'], //right
            ];
            $alias = 'r';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, 2, $where, $join, $alias, $order = '', $field = 'zm.*,zj.operation_type', 1, 1000000);
            if ($cname_data) {
                if ($type == 1) {
                    return $cname_data;
                } else {
                    $cname_data = self::setRegroupheavy($cname_data);
                    return $cname_data;
//                    return jsonResponseSuccess($cname_data);
                }
            } else {
                throw new NothingMissException();
            }
        }

    }

    //获取未选择权限和本角色（权限名）已选的
    public static function getnotjgPowerData($role_id)
    {
        //获取机构所有限权
        $RoleData = self::getjgRoleData();
        //获取本角色名拥有的权限
        $PersonnelRole = self::getjgPersonnelRole($role_id, 1);
        //没有被选中的权限
        $newRoleData = get_diff_array_by_pk($RoleData, $PersonnelRole, 'id');
        //重新组合后的数据
        $PersonnelRole = self::setRegroup($PersonnelRole);
        $newRoleData = self::setRegroup($newRoleData);
        $data = [
            'PersonnelRole' => $PersonnelRole,
            'newRoleData' => $newRoleData,
        ];
        return $data;
    }

    //重新组合权限类目
    public static function setRegroup($Regroup_array)
    {
        $menu_new_data = $Regroup_array;
        $menu_list = [];
        foreach ($Regroup_array as $k => $v) {
            foreach ($menu_new_data as $kk => $vv) {
                if ($v['id'] == $vv['pid']) {
                    $Regroup_array[$k]['children'][] = $vv;
                    $menu_list[$k] = $Regroup_array[$k];
                } else {
                    $menu_list[$k] = $v;
                }
            }
        }
        return $menu_list;
    }

    //重新组合权限类目(去重)
    public static function setRegroupheavy($Regroup_array, $account_type, $role_id)
    {
        //获取最后一级
//        $Regroup_array = assoc_unique($Regroup_array, 'id');
        foreach ($Regroup_array as $k => $v) {
            $where_two = [
                'r.id' => $role_id,
                'r.is_del' => 1,
//            'r.start_time' => ['<', time()],
//            'r.end_time' => ['>', time()],
                'zj.is_del' => 1,
                'zm.is_del' => 1,
                'zm.grade' => 1,
                'zm.pid' => $v['id']
            ];
            if ($account_type == 2) {
                $where_two['zm.name'] = ['<>', '个人信息'];
            }
            $join = [
                ['yx_zht_jurisdiction zj', 'zj.role_id = r.id', 'left'], //right
                ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'right'], //right
            ];
            $alias = 'r';
            $table = 'zht_role';
            $two_children = Crud::getRelationData($table, 2, $where_two, $join, $alias, $order = 'zm.sort asp', $field = 'zm.*', 1, 1000000);
            if ($two_children) {
                $Regroup_array[$k]['children'] = $two_children;
                foreach ($two_children as $kk => $vv) {
                    $where_three = [
                        'pid' => $vv['id'],
                        'is_del' => 1
                    ];
                    $three_children = Crud::getData('zht_menu', 2, $where_three, '*', '', 1, 1000000);

                    if ($two_children) {
                        $Regroup_array[$k]['children'][$kk]['children'] = $three_children;
                    } else {
                        $Regroup_array[$k]['children'][$kk]['children'] = [];
                    }
                }
            } else {
                $Regroup_array[$k]['children'] = [];
            }
        }

        return $Regroup_array;
    }

    public static function setRegroupheavya($Regroup_array, $account_type)
    {
        //获取最后一级
//        $Regroup_array = assoc_unique($Regroup_array, 'id');
        foreach ($Regroup_array as $k => $v) {
            $where_two = [
                'pid' => $v['id'],
                'is_del' => 1
            ];
            if ($account_type == 2) {
                $where_two['name'] = ['<>', '个人信息'];
            }
            $two_children = Crud::getData('zht_menu', 2, $where_two, '*', '', 1, 1000000);
//            dump($two_children);
            if ($two_children) {
                $Regroup_array[$k]['children'] = $two_children;
                foreach ($two_children as $kk => $vv) {
                    $where_three = [
                        'pid' => $vv['id'],
                        'is_del' => 1
                    ];
                    $three_children = Crud::getData('zht_menu', 2, $where_three, '*', '', 1, 1000000);

                    if ($two_children) {
                        $Regroup_array[$k]['children'][$kk]['children'] = $three_children;
                    } else {
                        $Regroup_array[$k]['children'][$kk]['children'] = [];
                    }
                }
            } else {
                $Regroup_array[$k]['children'] = [];
            }
        }

        return $Regroup_array;
    }

    public static function setRegroupheavys($Regroup_array)
    {
        //获取最后一级
//        $grade_data = Db::name('zht_menu')->where(['is_del' => 1])->max('grade');

        $Regroup_array = assoc_unique($Regroup_array, 'id');
        //获取最后一级
//        $grade_data = Db::name('zht_menu')->where(['is_del' => 1])->max('grade');
        $menu_new_data = $Regroup_array;
        $menu_list = [];
        foreach ($Regroup_array as $k => $v) {
            foreach ($menu_new_data as $kk => $vv) {
                if ($v['id'] == $vv['pid']) {
                    $Regroup_array[$k]['children'][] = $vv;
                    $menu_list[$k] = $Regroup_array[$k];
                }
            }
        }
        foreach ($menu_list as $k => $v) {
            if (isset($v['children'])) {
                foreach ($v['children'] as $kk => $vv) {
                    if (!isset($vv['children'])) {
                        $menu_list[$k]['children'][$kk]['children'] = [];
                    }
                }
            }
        }
        return $menu_list;
    }


    //获取用户绑定的
    public static function getRoleUser($role_id)
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 2 || $user_data['type'] == 7) { //1用户，2机构
            $where = [
                'r.role_id' => $role_id,
                'r.is_del' => 1,
                'u.is_del' => 1,
                'u.type' => 1,
            ];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_user u', 'r.user_id = u.id', 'left'], //right
            ];
            $alias = 'r';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'u.name', 1, 1000000);
            if ($cname_data) {
                $cname_data = implode(",", $cname_data);
                return $cname_data;
            } else {
                return '';
            }
        }
    }

    //获取管理员角色名
    public static function getjgRoleName($mem_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            if (empty($mem_id)) {
                $mem_id = $account_data['mem_id'];
            }
            $where = [
                'is_del' => 1,
                'mem_id' => $mem_id,
                'type' => 3, //1总平台，2为机构其他角色名称
            ];

            $table = 'zht_role';
            $cname_data = Crud::getData($table, 2, $where, $field = 'id,role_name');
            if ($cname_data) {
                return jsonResponseSuccess($cname_data);
            } else {
                throw new NothingMissException();
            }

        }
    }

    //获取本机构所有权限名
    public static function getjgPowerdrop($mem_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'la.mem_id' => $mem_id,
                'zr.is_del' => 1,
                'zr.type' => 2, //1为总平台，2为机构角色，3机构人员角色名
                'zr.start_time' => ['<', time()],
                'zr.end_time' => ['>', time()],
                'zj.is_del' => 1,
                'zm.is_del' => 1,
                'la.is_del' => 1,
//                'm.is_delete' => 0,//0为正常
            ];
            $join = [
                ['yx_login_account la', 'zr.id = la.role_id', 'left'],
                ['yx_zht_jurisdiction zj', 'zr.id = zj.role_id', 'left'],
                ['yx_zht_menu zm', 'zj.menu_id = zm.id', 'left'],
            ];
            $alias = 'zr';
            $table = 'zht_role';
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'zm.id value,zm.grade,zm.name label,zm.pid', 1, 10000);
            if ($cname_data) {
//                dump($cname_data);
                $menu_new_data = $cname_data;
                $menu_list = [];
                foreach ($cname_data as $k => $v) {
                    foreach ($menu_new_data as $kk => $vv) {
                        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
                            if ($vv['label'] == '权限模板') {
                                unset($menu_new_data[$kk]);
                            }
                            if ($vv['label'] == '人员管理') {
                                unset($menu_new_data[$kk]);
                            }
                        }
                        if ($v['value'] == $vv['pid']) {
                            $cname_data[$k]['children'][] = $vv;
                            $menu_list[$k] = $cname_data[$k];
                        }

                    }
                }
                foreach ($menu_list as $k => $v) {
                    if ($v['grade'] != 0) {
                        unset($menu_list[$k]);
                    }
                }

                $data_menu_list = [];
                foreach ($menu_list as $k => $v) {
                    $data_menu_list[] = $v;
                }
                return jsonResponseSuccess($data_menu_list);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    public static function getjgPowerdrops()
    {
        $account_data = self::isuserData();
        $where = [
            'la.mem_id' => $account_data['mem_id'],
            'la.is_del' => 1,
            'zj.is_del' => 1,
        ];
        //获取角色
        $join = [
            ['yx_login_account la', 'zj.role_id = la.role_id', 'left'], //right
        ];
        $alias = 'zj';
        $table = 'zht_jurisdiction';
        $role_ids = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'zj.*', 1, 1000);
        $data_jurisdiction = [];
        foreach ($role_ids as $k => $v) {
            if ($v['pid'] != 0) {
                $data_jurisdiction[] = [$v['pid'], $v['menu_id']];
            }
        }
        return jsonResponseSuccess($data_jurisdiction);
    }

    //用权限数组获取上组
    public static function getjgPowerPid($role_id, $power)
    {
        $power = Many_One($power);
        $power = array_unique($power);
        foreach ($power as $k => $v) {
            $pid = Crud::getData('zht_menu', 1, ['id' => $v, 'is_del' => 2], 'pid');
            $opower_add = [
                'role_id' => $role_id,
                'type' => 3,//1总平台，2机构，3机构人员
                'menu_id' => $v,
                'pid' => $pid['pid']
            ];
            $opower_id = Crud::setAdd('zht_jurisdiction', $opower_add);
        }
        if ($opower_id) {
            return $opower_id;
        }
    }

    public static function getjgPowerName($power)
    {
        $power = Many_One($power);

        $power_names = [];
        foreach ($power as $k => $v) {
            $Power_name = Crud::getData('zht_menu', 1, ['id' => $v, 'is_del' => 1], 'name');
            $power_names[] = $Power_name['name'];

        }
        if ($power_names) {
            return $power_names;
        }
    }

    //删除权限
    public static function deljgPowerPid($role_id)
    {
        $role_data = Crud::setUpdate('zht_role', ['id' => ['in', $role_id], 'type' => 3], ['is_del' => 2]);
        if (!$role_data) {
            throw new UpdateMissException();
        }
        $del_jurisdiction = Crud::setUpdate('zht_jurisdiction', ['role_id' => ['in', $role_id], 'type' => 3], ['is_del' => 2]);
        if (!$del_jurisdiction) {
            throw new UpdateMissException();
        } else {
            return jsonResponseSuccess($del_jurisdiction);
        }


    }

}