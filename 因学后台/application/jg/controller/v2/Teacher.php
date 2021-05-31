<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 9:47
 */

namespace app\jg\controller\v2;


use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\jg\controller\v2\Classroom;
use app\lib\exception\AddMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Teacher extends BaseController
{
    //获取机构老师
    public static function getjgTeacher($page = '1', $teacher_name = '', $mem_id = '', $teacher_type_id = '', $teacher_nickname = '', $teacher_phone = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                't.is_del' => 1,
            ];
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $where['t.mem_id'] = ['in', $mem_ids];
            } else {
                $where['t.mem_id'] = $mem_id;
            }


            $mem_ids = bindingMember::getbindingjgMemberId();
            if (isset($mem_id) && !empty($mem_id)) {
                $where['t.mem_id'] = $mem_id;
            } else {
                $where['t.mem_id'] = ['in', $mem_ids];
            }
        }
//        (isset($teacher_name) && !empty($teacher_name)) && $where['t.teacher_name'] =  $teacher_name ;
        (isset($teacher_nickname) && !empty($teacher_nickname)) && $where['t.teacher_nickname'] = ['like', '%' . $teacher_nickname . '%'];
        (isset($teacher_phone) && !empty($teacher_phone)) && $where['t.teacher_phone'] = $teacher_phone;
