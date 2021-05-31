<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/1 0001
 * Time: 13:42
 */

namespace app\jg\controller\v2;


use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\EditRecoMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Db;

class LmportStudent extends BaseController
{
    //学生列表
    public static function getLmportStudentList($student_status = '', $student_name = '', $mem_id = '', $time = '', $del_type = 1, $page = 1, $pageSize = 8, $type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2 || $mem_data['type'] == 7) {
            $where = [
                'm.status' => 1,
                'm.is_del' => 1,
                'lsm.is_del' => $del_type,
//                'lm.is_del' => 1,
            ];
        } else {
            throw new ISUserMissException();
        }
        if (empty($mem_id)) {
            $where['lsm.mem_id'] = $mem_data['mem_id'];
        }else{
            $where['lsm.mem_id'] = $mem_id;
        }
        (isset($student_id) && !empty($student_id)) && $where['lsm.student_id'] = $student_id;
        (isset($student_id) && !empty($student_id)) && $where['lsm.student_status'] = $student_status; //1公海池，2业务员列表,3在读学员
        (isset($mem_data['user_id']) && !empty($mem_data['user_id'])) && $where['lsm.salesman_id'] = $mem_data['user_id'];

        //名称搜索 手机号
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lsm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lsm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lsm.mem_id'] = $mem_id;
        (isset($return_visit_type) && !empty($return_visit_type)) && $where['lsm.return_type'] = $return_visit_type;
        //时间筛选
        if (isset($time) && !empty($time)) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where['lm.create_time'] = ['between', [$start_time, $end_time]];
        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            //['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lsm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
//        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.mem_id,lsm.student_id,lsm.student_name,lsm.salesman_id,lsm.student_type,lsm.customer_type,lsm.customer_notes,lsm.student_status,lsm.is_audition,lsm.get_into_time,lsm.sex,lsm.birthday,lsm.id_card,lsm.return_visit_id,lsm.student_identifier,lsm.province,lsm.city,lsm.area,lsm.address,lsm.community,lsm.school,lsm.class,lsm.introducer,lsm.return_type,lsm.return_visit_content,lsm.province_num,lsm.city_num,lsm.area_num,lsm.create_time,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id', $page, $pageSize);
        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.*,m.cname,m.province mprovince,m.area marea,m.city mcity,m.address msaddress,sa.user_name salesman_name', $page, $pageSize);
        foreach ($info as $k => $v) {
            $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
            $info[$k]['mlocation'] = $v['mprovince'] . $v['mcity'] . $v['marea'];
            $info[$k]['saddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
            $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $info[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
            $info[$k]['del_time'] = date('Y-m-d H:i:s',$v['update_time']);
            if (empty($v['salesman_name'])) {
                $info[$k]['salesman_name'] = '-';
            }

            if (empty($v['return_type'])) {
                $info[$k]['return_type'] = '-';
            }

            //  student_type 1潜在学员，2试听学员，3签约学员，4结业学员，5刚导入学生
            if ($v['student_type'] == 1) {
                $info[$k]['student_type_name'] = '潜在学员';
            } elseif ($v['student_type'] == 2) {
                $info[$k]['student_type_name'] = '试听学员';
            } elseif ($v['student_type'] == 3) {
                $info[$k]['student_type_name'] = '签约学员';
            } elseif ($v['student_type'] == 4) {
                $info[$k]['student_type_name'] = '结业学员';
            } elseif ($v['student_type'] == 5) {
                $info[$k]['student_type_name'] = '刚导入学生';
            }
            //年龄计算
            if (!empty($v['birthday'])) {
                $info[$k]['year_age'] = CalculationAge($v['birthday']);
            }

            if (!empty($v['phone'])) {
                $info[$k]['phone_look'] = '查看';
            }

            if ($v['is_audition'] == 1) {
                $info[$k]['is_audition'] = '试听';
            } elseif ($v['is_audition'] == 2) {
                $info[$k]['is_audition'] = '未试听';
            }

            //  customer_type 1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
            if ($v['customer_type'] == 1) {
                $info[$k]['customer_source'] = '线下活动';
            } elseif ($v['customer_type'] == 2) {
                $info[$k]['customer_source'] = '转介绍';
            } elseif ($v['customer_type'] == 3) {
                $info[$k]['customer_source'] = '自主上门';
            } elseif ($v['customer_type'] == 4) {
                $info[$k]['customer_source'] = '网络平台';
            } elseif ($v['customer_type'] == 5) {
                $info[$k]['customer_source'] = '其他渠道';
            } else {
                $info[$k]['customer_source'] = '-';
            }
            if ($v['sex'] == 1) {
                $info[$k]['sex_name'] = '男';
            } else if ($v['sex'] == 2) {
                $info[$k]['sex_name'] = '女';
            }

            //最新回访状态  1.未跟进，2后续联系，3无意向，4感兴趣，5有意向，6到访，7试听
            if (!empty($v['return_type'])) {
                if ($v['return_type'] == 1) {
                    $info[$k]['return_visit_type_name'] = '未跟进';
                } elseif ($v['return_type'] == 2) {
                    $info[$k]['return_visit_type_name'] = '后续联系';
                } elseif ($v['return_type'] == 3) {
                    $info[$k]['return_visit_type_name'] = '无意向';
                } elseif ($v['return_type'] == 4) {
                    $info[$k]['return_visit_type_name'] = '感兴趣';
                } elseif ($v['return_type'] == 5) {
                    $info[$k]['return_visit_type_name'] = '有意向';
                } elseif ($v['return_type'] == 6) {
                    $info[$k]['return_visit_type_name'] = '到访';
                } elseif ($v['return_type'] == 7) {
                    $info[$k]['return_visit_type_name'] = '试听';
                } else {
                    $info[$k]['return_visit_type_name'] = '-';
                }
            } else {
                $info[$k]['return_visit_type_name'] = '-';
            }
            //最新回访信息
            if (empty($v['return_visit_content'])) {
                $info[$k]['return_visit_content'] = '-';
            }
        }
        if (!$info) {
            throw new NothingMissException();
        } else {
            if ($type == 2) {
                return $info;
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lm.id', 'lm.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //展示公海池与学生
    public static function getLmportStudent($student_name = '', $mem_id = '', $time = '', $page = 1, $pageSize = 8, $type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2 || $mem_data['type'] == 7) {
            $where = [
                'm.status' => 1,
                'm.is_del' => 1,
                'lsm.student_status' => 1, //1公海池，2业务员列表
                'lsm.is_del' => 1,
                'lm.is_del' => 1,
                'lsm.mem_id' => $mem_data['mem_id'],
            ];
        } else {
            throw new ISUserMissException();
        }
        (isset($student_id) && !empty($student_id)) && $where['lsm.student_id'] = $student_id;
        (isset($mem_data['user_id']) && !empty($mem_data['user_id'])) && $where['lm.salesman_id'] = $mem_data['user_id'];

        //名称搜索 手机号
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lsm.mem_id'] = $mem_id;
        (isset($return_visit_type) && !empty($return_visit_type)) && $where['lsm.return_type'] = $return_visit_type;
        //时间筛选
        if (isset($time) && !empty($time)) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where['lm.create_time'] = ['between', [$start_time, $end_time]];
        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            ['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
//        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.mem_id,lsm.student_id,lsm.student_name,lsm.salesman_id,lsm.student_type,lsm.customer_type,lsm.customer_notes,lsm.student_status,lsm.is_audition,lsm.get_into_time,lsm.sex,lsm.birthday,lsm.id_card,lsm.return_visit_id,lsm.student_identifier,lsm.province,lsm.city,lsm.area,lsm.address,lsm.community,lsm.school,lsm.class,lsm.introducer,lsm.return_type,lsm.return_visit_content,lsm.province_num,lsm.city_num,lsm.area_num,lsm.create_time,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id', $page, $pageSize);
        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.*,m.cname,m.province mprovince,m.area marea,m.city mcity,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id student_id', $page, $pageSize);
        foreach ($info as $k => $v) {
            $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
            $info[$k]['mlocation'] = $v['mprovince'] . $v['mcity'] . $v['marea'];
            $info[$k]['saddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
            $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $info[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
            if (empty($v['salesman_name'])) {
                $info[$k]['salesman_name'] = '-';
            }

            if (empty($v['return_type'])) {
                $info[$k]['return_type'] = '-';
            }

            //  student_type 1潜在学员，2试听学员，3签约学员，4结业学员，5刚导入学生
            if ($v['student_type'] == 1) {
                $info[$k]['student_type_name'] = '潜在学员';
            } elseif ($v['student_type'] == 2) {
                $info[$k]['student_type_name'] = '试听学员';
            } elseif ($v['student_type'] == 3) {
                $info[$k]['student_type_name'] = '签约学员';
            } elseif ($v['student_type'] == 4) {
                $info[$k]['student_type_name'] = '结业学员';
            } elseif ($v['student_type'] == 5) {
                $info[$k]['student_type_name'] = '刚导入学生';
            }
            //年龄计算
            if (!empty($v['birthday'])) {
                $info[$k]['year_age'] = CalculationAge($v['birthday']);
            }

            if (!empty($v['phone'])) {
                $info[$k]['phone_look'] = '查看';
            }

            if ($v['is_audition'] == 1) {
                $info[$k]['is_audition'] = '试听';
            } elseif ($v['is_audition'] == 2) {
                $info[$k]['is_audition'] = '未试听';
            }

            //  customer_type 1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
            if ($v['customer_type'] == 1) {
                $info[$k]['customer_source'] = '线下活动';
            } elseif ($v['customer_type'] == 2) {
                $info[$k]['customer_source'] = '转介绍';
            } elseif ($v['customer_type'] == 3) {
                $info[$k]['customer_source'] = '自主上门';
            } elseif ($v['customer_type'] == 4) {
                $info[$k]['customer_source'] = '网络平台';
            } elseif ($v['customer_type'] == 5) {
                $info[$k]['customer_source'] = '其他渠道';
            } else {
                $info[$k]['customer_source'] = '-';
            }
            if ($v['sex'] == 1) {
                $info[$k]['sex_name'] = '男';
            } else if ($v['sex'] == 2) {
                $info[$k]['sex_name'] = '女';
            }

            //最新回访状态  1.未跟进，2后续联系，3无意向，4感兴趣，5有意向，6到访，7试听
            if (!empty($v['return_type'])) {
                if ($v['return_type'] == 1) {
                    $info[$k]['return_visit_type_name'] = '未跟进';
                } elseif ($v['return_type'] == 2) {
                    $info[$k]['return_visit_type_name'] = '后续联系';
                } elseif ($v['return_type'] == 3) {
                    $info[$k]['return_visit_type_name'] = '无意向';
                } elseif ($v['return_type'] == 4) {
                    $info[$k]['return_visit_type_name'] = '感兴趣';
                } elseif ($v['return_type'] == 5) {
                    $info[$k]['return_visit_type_name'] = '有意向';
                } elseif ($v['return_type'] == 6) {
                    $info[$k]['return_visit_type_name'] = '到访';
                } elseif ($v['return_type'] == 7) {
                    $info[$k]['return_visit_type_name'] = '试听';
                } else {
                    $info[$k]['return_visit_type_name'] = '-';
                }
            } else {
                $info[$k]['return_visit_type_name'] = '-';
            }
            //最新回访信息
            if (empty($v['return_visit_content'])) {
                $info[$k]['return_visit_content'] = '-';
            }
        }
        if (!$info) {
            throw new NothingMissException();
        } else {
            if ($type == 2) {
                return $info;
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lm.id', 'lm.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //公海池展示(字段)
    public static function getLmportStudentField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员姓名', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone_look', 'name' => '手机号', 'width' => '160', 'state' => '1'],
//            ['prop' => 'salesman_name', 'name' => '业务员', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '100', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'get_into_time', 'name' => '入池时间', 'width' => '', 'state' => ''],
            ['prop' => 'create_time', 'name' => '添加时间', 'width' => '', 'state' => ''],
            ['prop' => 'return_visit_content', 'name' => '回访信息', 'width' => '210', 'state' => ''],

        ];
        return jsonResponseSuccess($data);
    }

    //撤回公海池
    public static function withdrawLmportStudentList($page = 1)
    {
        $mem_data = self::isuserData();
//        $where = [];
        //名称搜索
        if ($mem_data['type'] == 2) {
            $where = [
                'lsm.mem_id' => $mem_data['mem_id'],
                'lm.type' => 1, //1公海池，2业务员列表
                'lm.is_del' => 2,
//                'm.is_del' => 1,
//                'm.status' => 1,
                'lsm.is_del' => 1,
            ];
        } elseif ($mem_data['type'] == 7) {
            $where = [
                'lsm.mem_id' => $mem_data['mem_id'],
                'lm.salesman_id' => $mem_data['user_id'],
                'lm.is_del' => 2,
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
        if (isset($time) && !empty($time)) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where['lm.create_time'] = ['between', [$start_time, $end_time]];
        }
        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            ['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
        $info = Crud::getRelationDataWhereOr($table, $type = 2, $where, $whereOr, $join, $alias, '', 'lm.*,m.cname,sa.user_name salesman_name', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['caddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $info[$k]['get_into_time'] = date('Y-m-d H:i:s', $v['get_into_time']);
                $info[$k]['phone_look'] = '查看';
                $info[$k]['del_time'] = $v['update_time'];
                if ($v['sex'] == 1) {
                    $info[$k]['sex_name'] = '男';
                } else if ($v['sex'] == 2) {
                    $info[$k]['sex_name'] = '女';
                }
                if (!empty($v['phone'])) {
                    $info[$k]['phone_look'] = '查看';
                }
                //获取回访最后一条
                $where_visit = [
                    'lmport_student_id' => $v['id']
                ];
                $salesman_type = Crud::getData('return_visit', 3, $where_visit, 'content', 'id desc');
                if ($salesman_type) {
                    $info[$k]['return_visit_content'] = $salesman_type['content'];
                } else {
                    $info[$k]['return_visit_content'] = '-';
                }
            }


            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lms.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //撤回公海池字段
    public static function getwithdrawLmportStudentField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员姓名', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone_look', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'salesman_name', 'name' => '业务员', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '100', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'del_time', 'name' => '删除时间', 'width' => '', 'state' => ''],

        ];
        return jsonResponseSuccess($data);
    }

    //撤回公海池操作
    public static function withdrawLmportStudent($student_member_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_member_id]
        ];

        $table = 'lmport_student_member';
        $lmport_student_data = Crud::setUpdate($table, $where, ['is_del' => 1, 'update_time' => time()]);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取潜在学员库信息
    public static function getOwnLmportStudent($student_id = '', $student_name = '', $mem_id = '', $time = '', $return_visit_type = '', $page = 1, $pageSize = 16, $type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2 || $mem_data['type'] == 7) {
            $where = [
                'm.status' => 1,
                'm.is_del' => 1,
                'lsm.student_status' => 2, //1公海池，2业务员列表
                'lsm.is_del' => 1,
                'lm.is_del' => 1,
                'lsm.mem_id' => $mem_data['mem_id'],
            ];
        } else {
            throw new ISUserMissException();
        }
        (isset($student_id) && !empty($student_id)) && $where['lsm.student_id'] = $student_id;
        (isset($mem_data['user_id']) && !empty($mem_data['user_id'])) && $where['lsm.salesman_id'] = $mem_data['user_id'];

        //名称搜索 手机号
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lsm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lsm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lsm.mem_id'] = $mem_id;
        (isset($return_visit_type) && !empty($return_visit_type)) && $where['lsm.return_type'] = $return_visit_type;
        //时间筛选
        if (isset($time) && !empty($time)) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where['lsm.create_time'] = ['between', [$start_time, $end_time]];
        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            ['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
//        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.mem_id,lsm.student_id,lsm.student_name,lsm.salesman_id,lsm.student_type,lsm.customer_type,lsm.customer_notes,lsm.student_status,lsm.is_audition,lsm.get_into_time,lsm.sex,lsm.birthday,lsm.id_card,lsm.return_visit_id,lsm.student_identifier,lsm.province,lsm.city,lsm.area,lsm.address,lsm.community,lsm.school,lsm.class,lsm.introducer,lsm.return_type,lsm.return_visit_content,lsm.province_num,lsm.city_num,lsm.area_num,lsm.create_time,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id', $page, $pageSize);
        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.*,m.cname,m.province mprovince,m.area marea,m.city mcity,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id student_id', $page, $pageSize);
        foreach ($info as $k => $v) {
            $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
            $info[$k]['mlocation'] = $v['mprovince'] . $v['mcity'] . $v['marea'];
            $info[$k]['saddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
            $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $info[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
            if (empty($v['salesman_name'])) {
                $info[$k]['salesman_name'] = '-';
            }

            if (empty($v['return_type'])) {
                $info[$k]['return_type'] = '-';
            }

            //  student_type 1潜在学员，2试听学员，3签约学员，4结业学员，5刚导入学生
            if ($v['student_type'] == 1) {
                $info[$k]['student_type_name'] = '潜在学员';
            } elseif ($v['student_type'] == 2) {
                $info[$k]['student_type_name'] = '试听学员';
            } elseif ($v['student_type'] == 3) {
                $info[$k]['student_type_name'] = '签约学员';
            } elseif ($v['student_type'] == 4) {
                $info[$k]['student_type_name'] = '结业学员';
            } elseif ($v['student_type'] == 5) {
                $info[$k]['student_type_name'] = '刚导入学生';
            }
            //年龄计算
            if (!empty($v['birthday'])) {
                $info[$k]['year_age'] = CalculationAge($v['birthday']);
            }

            if (!empty($v['phone'])) {
                $info[$k]['phone_look'] = '查看';
            }

            if ($v['is_audition'] == 1) {
                $info[$k]['is_audition'] = '试听';
            } elseif ($v['is_audition'] == 2) {
                $info[$k]['is_audition'] = '未试听';
            }

            //  customer_type 1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
            if ($v['customer_type'] == 1) {
                $info[$k]['customer_source'] = '线下活动';
            } elseif ($v['customer_type'] == 2) {
                $info[$k]['customer_source'] = '转介绍';
            } elseif ($v['customer_type'] == 3) {
                $info[$k]['customer_source'] = '自主上门';
            } elseif ($v['customer_type'] == 4) {
                $info[$k]['customer_source'] = '网络平台';
            } elseif ($v['customer_type'] == 5) {
                $info[$k]['customer_source'] = '其他渠道';
            } else {
                $info[$k]['customer_source'] = '-';
            }
            if ($v['sex'] == 1) {
                $info[$k]['sex_name'] = '男';
            } else if ($v['sex'] == 2) {
                $info[$k]['sex_name'] = '女';
            }

            //最新回访状态  1.未跟进，2后续联系，3无意向，4感兴趣，5有意向，6到访，7试听
            if (!empty($v['return_type'])) {
                if ($v['return_type'] == 1) {
                    $info[$k]['return_visit_type_name'] = '未跟进';
                } elseif ($v['return_type'] == 2) {
                    $info[$k]['return_visit_type_name'] = '后续联系';
                } elseif ($v['return_type'] == 3) {
                    $info[$k]['return_visit_type_name'] = '无意向';
                } elseif ($v['return_type'] == 4) {
                    $info[$k]['return_visit_type_name'] = '感兴趣';
                } elseif ($v['return_type'] == 5) {
                    $info[$k]['return_visit_type_name'] = '有意向';
                } elseif ($v['return_type'] == 6) {
                    $info[$k]['return_visit_type_name'] = '到访';
                } elseif ($v['return_type'] == 7) {
                    $info[$k]['return_visit_type_name'] = '试听';
                } else {
                    $info[$k]['return_visit_type_name'] = '-';
                }
            } else {
                $info[$k]['return_visit_type_name'] = '-';
            }
            //最新回访信息
            if (empty($v['return_visit_content'])) {
                $info[$k]['return_visit_content'] = '-';
            }
        }
        if (!$info) {
            throw new NothingMissException();
        } else {
            if ($type == 2) {
                return $info;
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lm.id', 'lm.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取潜在学员库信息（字段）
    public static function getOwnLmportStudentField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员姓名', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone_look', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'salesman_name', 'name' => '业务员', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '100', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'customer_source', 'name' => '客户来源', 'width' => '', 'state' => ''],
            ['prop' => 'return_visit_type_name', 'name' => '回访状态标签', 'width' => '', 'state' => ''],
            ['prop' => 'is_audition', 'name' => '是否试听', 'width' => '', 'state' => ''],
            ['prop' => 'create_time', 'name' => '添加时间', 'width' => '', 'state' => ''],
            ['prop' => 'return_visit_content', 'name' => '回访信息', 'width' => '210', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //在读学员数据
    public static function getStayStudent($student_name = '', $mem_id = '', $time = '', $page = 1, $pageSize = 16, $type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2 || $mem_data['type'] == 7) {
            $where = [
                'm.status' => 1,
                'm.is_del' => 1,
                'lsm.student_status' => 3, //1公海池，2业务员列表
                'lsm.is_del' => 1,
                'lm.is_del' => 1,
                'lsm.mem_id' => $mem_data['mem_id'],
            ];
        } else {
            throw new ISUserMissException();
        }
        (isset($student_id) && !empty($student_id)) && $where['lsm.student_id'] = $student_id;
        (isset($mem_data['user_id']) && !empty($mem_data['user_id'])) && $where['lm.salesman_id'] = $mem_data['user_id'];

        //名称搜索 手机号
        $whereOr = [];
        (isset($student_name) && !empty($student_name)) && $where['lm.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($student_name) && !empty($student_name)) && $whereOr['lm.phone'] = $student_name;
        (isset($mem_id) && !empty($mem_id)) && $where['lsm.mem_id'] = $mem_id;
        (isset($return_visit_type) && !empty($return_visit_type)) && $where['lsm.return_type'] = $return_visit_type;
        //时间筛选
        if (isset($time) && !empty($time)) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where['lm.create_time'] = ['between', [$start_time, $end_time]];
        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            ['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
//        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.mem_id,lsm.student_id,lsm.student_name,lsm.salesman_id,lsm.student_type,lsm.customer_type,lsm.customer_notes,lsm.student_status,lsm.is_audition,lsm.get_into_time,lsm.sex,lsm.birthday,lsm.id_card,lsm.return_visit_id,lsm.student_identifier,lsm.province,lsm.city,lsm.area,lsm.address,lsm.community,lsm.school,lsm.class,lsm.introducer,lsm.return_type,lsm.return_visit_content,lsm.province_num,lsm.city_num,lsm.area_num,lsm.create_time,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id', $page, $pageSize);
        $info = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.*,m.cname,m.province mprovince,m.area marea,m.city mcity,m.address msaddress,sa.user_name salesman_name,lm.phone,lm.id student_id', $page, $pageSize);
        foreach ($info as $k => $v) {
            $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
            $info[$k]['mlocation'] = $v['mprovince'] . $v['mcity'] . $v['marea'];
            $info[$k]['saddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
            $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $info[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
            if (empty($v['salesman_name'])) {
                $info[$k]['salesman_name'] = '-';
            }

            if (empty($v['return_type'])) {
                $info[$k]['return_type'] = '-';
            }

            //  student_type 1潜在学员，2试听学员，3签约学员，4结业学员，5刚导入学生
            if ($v['student_type'] == 1) {
                $info[$k]['student_type_name'] = '潜在学员';
            } elseif ($v['student_type'] == 2) {
                $info[$k]['student_type_name'] = '试听学员';
            } elseif ($v['student_type'] == 3) {
                $info[$k]['student_type_name'] = '签约学员';
            } elseif ($v['student_type'] == 4) {
                $info[$k]['student_type_name'] = '结业学员';
            } elseif ($v['student_type'] == 5) {
                $info[$k]['student_type_name'] = '刚导入学生';
            }
            //年龄计算
            if (!empty($v['birthday'])) {
                $info[$k]['year_age'] = CalculationAge($v['birthday']);
            }

            if (!empty($v['phone'])) {
                $info[$k]['phone_look'] = '查看';
            }

            if ($v['is_audition'] == 1) {
                $info[$k]['is_audition'] = '试听';
            } elseif ($v['is_audition'] == 2) {
                $info[$k]['is_audition'] = '未试听';
            }

            //  customer_type 1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
            if ($v['customer_type'] == 1) {
                $info[$k]['customer_source'] = '线下活动';
            } elseif ($v['customer_type'] == 2) {
                $info[$k]['customer_source'] = '转介绍';
            } elseif ($v['customer_type'] == 3) {
                $info[$k]['customer_source'] = '自主上门';
            } elseif ($v['customer_type'] == 4) {
                $info[$k]['customer_source'] = '网络平台';
            } elseif ($v['customer_type'] == 5) {
                $info[$k]['customer_source'] = '其他渠道';
            } else {
                $info[$k]['customer_source'] = '-';
            }
            if ($v['sex'] == 1) {
                $info[$k]['sex_name'] = '男';
            } else if ($v['sex'] == 2) {
                $info[$k]['sex_name'] = '女';
            }

            //最新回访状态  1.未跟进，2后续联系，3无意向，4感兴趣，5有意向，6到访，7试听
            if (!empty($v['return_type'])) {
                if ($v['return_type'] == 1) {
                    $info[$k]['return_visit_type_name'] = '未跟进';
                } elseif ($v['return_type'] == 2) {
                    $info[$k]['return_visit_type_name'] = '后续联系';
                } elseif ($v['return_type'] == 3) {
                    $info[$k]['return_visit_type_name'] = '无意向';
                } elseif ($v['return_type'] == 4) {
                    $info[$k]['return_visit_type_name'] = '感兴趣';
                } elseif ($v['return_type'] == 5) {
                    $info[$k]['return_visit_type_name'] = '有意向';
                } elseif ($v['return_type'] == 6) {
                    $info[$k]['return_visit_type_name'] = '到访';
                } elseif ($v['return_type'] == 7) {
                    $info[$k]['return_visit_type_name'] = '试听';
                } else {
                    $info[$k]['return_visit_type_name'] = '-';
                }
            } else {
                $info[$k]['return_visit_type_name'] = '-';
            }
            //最新回访信息
            if (empty($v['return_visit_content'])) {
                $info[$k]['return_visit_content'] = '-';
            }
        }
        if (!$info) {
            throw new NothingMissException();
        } else {
            if ($type == 2) {
                return $info;
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, 'lm.id', 'lm.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //在读学员字段
    public static function getStayStudentField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员姓名', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone_look', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'salesman_name', 'name' => '业务员', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '100', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'create_time', 'name' => '添加时间', 'width' => '', 'state' => ''],
            ['prop' => 'return_visit_content', 'name' => '回访信息', 'width' => '210', 'state' => ''],

        ];
        return jsonResponseSuccess($data);
    }

    //获取用户详细信息加字段
    public static function getLmportStudentdetailsField($student_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            //获取用户信息
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


        } else {
            throw new ISUserMissException();
        }

    }

    //添加学生数据(单个用户提交)
    //mem_id 机构ID user_name 用户名称 student_type 学生状态 sex 性别  birthday 生日 introducer 介绍人 customer_type user_relation 关系
    public static function addLmportStudent()
    {
        $account_data = self::isuserData();
        $data = input();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $data['salesman_id'] = $account_data['user_id'];

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
        $data_student = [
            'phone' => $data['phone'],
            'student_name' => $data['student_name'],
            'sex' => $data['sex'],
            'birthday' => $data['birthday'],
        ];
        $student_id = Crud::setAdd($table, $data_student, 2);
        if (!$student_id) {
            Db::rollback();
            throw new AddMissException();
        }
        //多机构绑定关系
        $data['student_id'] = $student_id;
        $data['student_identifier'] = $order_num = time() . rand(10, 99);
        $student_member_id_id = Crud::setAdd('lmport_student_member', $data, 2);
        if (!$student_member_id_id) {
            Db::rollback();
            throw new AddMissException();
        }
        //添加家长
        $parent_data = [
            'parent_name' => $data['user_name'],
            'phone' => $data['phone'],
            'student_id' => $student_id,
            'mem_id' => $account_data['mem_id'],
            'user_relation' => $data['user_relation'],
            'student_member_id' => $student_member_id_id,
        ];
        $parent_id = Crud::setAdd('parent', $parent_data, 2);
        if (!$parent_id) {
            Db::rollback();
            throw new AddMissException();
        }
        //展示有关系 yx_student_parent_relation
        $parent_relation_data = [
            'student_id' => $student_id,
            'parent_id' => $parent_id,
            'user_relation' => $data['user_relation'],
            'mem_id' => $data['mem_id'],
            'student_member_id' => $student_member_id_id,
        ];
        $parent_relation_info = Crud::setAdd('student_parent_relation', $parent_relation_data);
        if (!$parent_relation_info) {
            Db::rollback();
            throw new AddMissException();
        } else {
            Db::commit();
            return jsonResponseSuccess($student_id);
        }
    }

    //潜在学院移到公海池
    public static function moveLmportStudent($student_member_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_member_id]
        ];
        $updata = [
            'get_into_time' => time(),
            'student_status' => 1, //1公海池，2业务员列表
        ];
        $lmport_student_data = Crud::setUpdate('lmport_student_member', $where, $updata);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //公海池移到潜在学院
    public static function moveOwnLmportStudent($student_member_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_member_id]
        ];
        $updata = [
            'get_into_time' => time(),
            'student_status' => 2, //1公海池，2业务员列表
        ];
        $lmport_student_data = Crud::setUpdate('lmport_student_member', $where, $updata);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //删除学生
    public static function delOwnLmportStudent($student_member_id)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'id' => ['in', $student_member_id]
        ];
        $lmport_student_data = Crud::setUpdate('lmport_student_member', $where, ['is_del' => 2, 'update_time' => time()]);
        if ($lmport_student_data) {
            return jsonResponseSuccess($lmport_student_data);
        } else {
            throw new UpdateMissException();
        }
    }


    //修改用户身份信息(学员信息)  student_name
    public static function editLmportStudentInfo()
    {
        $data = input();
        $account_data = self::isuserData();

        $data['update_time'] = time();
        if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
            $data['province_num'] = $data['address_code'][0];
            $data['city_num'] = $data['address_code'][1];
            $data['area_num'] = $data['address_code'][2];
        }
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'mem_id' => $data['mem_id'],
            'id' => $data['student_member_id'],
            'is_del' => 1,
        ];
        //用户没进入平台，或在平台没有购买课程
        $lmport_student_info = Crud::setUpdate('lmport_student_member', $where, $data);
        if ($lmport_student_info) {
            $StudentInfo = self::getOwnLmportStudent($data['student_id'], '', '', '', '', '', '', 2);
//            return $StudentInfo;
            return jsonResponseSuccess($StudentInfo);
        } else {
            throw new UpdateMissException();
        }

    }

    //展示家长信息
    public static function getLmportStudentParent()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'spr.student_id' => $data['student_id'],
                'spr.is_del' => 1,
                'p.is_del' => 1,
                'p.mem_id' => $account_data['mem_id'],
                'spr.type' => 1,
            ];
            $join = [
                ['yx_parent p', 'spr.parent_id = p.id', 'left'], //机构信息
            ];
            $alias = 'spr';
            $parent_data = Crud::getRelationData('student_parent_relation', $type = 2, $where, $join, $alias, '', 'p.*,spr.user_relation,spr.student_id', 1, 1000);
            if (!$parent_data) {
                throw new NothingMissException();
            } else {
                foreach ($parent_data as $k => $v) {
                    if ($v['user_relation'] == 1) {
                        $parent_data[$k]['parent_student_relation_name'] = '爸爸';
                    } elseif ($v['user_relation'] == 2) {
                        $parent_data[$k]['parent_student_relation_name'] = '妈妈';
                    } elseif ($v['user_relation'] == 3) {
                        $parent_data[$k]['parent_student_relation_name'] = '爷爷/外公';
                    } elseif ($v['user_relation'] == 4) {
                        $parent_data[$k]['parent_student_relation_name'] = '奶奶/外婆';
                    } elseif ($v['user_relation'] == 5) {
                        $parent_data[$k]['parent_student_relation_name'] = '其他';
                    }
                }
                return jsonResponseSuccess($parent_data);
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //添加学长信息及关系和修改
    public static function addLmportStudentParent()
    {
        $data = input();
        if (!is_array($data)) {
            return jsonResponse('3000', '数据有误，请重新添加');
        }
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {

            foreach ($data['parent_data'] as $k => $v) {
                if (isset($v['id']) && !empty($v['id'])) {
                    self::delLmportStudentParent($data['student_id'], 2);
                    //如果有家长ID，将删除家长表和关系表信息
                }
                $parent_data = [
                    'parent_name' => $v['parent_name'],
                    'user_relation' => $v['user_relation'],
                    'student_id' => $data['student_id'],
                    'mem_id' => $account_data['mem_id'],
                    'phone' => $v['phone'],
                ];
                if (isset($v['we_chat']) && !empty($v['we_chat'])) {
                    $parent_data['we_chat'] = $v['we_chat'];
                }
                if (isset($v['qq']) && !empty($v['qq'])) {
                    $parent_data['qq'] = $v['qq'];
                }
                if (isset($v['email']) && !empty($v['email'])) {
                    $parent_data['email'] = $v['email'];
                }
                if (isset($v['company']) && !empty($v['company'])) {
                    $parent_data['company'] = $v['company'];
                }
                //添加家长信息
                $parent_id = Crud::setAdd('parent', $parent_data, 2);
                if (!$parent_id) {
                    throw new AddMissException();
                }
                //添加关系  student_parent_relation
                $parent_relation_where = [
                    'student_id' => $data['student_id'],
                    'mem_id' => $account_data['mem_id'],
                    'parent_id' => $parent_id,
                    'user_relation' => $v['user_relation'],
                ];
                $parent_relation_data = Crud::setAdd('student_parent_relation', $parent_relation_where);
            }
            if (!$parent_relation_data) {
                throw new AddMissException();
            } else {
                return jsonResponseSuccess($parent_relation_data);
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //修改家长信息
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

    //删除家长信息及关系
    public static function delLmportStudentParent($student_id, $type = 1)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $delStudentParent = Crud::setUpdate('parent', ['student_id' => $student_id, 'mem_id' => $account_data['mem_id']], ['is_del' => 2, 'update_time' => time()]);
            if (!$delStudentParent) {
                throw  new  UpdateMissException();
            }
            $where = [
                'student_id' => $student_id,
                'mem_id' => $account_data['mem_id']
            ];
            $student_parent_relation = Crud::setUpdate('student_parent_relation', $where, ['is_del' => 2, 'update_time' => time()]);
            if (!$student_parent_relation) {
                throw new UpdateMissException();
            } else {
                if ($type != 1) {
                    return $student_parent_relation;
                }
                return jsonResponseSuccess($student_parent_relation);
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //添加回访记录 yx_return_visit
    public static function addReturnVisit()
    {
        $data = input();
        $mem_data = self::isuserData();
        $data['salesman_id'] = $mem_data['user_id'];
        //学生数据添加最新的一条 yx_lmport_student
        $updata_data = Crud::setUpdate('lmport_student_member', ['id' => $data['student_member_id']], ['return_type' => $data['type'], 'return_visit_content' => $data['content']]);
        if ($updata_data) {
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $mem_data['mem_id'];
            }
            $info = Crud::setAdd('return_visit', $data);
            if (!$info) {
                throw new AddMissException();
            } else {
                return jsonResponseSuccess($info);
            }
        } else {
            throw new UpdateMissException();
        }
    }

    //获取回访记录 这个接口多地方访问所要传多值
    public static function getReturnVisit()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input();
            if (!isset($data['mem_id']) && empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            $where = [
                'rv.is_del' => 1,
                'rv.student_id' => $data['student_id'],
                'rv.mem_id' => $data['mem_id'],
            ];
            (isset($account_data['user_id']) && !empty($account_data['user_id'])) && $where['rv.salesman_id'] = $account_data['user_id'];
            $join = [
                ['yx_salesman sa', 'rv.salesman_id = sa.id', 'left'], //业务员信息
            ];
            $alias = 'rv';
            $info = Crud::getRelationData('return_visit', $type = 2, $where, $join, $alias, 'create_time desc', 'rv.type,rv.content,rv.create_time,sa.user_name salesman_name', $data['page'], $data['pageSize']);
            if (!$info) {
                throw new NothingMissException();
            } else { //returnVisitTypeName
                foreach ($info as $k => $v) {
                    $info[$k]['returnVisitTypeName'] = returnVisitTypeName($v['type']);
                    $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
//                $info[$k][$v] = isemptydata($v);
                }
                $num = Crud::getCountSelNun('return_visit', $where, $join, $alias, 'rv.id');
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            }
        } else {
            throw new ISUserMissException();
        }

    }

    //获取学生下拉内容
    public static function getUserDatadrop()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'is_del' => 1,
                'mem_id' => $account_data['mem_id'],
                'student_status' => ['in', [2, 3]]  //1公海池，2业务员列表（潜在学员库），3在读学员
            ];
            //绑定信息ID
            $user_data = Crud::getData('lmport_student_member', 2, $where, 'id value,student_name label,student_status,phone,student_id');
            foreach ($user_data as $k => $v) {
                $user_data[$k]['label'] = $v['label'] . '-' . $v['phone'];
            }
            return jsonResponseSuccess($user_data);
        } else {
            throw new ISUserMissException();
        }
    }


    //获取用户身份信息(详细信息)
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

    //修改用户信息加机构名称
    public static function editLmportStudentDetails()
    {
        $data = input();
        $student_id = $data['student_id'];
        unset($data['student_id']);
        $table = request()->controller();
        $StudentDetails_data = Crud::setUpdate($table, ['id' => $student_id], $data);
        if ($StudentDetails_data) {
            return jsonResponseSuccess($StudentDetails_data);
        } else {
            throw new EditRecoMissException();
        }

    }


}