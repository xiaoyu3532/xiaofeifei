<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/1 0001
 * Time: 13:42
 */

namespace app\jg\controller\v2;


use app\jg\controller\v1\BaseController;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\EditRecoMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Db;

class LmportStudent extends BaseController
{
    //展示公海池与学生
    public static function getLmportStudent($student_name = '', $mem_id = '', $page = 1)
    {
        $mem_data = self::isuserData();
//        $where = [];
        //名称搜索
        if ($mem_data['type'] == 2) {
            $where = [
                'lsm.mem_id' => $mem_data['mem_id'],
                'lm.type' => 1, //1公海池，2业务员列表
//                'm.is_del' => 1,
//                'm.status' => 1,
                'lsm.is_del' => 1,
            ];
        } elseif ($mem_data['type'] == 7) {
            $where = [
                'lsm.mem_id' => $mem_data['mem_id'],
                'lm.salesman_id' => $mem_data['user_id'],
                'lm.type' => 1, //1公海池，2业务员列表
                'lsm.is_del' => 1,
//                'm.is_del' => 1,
//                'm.status' => 1,
            ];
        } else {
            throw new ISUserMissException();
        }
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lm.mem_id'] = $mem_id;
        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            ['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
        $info = Crud::getRelationDataWhereOr($table, $type = 2, $where, $whereOr, $join, $alias, '', 'lm.*,m.cname,sa.user_name saname', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lms.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取用户信息加机构名称
    public static function getLmportStudentDetails($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }

        //用户没进入平台，或在平台没有购买课程
        $where = [
            'lm.id' => $student_id,
//            'm.is_del' => 1,
//            'm.status' => 1,
            'lm.is_del' => 1,
        ];
        $table = request()->controller();
        $join = [
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
            ['yx_lmport_student_member lsm', 'lm.id = lsm.student_id', 'left'], //机构关联
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
        ];
        $alias = 'lm';
        $info = Crud::getRelationData($table, 1, $where, $join, $alias, 'lm.create_time desc', 'lm.id,lm.student_name,lm.phone,lm.sex,lm.year_age,lm.create_time,lm.customer_type,lm.student_type,m.cname,m.province,m.city,m.area,m.address,sa.user_name saname');
        $info['whether_type'] = 1; //1没进入平台的用户，2进入平台的用户

        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }


    }

    public static function getLmportStudentDetailss($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }

        $isStudent = self::isStudent($student_id);
        if ($isStudent == 2000) {
            //用户没进入平台，或在平台没有购买课程
            $where = [
                'lm.id' => $student_id,
//            'm.is_del' => 1,
//            'm.status' => 1,
                'lm.is_del' => 1,
            ];
            $table = request()->controller();
            $join = [
                ['yx_member m', 'lm.mem_id = m.uid', 'left'], //机构信息
                ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
            ];
            $alias = 'lm';
            $info = Crud::getRelationData($table, 1, $where, $join, $alias, 'lm.create_time desc', 'lm.id,lm.student_name,lm.phone,lm.sex,lm.year_age,lm.create_time,lm.customer_type,lm.student_type,m.cname,m.province,m.city,m.area,m.address,sa.user_name saname');
            $info['whether_type'] = 1; //1没进入平台的用户，2进入平台的用户
        } else {
            $where = [
                'lm.id' => $student_id,
//            'm.is_del' => 1,
//            'm.status' => 1,
                'lm.is_del' => 1,
            ];
            $table = request()->controller();
            $join = [
                ['yx_member m', 'lm.mem_id = m.uid', 'left'], //机构信息
                ['yx_student s', 'lm.student_id = s.id', 'left'], //用户进入平台直接调取平台内的学生信息
                ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
            ];
            $alias = 'lm';  //lm.id lmid
            $info = Crud::getRelationData($table, 1, $where, $join, $alias, '', 's.id,lm.student_type,lm.customer_type,lm.identifier,s.name student_name,s.phone,s.sex,s.year_age,s.create_time,m.cname,m.province,m.city,m.area,m.address,sa.user_name saname');
            $info['whether_type'] = 2; //1没进入平台的用户，2进入平台的用户
        }
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }


    }

    //修改用户信息加机构名称 $student_id学生ID， $whether_type 1为无进入平台，2已在平台购买课程
    public static function editLmportStudentDetails()
    {
        $data = input();
        $student_id = $data['id'];
        unset($data['id']);
        $table = request()->controller();
        $data['updata_time'] = time();
        $StudentDetails_data = Crud::setUpdate($table, ['id' => $student_id], $data);
        if ($StudentDetails_data) {
            return jsonResponseSuccess($StudentDetails_data);
        } else {
            throw new EditRecoMissException();
        }

    }

    public static function editLmportStudentDetailss()
    {
        $data = input();
        $student_id = $data['id'];
        $whether_type = $data['whether_type'];
        unset($data['id']);
        unset($data['whether_type']);
        $table = request()->controller();
        $data['updata_time'] = time();
        if ($whether_type == 1) {
            $StudentDetails_data = Crud::setUpdate($table, ['id' => $student_id], $data);
            if ($StudentDetails_data) {
                return jsonResponseSuccess($StudentDetails_data);
            } else {
                throw new EditRecoMissException();
            }
        } elseif ($whether_type == 2) {
            Db::startTrans();
            $lmport_student_details_data = Crud::setUpdate($table, ['student_id' => $student_id], $data);
            if (!$lmport_student_details_data) {
                Db::rollback();
                throw new EditRecoMissException();
            }
            if (isset($data['student_name']) && !empty($data['student_name'])) {
                $data['name'] = $data['student_name'];
                unset($data['student_name']);
                unset($data['student_type']);
            }
            $Student_data = Crud::setUpdate('student', ['id' => $student_id], $data);
            if (!$Student_data) {
                Db::rollback();
                throw new EditRecoMissException();
            } else {
                Db::commit();
                return jsonResponseSuccess($Student_data);
            }

        }

    }

    //获取用户身份信息 $whether_type 1为无进入平台，2已在平台购买课程
    public static function getLmportStudentInfo($student_id)
    {
        $table = request()->controller();
        $where = [
            'id' => $student_id,
            'is_del' => 1,
        ];
        $lmport_student_info = Crud::getData($table, 1, $where, 'id,birthday,id_card,province,city,area,address,school,class');
        if ($lmport_student_info) {
            return jsonResponseSuccess($lmport_student_info);
        } else {
            throw new NothingMissException();
        }
    }

    public static function getLmportStudentInfos($student_id)
    {
        $table = request()->controller();
        $isStudent = self::isStudent($student_id);
        if ($isStudent == 2000) { //用户没进入平台，或在平台没有购买课程
            $where = [
                'id' => $student_id,
                'is_del' => 1,
            ];
            $lmport_student_info = Crud::getData($table, 1, $where, 'id,birthday,id_card,province,city,area,address,school,class');
            $lmport_student_info['whether_type'] = 1;
        } else {
            $where = [
                'id' => $isStudent,
                'is_del' => 1,
            ];
            $lmport_student_info = Crud::getData('student', 1, $where, 'id,birthday,id_card,province,city,area,address,school,class');
            $lmport_student_info['whether_type'] = 2;
        }

        if ($lmport_student_info) {
            return jsonResponseSuccess($lmport_student_info);
        } else {
            throw new NothingMissException();
        }
    }

    //修改用户身份信息 $whether_type 1为无进入平台，2已在平台购买课程
    public static function editLmportStudentInfo()
    {
        $data = input();
        $student_id = $data['student_id'];
        unset($data['student_id']);
        $table = request()->controller();
        //用户没进入平台，或在平台没有购买课程
        $lmport_student_info = Crud::setUpdate($table, ['id' => $student_id], $data);
        if ($lmport_student_info) {
            return jsonResponseSuccess($lmport_student_info);
        } else {
            throw new NothingMissException();
        }

    }

    public static function editLmportStudentInfos()
    {
        $data = input();
        $whether_type = $data['whether_type'];
        $student_id = $data['student_id'];
        unset($data['student_id']);
        unset($data['whether_type']);
        $table = request()->controller();
        if ($whether_type == 1) {
            //用户没进入平台，或在平台没有购买课程
            $lmport_student_info = Crud::setUpdate($table, ['id' => $student_id], $data);
            if ($lmport_student_info) {
                return jsonResponseSuccess($lmport_student_info);
            } else {
                throw new NothingMissException();
            }
        } else {
            Db::startTrans();
            //修改导入学生信息
            $lmport_student_info = Crud::setUpdate($table, ['student_id' => $student_id], $data);
            if (!$lmport_student_info) {
                Db::rollback();
                throw new UpdateMissException();
            }
            //修改入驻学生
            $student_data = Crud::setUpdate('student', ['id' => $student_id], $data);
            if (!$student_data) {
                Db::rollback();
                throw new UpdateMissException();
            } else {
                Db::commit();
                return jsonResponseSuccess($student_data);
            }
        }
    }

    //添加学长信息及关系
    public static function addLmportStudentParent()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $whether_type = $data['whether_type'];
        unset($data['whether_type']);
        $student_id = $data['student_id'];
        unset($data['student_id']);
        $LmportStudentParent = Crud::setAdd('parent', $data, 2);
        if (!$LmportStudentParent) {
            throw  new AddMissException();
        }
        if ($whether_type == 1) {
            $StudentParent = [
                'student_id' => $student_id,
                'parent_id' => $LmportStudentParent,
                'type' => 1, //1导入学生ID，2学生ID
            ];
        } elseif ($whether_type == 2) {
            $StudentParent = [
                'student_id' => $student_id,
                'parent_id' => $LmportStudentParent,
                'type' => 2, //1导入学生ID，2学生ID
            ];
        }
        $student_parent_relation = Crud::setAdd('student_parent_relation', $StudentParent);
        if (!$student_parent_relation) {
            throw new AddMissException();
        } else {
            return jsonResponseSuccess($student_parent_relation);
        }
    }
    public static function addLmportStudentParents()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $whether_type = $data['whether_type'];
        unset($data['whether_type']);
        $student_id = $data['student_id'];
        unset($data['student_id']);
        $LmportStudentParent = Crud::setAdd('parent', $data, 2);
        if (!$LmportStudentParent) {
            throw  new AddMissException();
        }
        if ($whether_type == 1) {
            $StudentParent = [
                'student_id' => $student_id,
                'parent_id' => $LmportStudentParent,
                'type' => 1, //1导入学生ID，2学生ID
            ];
        } elseif ($whether_type == 2) {
            $StudentParent = [
                'student_id' => $student_id,
                'parent_id' => $LmportStudentParent,
                'type' => 2, //1导入学生ID，2学生ID
            ];
        }
        $student_parent_relation = Crud::setAdd('student_parent_relation', $StudentParent);
        if (!$student_parent_relation) {
            throw new AddMissException();
        } else {
            return jsonResponseSuccess($student_parent_relation);
        }
    }

    //展示家长信息  $whether_type 1为无进入平台，2已在平台购买课程  传入的值student_id会根据whether_type不同
    public static function getLmportStudentParent()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $whether_type = $data['whether_type'];
        unset($data['whether_type']);

        if ($whether_type == 1) {
            $where = [
                'spr.student_id' => $data['student_id'],
                'spr.is_del' => 1,
                'p.is_del' => 1,
                'spr.type' => 1,
            ];

        } elseif ($whether_type == 2) {
            $where = [
                'spr.student_id' => $data['student_id'],
                'spr.is_del' => 1,
                'p.is_del' => 1,
                'spr.type' => 2,
            ];
        }
        $join = [
            ['yx_parent p', 'spr.parent_id = p.id', 'left'], //机构信息
        ];
        $alias = 'spr';
        $parent_data = Crud::getRelationData('student_parent_relation', $type = 2, $where, $join, $alias, '', 'p.*', 1, 1000);
        if (!$parent_data) {
            throw new AddMissException();
        } else {
            return jsonResponseSuccess($parent_data);
        }
    }

    //修改家长信息  $whether_type 1为无进入平台，2已在平台购买课程  传入的值student_id会根据whether_type不同
    public static function editLmportStudentParent()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $id = $data['parent_id'];
        unset($data['parent_id']);
        $LmportStudentParent = Crud::setUpdate('parent', ['id' => $id], $data);
        if (!$LmportStudentParent) {
            throw  new AddMissException();
        } else {
            return jsonResponseSuccess($LmportStudentParent);
        }
    }

    //潜在学院移到公海池
    public static function moveLmportStudent($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_id]
        ];
        $updata = [
            'get_into_time' => time(),
            'type' => 1, //1公海池，2业务员列表
        ];
        $table = request()->controller();
        $lmport_student_data = Crud::setUpdate($table, $where, $updata);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取机构或业务员直行导入数据
    //学生性名与手机号 机构
    public static function getOwnLmportStudent($student_name = '', $mem_id = '', $time_data = '', $page = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'lm.mem_id' => $mem_data['mem_id'],
                'lm.type' => 2, //1公海池，2业务员列表
                'm.is_del' => 1,
                'm.status' => 1,
//                'sa.is_del' => 1,
            ];
        } elseif ($mem_data['type'] == 7) {
            $where = [
                'lm.mem_id' => $mem_data['mem_id'],
                'lm.salesman_id' => $mem_data['user_id'],
                'lm.type' => 2, //1公海池，2业务员列表
//                'sa.is_del' => 1,
//                'm.is_del' => 1,
//                'm.status' => 1,
            ];
        } else {
            throw new ISUserMissException();
        }
        //名称搜索 手机号
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lm.mem_id'] = $mem_id;
        //时间筛选
        if (isset($time_data) && !empty($time_data)) {
//            if ($info['start_time']) {
//                $info['start_time'] = $info['start_time'] * 1000;
//            }
//            if ($info['end_time']) {
//                $info['end_time'] = $info['end_time'] * 1000;
//            }
        }
        $table = request()->controller();
        $join = [
            ['yx_member m', 'lm.mem_id = m.uid', 'left'], //机构信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lm';
        $info = Crud::getRelationDataWhereOr($table, $type = 2, $where, $whereOr, $join, $alias, 'lm.create_time desc', 'lm.*,m.cname,sa.user_name saname', $page);
//        dump($info);exit;
        foreach ($info as $k => $v) {
            $where_visit = [
                'id' => $v['return_visit_id']
            ];
            $salesman_type = Crud::getData('return_visit', 3, $where_visit, 'type', 'id desc');
            if ($salesman_type) {
                $info[$k]['salesman_type'] = $salesman_type['type'];
            } else {
                $info[$k]['salesman_type'] = '-';
            }
        }


        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lms.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //公海池移到潜在学院
    public static function moveOwnLmportStudent($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_id]
        ];
        $updata = [
            'get_into_time' => time(),
            'type' => 2, //1公海池，2业务员列表
        ];
        $table = request()->controller();
        $lmport_student_data = Crud::setUpdate($table, $where, $updata);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //删除学生
    public static function delOwnLmportStudent($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_id]
        ];

        $table = request()->controller();
        $lmport_student_data = Crud::setUpdate($table, $where, ['is_del' => 1]);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //导入学生展示(未完成，等做完)
    public static function getLmportStudentExhibition()
    {
        $data = input();
        $mem_data = self::isuserData();
        if ($mem_data != 2 && $mem_data != 7) {
            throw new ISUserMissException();
        }
        $table = request()->controller();
        //判断当前用户是否进入平台
        $where = [
            'id' => $data['id'],
            'is_del' => 1,
        ];
        $lmport_identifie = Crud::getData($table, $type = 1, $where, $field = 'identifier');
        if (empty($lmport_identifie['identifier'])) {
            //用户没有进入平台
            $lmport_student_data = Crud::getData($table, $type = 1, $where, $field = '*');

            //返回访信息 yx_return_visit
            $return_visitwhere = [
                'id' => $lmport_student_data['return_visit_id'],
                'is_del' => 1
            ];
            $return_visit_data = Crud::getData('return_visit', $type = 1, $return_visitwhere, $field = '*');


        } else {
            //用户进入平台

        }

    }

    //添加导入学生数据(单个用户提交)
    public static function addLmportStudent()
    {
        $account_data = self::isuserData();
        $data = input();
        $data['mem_id'] = $account_data['mem_id'];
        $data['salesman_id'] = $account_data['user_id'];
        if (isset($data['year_age']) && !empty($data['year_age'])) {
            $data['month_age'] = $data['year_age'] * 12;
        }
        //判断此用户是否在本机构中 yx_lmport_student yx_lmport_student_member
        $where = [
            'student_name' => $data['student_name'],
            'phone' => $data['phone'],
            'is_del' => 1,
        ];
        $lmport_student_data = Crud::getData('lmport_student', 1, $where, 'id');

        if ($lmport_student_data) {//验证本机构是否有此学生
            $lmport_student_member_where = [
                'student_id' => $lmport_student_data['id'],
                'mem_id' => $account_data['mem_id'],
                'is_del' => 1,
            ];
            $lmport_student_member_data = Crud::getData('lmport_student_member', 1, $lmport_student_member_where, 'id');
            if ($lmport_student_member_data) {
                return jsonResponse('3000', '此用户以存在');
            }
        }
        Db::startTrans();
        $table = request()->controller();
        $student_id = Crud::setAdd($table, $data, 2);
        if (!$student_id) {
            Db::rollback();
            throw new AddMissException();
        }
        $lmport_student_member_data = [
            'student_id' => $student_id,
            'mem_id' => $account_data['mem_id'],
        ];
        $lmport_student_member_info = Crud::setAdd('lmport_student_member', $lmport_student_member_data);
        if (!$lmport_student_member_info) {
            Db::rollback();
            throw new AddMissException();
        } else {
            Db::commit();
            return jsonResponseSuccess($student_id);
        }


    }

    //添加回访记录 yx_return_visit
    public static function addReturnVisit()
    {
        $data = input();
        $mem_data = self::isuserData();
        $data['salesman_id'] = $mem_data['user_id'];
        $info = Crud::setAdd('return_visit', $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }

    }

    //获取回访记录 yx_return_visit
    public static function getReturnVisit()
    {
        $data = input();
        $where = [
            'rv.is_del' => 1,
            'rv.lmport_student_id' => $data['lmport_student_id'],
        ];
        $join = [
            ['yx_salesman sa', 'rv.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'rv';
        $info = Crud::getRelationData('return_visit', $type = 2, $where, $join, $alias, 'create_time desc', 'rv.type,rv.content,rv.create_time,sa.user_name', $data['page']);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun('return_visit', $where, $join, $alias, 'rv.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //导入学生列表
    public function LmportStudentList()
    {
        $mem_data = self::isuserData();
        if (empty($mem_data)) {
            throw new ISUserMissException();
        }

        vendor('PHPExcelguide.Classes.PHPExcel.IOFactory'); //导入PHPExcel文件中的IOFactory.php类
        $file = request()->file('file');//获取文件，file是请求的参数名
        $info = $file->move(ROOT_PATH . 'public' . DS . 'excel');//move将文件移动到项目文件的xxx
        if ($info) {
            $excel_path = $info->getSaveName();  //获取上传文件名
//          $excel_suffix = $info->getExtension(); //获取上传文件后缀
            $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $excel_path;   //上传文件的地址
            $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
//            $arr = reset($excel_array); //获取字段名
            unset($excel_array[0]); //删除字段名，剩下的都是存储到数据库的数据了！！
            if (empty($excel_array)) {
                throw new NothingMissException();
            }
            $Success_num = 0; //成功条数
            $Unusual_num = 0; //异常条数
            foreach ($excel_array as $k => $v) {
                //先查询此用户是否存在
                $where = [
                    'student_name' => $v[1],
                    'phone' => $v[2],
                ];

                $table = request()->controller();
                $lmport_student_data = Crud::getData($table, 1, $where, $field = 'id');
                if ($v[4] == '男') {
                    $sex = 1;
                } elseif ($v[4] == '女') {
                    $sex = 2;
                } else {
                    $sex = 3;
                }
                $add_data = [
                    'mem_id' => $mem_data['mem_id'],
                    'salesman_id' => $mem_data['user_id'],
                    'user_name' => $v[0],
                    'student_name' => $v[1],
                    'phone' => $v[2],
                    'user_relation' => $v[6], //关系
                    'create_time' => time(),
                    'sex' => $sex,
                    'year_age' => $v[3],
                    'birthday' => $v[5],
                ];
                if ($lmport_student_data) {
                    //查看本机构是否有 yx_lmport_student_member
                    $lmport_student_member_where = [
                        'mem_id' => $mem_data['mem_id'],
                        'student_id' => $lmport_student_data['id'],
                    ];
                    //本机构是否有此人员
                    $lmport_student_member = Crud::getData('lmport_student_member', 1, $lmport_student_member_where, $field = 'id');
                    if ($lmport_student_member) {//有就是异常数据
                        $add_info = Crud::setAdd('lmport_student_unusual', $add_data, 1);
                        if ($add_info) {
                            $Unusual_num++;
                        }
                    } else {//如果没有关系就直接添加关系
                        $lmport_student_member_data = [
                            'mem_id' => $mem_data['mem_id'],
                            'student_id' => $lmport_student_data['id'],
                        ];
                        $lmport_student_member_info = Crud::setAdd('lmport_student_member', $lmport_student_member_data, 1);
                        if ($lmport_student_member_info) {
                            $Success_num++;
                        }
                    }
                } else {

                }

                //用户信息导入
                Db::startTrans();
                $add_info = Crud::setAdd($table, $add_data, 2);
                if (!$add_info) {
                    Db::rollback();
                    throw new AddMissException();
                }

                //添加关系
                $lmport_student_member_data = [
                    'mem_id' => $mem_data['mem_id'],
                    'student_id' => $add_info,
                ];
                $lmport_student_member_info = Crud::setAdd('lmport_student_member', $lmport_student_member_data, 1);
                if (!$lmport_student_member_info) {
                    Db::rollback();
                    throw new AddMissException();
                } else {
                    Db::commit();
                    $Success_num++;
                }

            }
            $data_info = [
                'Success_num' => $Success_num,
                'Unusual_num' => $Unusual_num,
                'Sum_num' => $Success_num + $Unusual_num,
            ];
            return jsonResponseSuccess($data_info);
        }
    }

    public function LmportStudentLists()
    {
        $mem_data = self::isuserData();
        if (empty($mem_data)) {
            throw new ISUserMissException();
        }

        vendor('PHPExcelguide.Classes.PHPExcel.IOFactory'); //导入PHPExcel文件中的IOFactory.php类
        $file = request()->file('file');//获取文件，file是请求的参数名
        $info = $file->move(ROOT_PATH . 'public' . DS . 'excel');//move将文件移动到项目文件的xxx
        if ($info) {
            $excel_path = $info->getSaveName();  //获取上传文件名
//          $excel_suffix = $info->getExtension(); //获取上传文件后缀
            $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $excel_path;   //上传文件的地址
            $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
//            $arr = reset($excel_array); //获取字段名
            unset($excel_array[0]); //删除字段名，剩下的都是存储到数据库的数据了！！
            if (empty($excel_array)) {
                throw new NothingMissException();
            }
            $Success_num = 0; //成功条数
            $Unusual_num = 0; //异常条数
            foreach ($excel_array as $k => $v) {
                //先查询此用户是否存在
                $where = [
                    'student_name' => $v[1],
                    'phone' => $v[2],
                ];

                $table = request()->controller();
                $info = Crud::getData($table, 1, $where, $field = 'id');
                if ($info) {
                    //查看本机构是否有 yx_lmport_student_member
                    $lmport_student_member_where = [
                        'mem_id' => $mem_data['mem_id'],
                        'student_id' => $info['id'],
                    ];
                    $lmportStudentMember = Crud::getData('lmport_student_member', 1, $lmport_student_member_where, $field = 'id');
                    if ($lmportStudentMember) {
                        if ($v[4] == '男') {
                            $sex = 1;
                        } elseif ($v[4] == '女') {
                            $sex = 2;
                        } else {
                            $sex = 3;
                        }
                        $add_data = [
                            'mem_id' => $mem_data['mem_id'],
                            'salesman_id' => $mem_data['user_id'],
                            'user_name' => $v[0],
                            'student_name' => $v[1],
                            'phone' => $v[2],
                            'user_relation' => $v[6], //关系
                            'create_time' => time(),
                            'sex' => $sex,
                            'year_age' => $v[3],
                            'birthday' => $v[5],
                        ];

                        $add_info = Crud::setAdd('lmport_student_unusual', $add_data, 1);
                        if ($add_info) {
                            $Unusual_num++;
                        }
                    }
                }


                if ($v[4] == '男') {
                    $sex = 1;
                } elseif ($v[4] == '女') {
                    $sex = 2;
                } else {
                    $sex = 3;
                }
                $add_data = [
                    'mem_id' => $mem_data['mem_id'],
                    'salesman_id' => $mem_data['user_id'],
                    'user_name' => $v[0],
                    'student_name' => $v[1],
                    'phone' => $v[2],
                    'user_relation' => $v[6], //关系
                    'create_time' => time(),
                    'sex' => $sex,
                    'year_age' => $v[3],
                    'birthday' => $v[5],
                ];
                if ($info) {
                    //有值为用户已存(为异常用户)
                    //如果异常数据有此用户
//                    $unusual_data = Crud::getData('lmport_student_unusual', 1, $where, $field = 'id');
//                    if($unusual_data){
//                        $Unusual_num++;
//                    }else{
                    $add_info = Crud::setAdd('lmport_student_unusual', $add_data, 1);
                    if ($add_info) {
                        $Unusual_num++;
                    }
//                    }
                } else {
                    $add_info = Crud::setAdd($table, $add_data, 1);
                    if ($add_info) {
                        $Success_num++;
                    }
                }
            }
            $data_info = [
                'Success_num' => $Success_num,
                'Unusual_num' => $Unusual_num,
                'Sum_num' => $Success_num + $Unusual_num,
            ];
            return jsonResponseSuccess($data_info);
        }
    }

    //导出异常数据
    public function exportStudentList()
    {
        $mem_data = self::isuserData();
//        $start_time = 0;
//        $end_time = 0;
        $where = [
            'mem_id' => $mem_data['mem_id'],
//            'o.create_time' => ['between', [$start_time, $end_time]]
        ];

        $table = request()->controller();
        $list = Crud::getData($table, 2, $where, $field = 'user_name,student_name,phone,year_age,sex,birthday,user_relation', 'create_time desc', 1, 100000);
        if (empty($list)) {
            throw new NothingMissException();
        }

        vendor('PHPExcel.Classes.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '家长名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '学生姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '学生年龄');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '学生姓别');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '学生出生日期');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '关系');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '时间');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);

        //设置单元格为文本
        foreach ($list as $k => $val) {
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $val['user_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $val['student_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $val['phone']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $val['year_age']);
            if ($val['sex'] == 1) {
                $sex = '男';
            } else if ($val['sex'] == 2) {
                $sex = '女';
            } else {
                $sex = '未知';
            }
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $sex);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $val['birthday']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $val['user_relation']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date('Y-m-d H:i:s', $val['create_time']));
        }
        // 1.保存至本地Excel表格
        $objWriter->save('xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=异常数据.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    //验证用户是否进入平台
    public static function isStudent($student_id)
    {
        //判断此学生是否进入此平台
        $lmport_student_where = [
            'id' => $student_id,
            'is_del' => 1,
        ];
        $table = request()->controller();
        $lmport_student_data = Crud::getData($table, 1, $lmport_student_where, 'student_id');
        if (empty($lmport_student_data) && empty($lmport_student_data['student_id'])) {
            return 2000;
        }
        $student_where = [
            'id' => $lmport_student_data['student_id'],
            'is_del' => 1,
        ];
        $student_data = Crud::getData('student', 1, $student_where, 'id');
        if ($student_data) {
            return $student_data['id'];
        } else {
            return 2000;
        }
    }


}