//        (isset($teacher_type_id) && !empty($teacher_type_id[1])) && $where['t.category_small_id'] =  $teacher_type_id[1];

        if (isset($teacher_type_id) && !empty($teacher_type_id)) {
            $teacher_type_name = Crud::getData('category_small', 1, ['id' => $teacher_type_id[1], 'is_del' => 1], 'category_small_name');
            $where['t.teacher_type_name'] = ['like', '%' . $teacher_type_name['category_small_name'] . '%'];
        }
        $join = [
            ['yx_member m', 't.mem_id = m.uid', 'left'],
            ['yx_admin_user au', 't.id = au.teacher_id', 'left'],
        ];
        $alias = 't';
        $table = request()->controller();
        $teacher_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 't.create_time desc', $field = 't.*,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone,au.real_member_name', $page);
        if ($teacher_data) {
            foreach ($teacher_data as $k => $v) {
                $teacher_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                if ($v['sex'] == 1) {
                    $teacher_data[$k]['sex_name'] = '男';
                } else if ($v['sex'] == 2) {
                    $teacher_data[$k]['sex_name'] = '女';
                }
                $teacher_data[$k]['sex'] = (string)$v['sex'];

                if (isset($v['certificate']) && !empty($v['certificate'])) { //教师资格证书照片
                    $teacher_data[$k]['certificate'] = handle_img_take($v['certificate']);
                } else {
                    $teacher_data[$k]['certificate'] = [];
                }
                if (isset($v['diploma']) && !empty($v['diploma'])) { //毕业证书
                    $teacher_data[$k]['diploma'] = handle_img_take($v['diploma']);
                } else {
                    $teacher_data[$k]['diploma'] = [];
                }
                if (isset($v['prize']) && !empty($v['prize'])) { //获取奖证书
                    $teacher_data[$k]['prize'] = handle_img_take($v['prize']);
                } else {
                    $teacher_data[$k]['prize'] = [];
                }

                $teacher_data[$k]['teacher_type_id'] = unserialize($v['teacher_type_id']);
                $teacher_data[$k]['create_time'] = date('Y-m-d H:i:s');
                if (empty($v['real_member_name'])) {
                    $teacher_data[$k]['real_member_name'] = '-';
                }

                $teacher_data[$k]['teacher_type_name_exhibition'] = explode(',', $v['teacher_type_name']);
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 't.id');
            $info_data = [
                'info' => $teacher_data,
                'pageSize' => 8,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取机构老师列表字段
    public static function getjgTeacherField()
    {
        $data = [
            ['prop' => 'teacher_nickname', 'name' => '昵称', 'width' => '', 'state' => ''],
            ['prop' => 'real_member_name', 'name' => '真实名称', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'teacher_age', 'name' => '教龄', 'width' => '160', 'state' => '1'],
            ['prop' => 'teacher_type_name_exhibition', 'name' => '所属课目', 'width' => '', 'state' => ''],
            ['prop' => 'teacher_phone', 'name' => '手机号', 'width' => '100', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'member_phone', 'name' => '机构电话', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }


    //添加机构老师 teacher_nickname 老师昵称 sex 性别 teaching_phone 手机号 teaching_age 教龄  grade 星级  brief 简介 teacher_name 老师真实名称
    //certificate_explain 教师资格证书照片说明  diploma_explain 毕业证书说明 diploma_prize 获取奖证书说明
    public static function addjgTeacher()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || ($account_data['type'] == 7)) { //1用户，2机构
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            if ($data['certificate']) { //教师资格证书照片
                $data['certificate'] = handle_img_deposit($data['certificate']);
            }
            if ($data['diploma']) { //毕业证书
                $data['diploma'] = handle_img_deposit($data['diploma']);
            }
            if ($data['prize']) { //获取奖证书
                $data['prize'] = handle_img_deposit($data['prize']);
            }
            $data['teacher_identifier'] = time() . rand(10, 99);
            //适合老师分类
            if (isset($data['teacher_type_id']) && !empty($data['teacher_type_id'])) {
                $data['teacher_type_name'] = Classroom::getCategorySmall($data['teacher_type_id']);
                $data['teacher_type_id'] = serialize($data['teacher_type_id']);
//                $data['category_id'] = $data['teacher_type_id'][0];
//                $data['category_small_id'] = $data['category_small_id'][1];
            }
            $table = request()->controller();
            $info = Crud::setAdd($table, $data);
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                throw new AddMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //修改机构老师
    public static function setjgTeacher()
    {
        $data = input();
        $where = [
            'id' => $data['teacher_id']
        ];

        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        unset($data['teacher_id']);
        if ($data['certificate']) { //教师资格证书照片
            $data['certificate'] = handle_img_deposit($data['certificate']);
        } else {
            $data['certificate'] = [];
        }
        if ($data['diploma']) { //毕业证书
            $data['diploma'] = handle_img_deposit($data['diploma']);
        } else {
            $data['diploma'] = [];
        }
        if ($data['prize']) { //获取奖证书
            $data['prize'] = handle_img_deposit($data['prize']);
        } else {
            $data['prize'] = [];
        }
        //适合老师分类
        if (isset($data['teacher_type_id']) && !empty($data['teacher_type_id'])) {
            $data['teacher_type_name'] = Classroom::getCategorySmall($data['teacher_type_id']);
            $data['teacher_type_id'] = serialize($data['teacher_type_id']);
//            $data['category_id'] = $data['teacher_type_id'][0];
//            $data['category_small_id'] = $data['category_small_id'][1];
        }
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }


    //删除老师
    public static function deljgTeacher($teacher_id)
    {
        $where = [
            'id' => ['in', $teacher_id]
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $res = Crud::setUpdate($table, $where, $upData);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new NothingMissException();
        }
    }

    //组合课目分类及老师名称
    public static function getjgTeacherTypesearch($mem_id = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $mem_id = ['in', $mem_ids];
            }
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $mem_id,
                ];
                $children = Crud::getData('category_small', $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                    foreach ($children as $kk => $vv) {
                        $where = [
                            'is_del' => 1,
                            'type' => 1,
                            'mem_id' => $mem_id,
                            'teacher_type_name' => ['like', '%' . $vv['label'] . '%']
                        ];
                        $curriculum_info = Crud::getData('teacher', $type = 2, $where, $field = 'id value,teacher_nickname label', $order = '', $page = '1', $pageSize = '1000');
                        if ($curriculum_info) {
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                        } else {
                            $info[$k]['children'][$kk]['children'] = [];
                        }
                    }
                } else {
                    $info[$k]['children'] = [];
                }

            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }


    //老师分类列表及老师姓名
    public static function getjgTeacherTypesearchs()
    {
        $where1 = [
            'type' => 1,
            'is_del' => 1,
        ];
        $table1 = 'teacher_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id value,name label', $order = 'sort desc', $page = '1', $pageSize = '10000');
        if ($type_name_list) {
            $user_data = self::isuserData();
            if ($user_data['type'] == 2) { //1用户，2机构
                $table = request()->controller();
                foreach ($type_name_list as $k => $v) {
                    $where = [
                        'is_del' => 1,
                        'mem_id' => $user_data['mem_id'],
                        'type_id' => $v['value'],
                    ];
                    $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
                    $type_name_list[$k]['children'] = $info;
                }
            }
            return jsonResponseSuccess($type_name_list);
        } else {
            throw new NothingMissException();
        }
    }

    //获取老师下拉列表
    public static function getjgTeacherMember()
    {
        $data = input();  //type 2返回未绑定的老师
        if (!isset($data['type']) || empty($data['type'])) {
            $data['type'] = 1;
        }
        $where = [
            'is_del' => 1,
        ];
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $where['mem_id'] = ['in', $mem_ids];
        } else {
            $where['mem_id'] = $data['mem_id'];
        }
        $table = request()->controller();
        $teacher_data = Crud::getData($table, $type = 2, $where, 'id,teacher_nickname', '', 1, 1000);
        if ($data['type'] == 2) {
            //查询出本机构已绑定的用户
            $binding_teacher_ids = Crud::getData('admin_user', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'teacher_id id');
            if ($binding_teacher_ids) {
                $teacher_data = get_diff_array_by_pk($teacher_data, $binding_teacher_ids, 'id');
            }
        }

        if ($teacher_data) {
            return jsonResponseSuccess($teacher_data);
        } else {
            throw new NothingMissException();
        }
    }


}