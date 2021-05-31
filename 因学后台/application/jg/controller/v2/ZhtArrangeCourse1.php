<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/29 0029
 * Time: 10:37
 */

namespace app\jg\controller\v2;


use app\jg\controller\v1\BaseController;
use app\jg\controller\v2\Order;
use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\EditRecoMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use Edas\Request\V20170801\DeleteUserDefineRegionRequest;
use think\Db;
use app\jg\controller\v2\Automatic;

class ZhtArrangeCourse extends BaseController
{
    //获取排课列表 (根据时间变化老师和教室ID)
    public static function getZhtArrangeCourse($page = 1, $pageSize = 8, $mem_id = '', $arrange_course_name = '', $classroom_id = '', $time_data = '', $teacher_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'zac.mem_id' => $mem_id,
                'zac.is_del' => 1,
                'm.is_del' => 1,
                'm.status' => 1,
                'zc.is_del' => 1,
            ];
            (isset($arrange_course_name) && !empty($arrange_course_name)) && $where['zac.arrange_course_name'] = ['like', '%' . $arrange_course_name . '%'];
            (isset($classroom_id) && !empty($classroom_id[1])) && $where['zac.day_classroom_id'] = $classroom_id[1];
            if (isset($time_data) && !empty($time_data)) {
                $start_time = $time_data[0] / 1000;
                $end_time = $time_data[1] / 1000;
                $where['zac.create_time'] = ['between', [$start_time, $end_time]];
            }

            $table = 'zht_arrange_course';
            $join = [
                ['yx_member m', 'zac.mem_id = m.uid', 'left'], //机构
                ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程
                ['yx_classroom cl', 'zac.day_classroom_id = cl.id', 'left'], //教室 //day_classroom_id
                ['yx_teacher te', 'zac.day_teacher_id = te.id', 'left'], //老师   //day_teacher_id
                ['yx_zht_category ca', 'zc.category_id = ca.id', 'left'], //一级分类
                ['yx_category_small cas', 'zc.category_small_id = cas.id', 'left'], //二级分类
            ];
            $alias = 'zac';
            $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zac.create_time desc', $field = 'zac.*,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone,zc.course_img,zc.course_name,zc.course_type,cl.classroom_name,te.teacher_nickname,ca.name category_name,cas.name category_small_name', $page, $pageSize);
            if ($info) {
                foreach ($info as $k => $v) {
                    $info[$k]['course_id'] = [$v['course_category_id'], $v['classroom_category_small_id'], $v['course_id']];
                    if ($v['arrange_course_type'] == 1) {
                        $info[$k]['arrange_course_type_name'] = '体验课';
                    } elseif ($v['arrange_course_type'] == 2) {
                        $info[$k]['arrange_course_type_name'] = '普通课';
                    }
                    //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                    if ($v['course_type'] == 1) {
                        $info[$k]['arrange_course_type_name'] = '体验课';
                    } elseif ($v['course_type'] == 2) {
                        $info[$k]['arrange_course_type_name'] = '普通课';
                    } elseif ($v['course_type'] == 3) {
                        $info[$k]['arrange_course_type_name'] = '活动课';
                    } elseif ($v['course_type'] == 4) {
                        $info[$k]['arrange_course_type_name'] = '试听课';
                    } elseif ($v['course_type'] == 5) {
                        $info[$k]['arrange_course_type_name'] = '赠送课';
                    }
                    $info[$k]['time_data'] = date('Y-m-d', $v['start_arrange_course']) . '-' . date('Y-m-d', $v['end_arrange_course']);
                    $info[$k]['course_id'] = [$v['course_category_id'], $v['classroom_category_small_id'], $v['course_id']];
                    $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                    $info[$k]['member_phone'] = $v['member_phone'];
                    $info[$k]['category_type'] = $v['category_name'] . ' ' . $v['category_small_name'];
                    $course_price = Crud::getData('zht_course_num', 1, ['course_id' => $v['course_id'], 'is_del' => 1], 'course_section_price');
                    if ($course_price) {
                        $info[$k]['course_price'] = $course_price['course_section_price'];
                    }
                    $timetable_data = Crud::getData('zht_course_timetable', 2, ['arrange_course_id' => $v['id'], 'is_del' => 1], 'attend_class_num', 'attend_class_num desc');
                    if (empty($timetable_data)) {
                        $attend_class_num = 0;
                    } else {
                        $attend_class_num = $timetable_data[0]['attend_class_num'];
                    }
                    $info[$k]['attend_class_num'] = $attend_class_num;
                    $identifier_time = Crud::getData('zht_course_timetable', 1, ['arrange_course_id' => $v['id'], 'is_del' => 1], 'identifier_time');
                    $info[$k]['classroom_id'] = unserialize($v['classroom_id']);//教室分类和ID
                    $info[$k]['teacher_id'] = unserialize($v['teacher_id']);//老师分类和ID
                    $info[$k]['time_slot'] = unserialize($v['time_slot']);//时间段
                    $info[$k]['timetable_time'] = unserialize($v['timetable_time']);//时间
                    $info[$k]['valuetime'] = unserialize($v['valuetime']);//前端展示
                    $info[$k]['week'] = unserialize($v['week']);//星期时间展示
                    $info[$k]['identifier_time'] = $identifier_time['identifier_time'];
                }

                $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'c.id');
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                    'pageSize' => (int)$pageSize,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取课程下拉多级
    public static function getClassHourList($arrange_course_id)
    {
        $arrange_course_name = Crud::getData('zht_arrange_course', 1, ['id' => $arrange_course_id, 'is_del' => 1], 'arrange_course_name,arrange_course_num');
        if (!$arrange_course_name) {
            throw new NothingMissException();
        }
        $where = [
            'arrange_course_id' => $arrange_course_id,
            'is_del' => 1,
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where, 'id,attend_class_num,day_time_end', 'attend_class_num', 1, 1000);
        if ($course_timetable) {
            $array_num = [];
            foreach ($course_timetable as $k => $v) {
                if ($v['day_time_end'] < time()) {
                    $disabled = true;
                } else {
                    //获取今天上课的人数  yx_zht_student_class_list
                    $where_student_class_list = [
                        'is_del' => 1,
                        'arrange_course_id' => $arrange_course_id,
                        'course_timetable_id' => $v['id']
                    ];
                    $student_class_list_num = Crud::getCount('zht_student_class_list', $where_student_class_list);
                    if ($student_class_list_num) {
                        if ($student_class_list_num >= $arrange_course_name['arrange_course_num']) {
                            $disabled = true;
                        } elseif ($student_class_list_num < $arrange_course_name['arrange_course_num']) {
                            $disabled = false;
                        }
                    } else {
                        $disabled = false;
                    }
                }
                $array_num[] = [
//                    'value' => $arrange_course[$ii],
                    'value' => $v['id'],
                    'disabled' => $disabled,
                    'label' => '第' . $v['attend_class_num'] . '课',
                ];
            }

            $array_data[] = [
                'value' => 1,
                'label' => $arrange_course_name['arrange_course_name'],
                'children' => $array_num
            ];
            return jsonResponseSuccess($array_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取课程下拉一级
    public static function getClassHourOneList($arrange_course_id, $type = 1)
    {
        $where = [
            'id' => $arrange_course_id,
            'is_del' => 1,
        ];
        //求班级的设置人员数
        $arrange_course_data = Crud::getData('zht_arrange_course', 1, $where, 'arrange_course_num');
        if (!$arrange_course_data) {
            throw new NothingMissException();
        }
        $where_course_timetable = [
            'arrange_course_id' => $arrange_course_id,
            'is_del' => 1,
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where_course_timetable, 'id,attend_class_num,day_time_end', 'attend_class_num', 1, 1000);
        if ($course_timetable) {
            if ($type == 1) {
                $array_num = [];
                foreach ($course_timetable as $k => $v) {
                    if ($v['day_time_end'] < time()) {
                        $disabled = true;
                    } else {
                        //获取本时间上课的人数  yx_zht_student_class_list
                        $where_student_class_list = [
                            'is_del' => 1,
                            'arrange_course_id' => $arrange_course_id,
                            'course_timetable_id' => $v['id']
                        ];
                        $student_class_list_num = Crud::getCount('zht_student_class_list', $where_student_class_list);
                        if ($student_class_list_num) {
                            if ($student_class_list_num >= $arrange_course_data['arrange_course_num']) {
                                $disabled = true;
                            } elseif ($student_class_list_num < $arrange_course_data['arrange_course_num']) {
                                $disabled = false;
                            }
                        } else {
                            $disabled = false;
                        }
                    }
                    $array_num[] = [
//                    'value' => $arrange_course[$ii],
                        'value' => $v['id'],
                        'disabled' => $disabled,
                        'label' => '第' . $v['attend_class_num'] . '课',
                    ];
                }
                return jsonResponseSuccess($array_num);
            } else {
                return $course_timetable;
            }
        } else {
            throw new NothingMissException();
        }
    }

    //获取订单添加学生 setArrangeCourseStudent
    public static function getCourseOrder($page = 1, $pageSize = 100000, $mem_id = '', $student_name = '', $course_id = '', $course_num = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'zo.mem_id' => $mem_id,
                'zo.course_num' => $course_num,
                'zo.is_del' => 1,
                'zo.course_id' => $course_id[2],
                'zo.status' => 2, //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                'm.is_del' => 1,
                'm.status' => 1,
                'ls.is_del' => 1,
            ];
            (isset($student_name) && !empty($student_name)) && $where['ls.student_name'] = ['like', '%' . $student_name . '%'];
            $whereOr = [];
            (isset($student_name) && !empty($student_name)) && $whereOr['ls.phone'] = ['like', '%' . $student_name . '%'];
            $table = 'zht_order';
            $join = [
                ['yx_member m', 'zo.mem_id = m.uid', 'left'], //机构
                ['yx_zht_course zc', 'zo.course_id = zc.id', 'left'], //课程
                ['yx_lmport_student_member ls', 'zo.student_member_id = ls.id', 'left'], //学生
//                ['yx_category ca', 'zc.category_id = ca.id', 'left'], //一级分类
//                ['yx_category_small cas', 'zc.category_small_id = cas.id', 'left'], //二级分类
            ];
            $alias = 'zo';
            $info = Crud::getRelationDataWhereOr($table, $type = 2, $where, $whereOr, $join, $alias, $order = 'zo.create_time desc', $field = 'zo.course_num,zo.order_id,zo.course_hour_record_id,zo.student_member_id,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone,zc.course_img,zc.course_name,ls.*', $page, $pageSize);
            if ($info) {
                foreach ($info as $k => $v) {
                    if (!empty($v['birthday'])) {
                        $info[$k]['year_age'] = CalculationAge($v['birthday']);
                    }
                    if ($v['sex'] == 1) {
                        $info[$k]['sex_name'] = '男';
                    } elseif ($v['sex'] == 2) {
                        $info[$k]['sex_name'] = '女';
                    }
                    $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                }
                $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'ls.id');
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                    'pageSize' => (int)$pageSize,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取添加学生字段
    public static function getCourseOrderField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学生姓名', 'width' => '', 'state' => ''],
            ['prop' => 'phone', 'name' => '手机号', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //返回不同时间段班级人员数
    public static function getCourseTimeNum()
    {
        $data = input();
        $where = [
            'zscl.arrange_course_id' => $data['arrange_course_id'],
            'zscl.is_del' => 1,
        ];
        if (isset($data['course_timetable_id']) && !empty($data['course_timetable_id'])) {
            $where['zscl.course_timetable_id'] = $data['course_timetable_id'];
        } else {
            $where_course_timetable = [
                'day_time_start' => ['>=', time()],
                'arrange_course_id' => $data['arrange_course_id'],
            ];
            $course_timetable_id = Crud::getData('zht_course_timetable', 2, $where_course_timetable, 'id', 'day_time_start');
            if (!$course_timetable_id) {
                throw new NothingMissException();
            } else {
                $where['zscl.course_timetable_id'] = $course_timetable_id[0]['id'];
            }
        }
        $join = [
            ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left']
        ];
        $alias = 'zscl';
//        $info = Crud::getRelationData('zht_student_class_list', 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zscl.course_timetable_id,zscl.student_member_id', 1, 1000000,'zscl.student_member_id');
        $info = Crud::getRelationData('zht_student_class_list', 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zscl.course_timetable_id,zscl.student_member_id', 1, 1000000);
        if ($info) {
            $array = [
                'course_timetable_id' => $info[0]['course_timetable_id'],
                'student_num' => count($info)
            ];
            return jsonResponseSuccess($array);
        } else {
            throw new NothingMissException();
        }

    }


    //将所选学生添加到排课程  yx_zht_student_class  arrange_course_id 安排班级ID student_id 学生ID student_class_type 1正常排班学员，2插班学员 course_num 课程节数
    //start_course_num 开始上课(从第几节课开始上) student_data学生信息 有学生student_id 学生订单号order_id course_num 课包数
    public static function setStudentClass()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $course_num = self::getClassHout($data['arrange_course_id'], '', 2);  //如果没有传‘tart_course_num’为全总添加课时（节数）
        //插班查看班级人数是否满员
        //将多维数组的ID单个取出来
        $course_num_array = array_column($course_num['arrange_course'], 'id');
        $isStudentCourseNum = self::isStudentCourseNum($data['arrange_course_id'], $data['student_data'], $course_num_array);
        if (!is_array($isStudentCourseNum)) {
            return $isStudentCourseNum;
        }
        foreach ($data['student_data'] as $k => $v) { // 多个学生的添加
            //添加课程表信息  yx_zht_course_hour_record
            $course_hour_record = [
                'id' => $v['course_hour_record_id'],
                'is_del' => 1
            ];
            //减待排课程
            Crud::setUpdate('zht_course_hour_record', $course_hour_record, ['stay_row_num' => 0]);
            //添加已排课时 scheduled_num
            Crud::setUpdate('zht_course_hour_record', $course_hour_record, ['scheduled_num' => $course_num['course_num']]);

            //添加关系 yx_zht_course_hour_student_class
            $course_hour_student_class = [
                'course_hour_record_id' => $v['course_hour_record_id'],
                'arrange_course_id' => $data['arrange_course_id']
            ];
            $course_hour_student_class_data = Crud::setAdd('zht_course_hour_student_class', $course_hour_student_class);

            //按排课程
            $student_class_add = [
                'mem_id' => $data['mem_id'],
                'arrange_course_id' => $data['arrange_course_id'],
                'student_id' => $v['student_id'],
                'student_member_id' => $v['student_member_id'],
                'student_class_type' => $data['student_class_type'],
                'course_num' => $course_num['course_num'], //总课程节数
//                'start_course_num' => $course_num[0]['attend_class_num'], //从第几节课开始
                'order_id' => $v['order_id'],
            ];
            $student_class = Crud::setAdd('zht_student_class', $student_class_add, 2);
            //修改订单状态
            $order_status = Crud::setUpdate('zht_order', ['order_id' => $v['order_id']], ['status' => 3, 'surplus_course_num' => 0, 'arrange_course_id' => $data['arrange_course_id']]);
            if ($student_class) {
                if ($course_num) {
                    foreach ($course_num['arrange_course'] as $kk => $vv) {
                        $add_data = [
                            'order_id' => $v['order_id'],
                            'arrange_course_id' => $data['arrange_course_id'],
                            'zht_student_class_id' => $student_class,
                            'student_id' => $v['student_id'],
                            'student_member_id' => $v['student_member_id'],
                            'student_class_type' => $data['student_class_type'],
                            'course_timetable_id' => $vv['id'], //排课时间ID
                            'course_hour_record_id' => $v['course_hour_record_id'], //课时记录ID
                        ];
                        $zht_student_class_list = Crud::setAdd('zht_student_class_list', $add_data);
                    }
                }
            }
        }
        if ($zht_student_class_list) {
            return jsonResponseSuccess($zht_student_class_list);
        } else {
            throw new AddMissException();
        }
    }

    //用户添加选择插入课程
    public static function setStudentClassOne()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if (isset($data['tart_course_num']) && !empty($data['tart_course_num'])) {
            $course_num = Many_One($data['tart_course_num']);
            $course_num = array_unique($course_num);
            $course_num_data = self::getClassHout($data['arrange_course_id'], $course_num);
            unset($course_num[0]);
        }
        //验证此人是否有此课程，如果有将提示
        foreach ($data['student_data'] as $k => $v) {
            foreach ($course_num as $kk => $vv) {
                $iscourse_where = [
                    'student_id' => $v['student_id'],
                    'course_timetable_id' => $vv,
                    'is_del' => 1
                ];
                $isstudent_class_list = Crud::getData('zht_student_class_list', 1, $iscourse_where, 'id');
                if ($isstudent_class_list) {
                    //学生信息
                    $student_data = Crud::getData('lmport_student', 1, ['id' => $v['student_id'], 'is_del' => 1], 'student_name');
                    $course_timetable_data = Crud::getData('zht_course_timetable', 1, ['id' => $vv, 'is_del' => 1], 'attend_class_num');
                    return jsonResponse('300', '学生' . $student_data['student_name'] . '第' . $course_timetable_data['attend_class_num'] . '节课已排，请重新选择');
                }
            }
        }
        //插班查看班级人数是否满员
        $isStudentCourseNum = self::isStudentCourseNum($data['arrange_course_id'], $data['student_data'], $course_num);
//        exit;
        if (!is_array($isStudentCourseNum)) {
            return $isStudentCourseNum;
        }
        foreach ($data['student_data'] as $k => $v) { // 多个学生的添加
            //查看我是否有此订单
            $order_data = Crud::getData('zht_order', 1, ['mem_id' => $data['mem_id'], 'student_id' => $v['student_id'], 'course_id' => $data['course_id'][2]], '*');
            if ($order_data) {
                //查看我的课时数记录 yx_zht_course_hour_record
                $course_hour_record = Crud::getData('zht_course_hour_record', 1, ['id' => $order_data['course_hour_record_id'], 'is_del' => 1], '*');
                if (!$course_hour_record) {
                    throw new NothingMissException();
                }
                //查询我现在有几课时可以使用    待排课时                    赠送课时                             已排课时
                $use_course_hour = $course_hour_record['stay_row_num'] + $course_hour_record['give_num'] - $course_hour_record['scheduled_give_num'];
//                if ($use_course_hour <= 0) {
//                    return jsonResponse('3000', '数据有误请重试');

//                }
                //判断我是否满足此课时
                //剩余课时    我的课时                      要添加的课时
                $Last_num = $use_course_hour - $course_num_data['course_num'];
                if ($Last_num >= 0) {
                    //获取已排课时数量     待排课                       需要添加的课时
                    $want_num = $course_hour_record['stay_row_num'] - $course_num_data['course_num'];
                    //待排课程满足
                    if ($want_num >= 0) {
                        //添加课时记录 yx_zht_course_hour_record
                        //减待排课时
                        Crud::setDecs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'stay_row_num', $want_num);
                        //加已排课时
                        Crud::setIncs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'scheduled_num', $want_num);
                    } elseif ($want_num < 0) { //那我的正常课不足还需要要修改赠送课信息   有问题我不够的课程在哪添加订单了
                        //减待排课时
                        Crud::setDecs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'stay_row_num', $course_hour_record['stay_row_num']);
                        //加已排课时
                        Crud::setIncs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'scheduled_num', $course_hour_record['stay_row_num']);
                        //加赠送已排课
                        Crud::setIncs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'scheduled_give_num', abs($want_num));
                    }
                    //添加学生排课及上课信息
                    $zht_student_class_list = self::addjgStudentInsertClass($data, $v, $course_num, $course_num_data, $course_hour_record['id']);
                } elseif ($Last_num < 0) {
                    //判断他有没有下常课时
                    //查询我现在有几课时可以使用    待排课时                    赠送课时                             已排赠送课时
//                    $use_course_hour = $course_hour_record['stay_row_num'] + $course_hour_record['give_num'] - $course_hour_record['scheduled_give_num'];
                    //验证待排课时是否大于0
                    if ($course_hour_record['stay_row_num'] > 0) {
                        //减待排课时
                        Crud::setDecs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'stay_row_num', $course_hour_record['stay_row_num']);
                        //加已排课时
                        Crud::setIncs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'scheduled_num', $course_hour_record['stay_row_num']);
                    }
                    //获取待排课课时，
                    $stay_give_num = $course_hour_record['give_num'] - $course_hour_record['scheduled_give_num'];
                    if ($stay_give_num > 0) {
                        //加已排赠送课时
                        Crud::setIncs('zht_course_hour_record', ['id' => $course_hour_record['id']], 'scheduled_give_num', $stay_give_num);
                    }
                    //计算新创订单需要增加的课时
                    $add_order_course_num = $course_num_data['course_num'] - $course_hour_record['stay_row_num'] - $stay_give_num;
                    $order_updata = [
                        'mem_id' => $data['mem_id'],
                        'course_id' => $data['course_id'],
                        'course_num' => $add_order_course_num,
                        'course_type' => 1, //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                        'status' => 3, //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                        'student_id' => $v['student_id'],
                        'student_member_id' => $v['student_member_id'],
                        'arrange_course_id' => $data['arrange_course_id'],
                        'type' => 2,
                        'course_hour_record_id' => $order_data['course_hour_record_id'],
                    ];
                    //添加体验订单1
                    $order_info = self::addjgStudentOrder($order_updata);
                    //添加学生排课及上课信息
                    $zht_student_class_list = self::addjgStudentInsertClass($data, $v, $course_num, $course_num_data, $order_data['course_hour_record_id'], $order_info);
                    //添加课程表信息  yx_zht_course_hour_record
                    $course_hour_record = [
                        'id' => $order_data['course_hour_record_id'],
                        'is_del' => 1
                    ];
                    //添加赠送已排课程
                    Crud::setIncs('zht_course_hour_record', $course_hour_record, 'scheduled_give_num', $add_order_course_num);
                    //添加赠送课时 scheduled_num
                    Crud::setIncs('zht_course_hour_record', $course_hour_record, 'give_num', $add_order_course_num);
                    $course_hour_student_class = [
                        'course_hour_record_id' => $order_data['course_hour_record_id'],
                        'arrange_course_id' => $data['arrange_course_id']
                    ];
                    //判断我的课时记录表和班级表是否绑定
                    $course_hour_student_class_data = Crud::getData('zht_course_hour_student_class', 2, $course_hour_student_class, 'id');
                    if (!$course_hour_student_class_data) {
                        //课时记录表和班级表绑定
                        Crud::setAdd('zht_course_hour_student_class', $course_hour_student_class);
                    }
                }
            } else {
                //添加课程课时记录
                //yx_zht_course_hour_record
                $hour_record = [
                    'course_id' => $data['course_id'][2],
                    'student_id' => $v['student_id'],
                    'student_member_id' => $v['student_member_id'],
                    'mem_id' => $data['mem_id'],
                    'scheduled_give_num' => $course_num_data['course_num'],  //添加赠送已排课程
                    'give_num' => $course_num_data['course_num'],  //添加赠送课时
                ];
                $course_hour_record_id = Crud::setAdd('zht_course_hour_record', $hour_record, 2);
                if (!$course_hour_record_id) {
                    throw new AddMissException();
                }
                //添加体验订单
                $order_updata = [
                    'mem_id' => $data['mem_id'],
                    'course_id' => $data['course_id'],
                    'course_num' => $course_num_data['course_num'],
                    'course_type' => 1, //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                    'status' => 3, //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                    'student_id' => $v['student_id'],
                    'student_member_id' => $v['student_member_id'],
                    'arrange_course_id' => $data['arrange_course_id'],
                    'course_hour_record_id' => $course_hour_record_id,
                    'type' => 2,
                ];
                //添加体验订单
                $order_info = self::addjgStudentOrder($order_updata);
                //修改用户状态 1公海池，2潜在学院库，3在读学院
                self::isReadStudent($v['student_member_id']);
                //添加关系 yx_zht_course_hour_student_class
                $course_hour_student_class = [
                    'course_hour_record_id' => $course_hour_record_id,
                    'arrange_course_id' => $data['arrange_course_id']
                ];
                $course_hour_student_class_data = Crud::setAdd('zht_course_hour_student_class', $course_hour_student_class);
                if (!$course_hour_student_class_data) {
                    throw  new  AddMissException();
                }
                //添加学生排课及上课信息
                $zht_student_class_list = self::addjgStudentInsertClass($data, $v, $course_num, $course_num_data, $course_hour_record_id, $order_info);
            }
        }
        if ($zht_student_class_list) {
            return jsonResponseSuccess($zht_student_class_list);
        } else {
            throw new AddMissException();
        }


    }

    //验证添加学生是否超出排班人员   测试
    public static function isStudentCourseNum($arrange_course_id, $student_arrya, $course_num)
    {
        $where_arrange_course = [
            'id' => $arrange_course_id,
            'is_del' => 1
        ];
        //排课设置人数
        $arrange_course_num = Crud::getData('zht_arrange_course', 1, $where_arrange_course, 'arrange_course_num');
//        dump($arrange_course_num);
        //查询当前有人数
        foreach ($course_num as $k => $v) {
            $where_student_class_list = [
                'course_timetable_id' => $v,
                'is_del' => 1
            ];
            //本节课学生名额
            $arrange_course_already_num = Crud::getData('zht_student_class_list', 2, $where_student_class_list, 'id', '', 1, 10000, 'student_member_id');
            if (!$arrange_course_already_num) {
                $arrange_course_already_num = 0;
            } else {
                $arrange_course_already_num = count($arrange_course_already_num);
            }

//            dump($arrange_course_already_num);
            //剩余学生名额
            $last_num = $arrange_course_num['arrange_course_num'] - $arrange_course_already_num;

            if ($last_num < count($student_arrya)) {
                return jsonResponse('3000', '此班级报名已满，请重新输入');
            }
        }
        return $arrange_course_num;
    }

    //获取排课时间 学生按排了课程后，在插入学生学生上课详情列表
    //添加用户详细排课 yx_zht_student_class_list arrange_course_id 按排课程ID student_id 学生ID  attend_class_num 今天上第几节课，  attend_class_time 上课时间
    //student_course_type 1正常，2请假 start_course_num 从第几节课开始
    public static function getStudentClassList($student_name = '', $phone = '', $page = 1, $pageSize = 8, $arrange_course_id, $time_array = '', $attend_class_num_id = '', $student_class_type = 1)
    {
        $where = [
            'zscl.is_del' => 1,
            'zscl.arrange_course_id' => $arrange_course_id,
            'ls.is_del' => 1,
            'zct.is_del' => 1,
        ];
        //时间筛选
        if (isset($time_array) && !empty($time_array)) {
            $start_time = $time_array[0] / 1000;
            $end_time = $time_array[1] / 1000;
            $where['zscl.create_time'] = ['between', [$start_time, $end_time]];
        }
        (isset($student_name) && !empty($student_name)) && $where['ls.student_name'] = ['like', '%' . $student_name . '%'];
        (isset($phone) && !empty($phone)) && $where['ls.phone'] = ['like', '%' . $phone . '%'];
        (isset($attend_class_num_id) && !empty($attend_class_num_id)) && $where['zscl.course_timetable_id'] = $attend_class_num_id;
        (isset($student_class_type) && !empty($student_class_type)) && $where['zscl.student_class_type'] = $student_class_type;
//        (isset($attend_class_num_id) && !empty($attend_class_num_id)) && $where['zct.id'] = $attend_class_num_id;
        $table = 'zht_student_class_list';
        $join = [
            ['yx_lmport_student_member ls', 'zscl.student_member_id = ls.id', 'left'], //学生
            ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'], //排课时间
        ];
        $alias = 'zscl';
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zct.attend_class_num', $field = 'ls.student_name,ls.phone,ls.birthday,zscl.*,zct.attend_class_num,zct.course_id', $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                if (!empty($v['birthday'])) {
                    $info[$k]['year_age'] = CalculationAge($v['birthday']);
                }
                if ($v['student_course_type'] == 1) {  //1等待上课，2已上课，3，请假，4.缺课,5休学
                    $info[$k]['leave_type_Exhibition'] = '未上课';
                } elseif ($v['student_course_type'] == 2) {
                    $info[$k]['leave_type_Exhibition'] = '已上课';
                } elseif ($v['student_course_type'] == 3) {
                    $info[$k]['leave_type_Exhibition'] = '请假';
                } elseif ($v['student_course_type'] == 4) {
                    $info[$k]['leave_type_Exhibition'] = '缺课';
                } elseif ($v['student_course_type'] == 5) {
                    $info[$k]['leave_type_Exhibition'] = '休学';
                }

            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'zxcl.id');
            $where = [
                'ls.is_del' => 1,
                'zsc.is_del' => 1,
                'zsc.arrange_course_id' => $arrange_course_id,
            ];
            $join = [
                ['yx_lmport_student ls', 'zsc.student_id = ls.id', 'left'], //学生
            ];
            $alias = 'zsc';
            $already_num = Crud::getRelationData('zht_student_class', $type = 2, $where, $join, $alias, $order = 'zsc.id', $field = 'zsc.student_member_id value,ls.student_name label', $page, $pageSize);
            $info_data = [
                'info' => $info,
                'num' => $num,
                'already_num' => $already_num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw  new  NothingMissException();
        }

    }

    //获取排课详细
    public static function getStudentClassListField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学生姓名', 'width' => '', 'state' => ''],
            ['prop' => 'phone', 'name' => '手机号', 'width' => '', 'state' => ''],
            ['prop' => 'birthday', 'name' => '出生年月', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '160', 'state' => '1'],
            ['prop' => 'attend_class_num', 'name' => '课程节次', 'width' => '', 'state' => ''],
            ['prop' => 'leave_type_Exhibition', 'name' => '状态', 'width' => '', 'state' => ''],
        ];
        Automatic::CourseTimetableSort();
        return jsonResponseSuccess($data);
    }

    //学生获取排课详情
    public static function getStudentClass($page = 1, $pageSize = 8, $time_array = '', $student_member_id = '', $course_id = '', $mem_id = '', $student_name = '')
    {
        $account_data = self::isuserData();
        if (!isset($mem_id) || empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        $where = [
            'zscl.student_member_id' => $student_member_id,
            'zscl.is_del' => 1,
//            'zo.mem_id' => $mem_id,
//            'm.is_del' => 1,
//            'zc.is_del' => 1,
//            'zac.is_del' => 1,
        ];
        //时间筛选
//        if (isset($time_array) && !empty($time_array)) {
//            $start_time = $time_array[0] / 1000;
//            $end_time = $time_array[1] / 1000;
//            $where['zscl.create_time'] = ['between', [$start_time, $end_time]];
//        }
//        (isset($student_member_id) && !empty($student_member_id)) && $where['zo.student_member_id'] = $student_member_id;
//        (isset($mem_id) && !empty($mem_id)) && $where['zo.mem_id'] = $mem_id;
//        (isset($student_name) && !empty($student_name)) && $where['ls.student_name'] = ['like', '%' . $student_name . '%'];
//        (isset($attend_class_num_id) && !empty($attend_class_num_id)) && $where['zscl.course_timetable_id'] = $attend_class_num_id;
        (isset($course_id) && !empty($course_id)) && $where['zac.course_id'] = $course_id;

        $table = 'zht_student_class_list';
        $join = [
            ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'], //排课
            ['yx_zht_student_class zsc', 'zscl.zht_student_class_id = zsc.id', 'left'], //班级
            ['yx_zht_arrange_course zac', 'zscl.arrange_course_id = zac.id', 'left'], //排课表
            ['yx_member m', 'zac.mem_id = m.uid', 'left'], //机构表
            ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程
            ['yx_zht_category ca', 'zc.category_id = ca.id', 'left'], //一级分类
            ['yx_category_small cas', 'zc.category_small_id = cas.id', 'left'], //二级分类
            ['yx_classroom cl', 'zct.classroom_id = cl.id', 'left'], //教室
            ['yx_teacher te', 'zct.teacher_id = te.id', 'left'], //老师 teacher_nickname
        ];
        $alias = 'zscl';
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zscl.leave_type,zct.day_time_start,zc.course_name,zc.course_type,ca.name category_name,cas.category_small_name,m.cname,cl.classroom_name,te.teacher_nickname,zct.attend_class_num', $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                $info[$k]['day_time_start_Exhibition'] = date('Y-m-d H:i:s', $v['day_time_start']);
                if ($v['course_type'] == 1) { //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                    $info[$k]['course_type_name'] = '体验课程';
                } elseif ($v['course_type'] == 2) {
                    $info[$k]['course_type_name'] = '普通课程';
                } elseif ($v['course_type'] == 3) {
                    $info[$k]['course_type_name'] = '活动课程';
                } elseif ($v['course_type'] == 4) {
                    $info[$k]['course_type_name'] = '试听课';
                } elseif ($v['course_type'] == 5) {
                    $info[$k]['course_type_name'] = '赠送课';
                }
                $info[$k]['category_combination'] = $v['category_name'] . ',' . $v['category_small_name'];
                if ($v['leave_type'] == 1) { //1待上课，2请假，3已结束
                    $info[$k]['leave_type_name'] = '待上课';
                } elseif ($v['leave_type'] == 2) {
                    $info[$k]['leave_type_name'] = '请假';
                } elseif ($v['leave_type'] == 3) {
                    $info[$k]['leave_type_name'] = '已结束';
                }
            }

            //获取当前用户课时记录 yx_zht_course_hour_record
            $where_course_hour_record = [
                'student_member_id' => $student_member_id,
                'is_del' => 1
            ];
            (isset($course_id) && !empty($course_id)) && $where_course_hour_record['course_id'] = $course_id;
            $zht_course_hour_record = Crud::getData('zht_course_hour_record', 1, $where_course_hour_record, 'stay_row_num,scheduled_num,cancelled_num,give_num');
            if (!$zht_course_hour_record) {
                throw new NothingMissException();
//                $course_hour=[
//                    'stay_row_num'=>$zht_course_hour_record['stay_row_num'],
//                    'scheduled_num'=>$zht_course_hour_record['scheduled_num'],
//                    'cancelled_num'=>$zht_course_hour_record['cancelled_num'],
//                    'give_num'=>$zht_course_hour_record['give_num'],
//                ];
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'zo.id');
            $info_data = [
                'course_hour_record' => $zht_course_hour_record,
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw  new  NothingMissException();
        }

    }

    //获取课时记录表课程名
    public static function getStudentCourse($student_member_id)
    {
        $where = [
            'zchr.student_member_id' => $student_member_id,
            'zchr.is_del' => 1
        ];
        $table = 'zht_course_hour_record';
        $join = [
            ['yx_zht_course zc', 'zchr.course_id = zc.id', 'left'], //课程
        ];
        $alias = 'zchr';
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zc.create_time', $field = 'zc.id,zc.course_name name', 1, 10000);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }


    }

    //学生获取排课详情字段
    public static function getStudentClassField()
    {
        $data = [
            ['prop' => 'course_name', 'name' => '课程名', 'width' => '', 'state' => ''],
            ['prop' => 'course_type_name', 'name' => '课程类型', 'width' => '', 'state' => ''],
            ['prop' => 'category_combination', 'name' => '所属科目', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '160', 'state' => '1'],
            ['prop' => 'teacher_nickname', 'name' => '排课教师', 'width' => '', 'state' => ''],
            ['prop' => 'day_time_start_Exhibition', 'name' => '排课时间', 'width' => '', 'state' => ''],
            ['prop' => 'leave_type_name', 'name' => '状态', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //返回求总课时
    public static function getClassHout($arrange_course_id, $course_num = '', $type = 1)
    {
        if ($type == 1) {
            unset($course_num[0]);
            $where = [
                'id' => ['in', $course_num],
                'is_del' => 1,
            ];
            $arrange_course = Crud::getData('zht_course_timetable', 2, $where, 'id,course_hour', 'day_time_start', 1, 1000);
        } elseif ($type == 2) {
            $where = [
                'arrange_course_id' => $arrange_course_id,
                'is_del' => 1,
            ];
            $arrange_course = Crud::getData('zht_course_timetable', 2, $where, 'id,attend_class_num,course_hour', 'attend_class_num', 1, 1000);
        }
        if ($arrange_course) {
            $array_num = 0;
            foreach ($arrange_course as $k => $v) {
                $array_num += $v['course_hour'];
            }
            $array_data = [
                'arrange_course' => $arrange_course,
                'course_num' => $array_num,
            ];
            return $array_data;
        } else {
            throw new NothingMissException();
        }


//        $arrange_course = Crud::getData('zht_course_timetable', 2, $where, 'id,attend_class_num', 'day_time_start', 1, 1000);
//        if ($arrange_course) {
//            foreach ($arrange_course as $k => $v) {
//                $array_num[] = [
//                    'id' => $v['id'],
//                    'array_num' => $v['attend_class_num'],
//                ];
//            }
//            return $array_num;
//        } else {
//            throw new NothingMissException();
//        }
    }

    public static function getClassHouts($arrange_course_id, $type = 1, $course_num = '')
    {
        if ($type == 1) {
            $where = [
                'arrange_course_id' => $arrange_course_id,
                'is_del' => 1,
            ];
        } elseif ($type == 2) {
            unset($course_num[0]);
            $where = [
                'id' => ['in', $course_num],
                'is_del' => 1,
            ];
        }
        //course_hour

        $arrange_course = Crud::getData('zht_course_timetable', 2, $where, 'id,course_hour', 'day_time_start', 1, 1000);
        if ($arrange_course) {
            $array_num = 0;
            foreach ($arrange_course as $k => $v) {
                $array_num += $v['course_hour'];
            }
            $array_data = [
                'arrange_course' => $arrange_course,
                'course_num' => $array_num,
            ];
            return $array_data;
        } else {
            throw new NothingMissException();
        }


//        $arrange_course = Crud::getData('zht_course_timetable', 2, $where, 'id,attend_class_num', 'day_time_start', 1, 1000);
//        if ($arrange_course) {
//            foreach ($arrange_course as $k => $v) {
//                $array_num[] = [
//                    'id' => $v['id'],
//                    'array_num' => $v['attend_class_num'],
//                ];
//            }
//            return $array_num;
//        } else {
//            throw new NothingMissException();
//        }
    }

    //撤回学生排课
    public static function withdrawStudentClass($student_member_id, $arrange_course_id)
    {


        //删除班级学员 yx_zht_student_class
//        Db::startTrans();
        if (empty($student_member_id)) {
            return jsonResponse('3000', '输入有误，请重新输入');
        }
        foreach ($student_member_id as $k => $v) {
//            $student_class = Crud::setUpdate('zht_student_class', ['student_member_id' => $v, 'arrange_course_id' => $arrange_course_id], ['is_del' => 2]);
//            if (!$student_class) {
//                Db::rollback();
//                throw new UpdateMissException();
//            }


            $table = 'zht_student_class_list';
            $join = [
                ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'],
            ];
            $where_student_class_list = [
                'zscl.student_member_id' => $v,
                'zscl.arrange_course_id' => $arrange_course_id,
                'zscl.is_del' => 1,
                'zct.is_del' => 1,
            ];
            $alias = 'zscl';
            $course_hour_record_data = Crud::getRelationData($table, $type = 2, $where_student_class_list, $join, $alias, $order = 'zscl.create_time desc', $field = 'zscl.course_hour_record_id,zct.course_hour', 1, 10000000);
            //获取学生课时记录
            if (!$course_hour_record_data) {
//                Db::rollback();
                throw new NothingMissException();
            }

            //求撤回课时
            $course_hour = 0;
            foreach ($course_hour_record_data as $kk => $vv) {
                $course_hour += $vv['course_hour'];
            }
            dump($course_hour);
            dump('+++++++++++++++++++');
            //获取本学生课时记录  yx_zht_course_hour_record
            $course_hour_record = Crud::getData('zht_course_hour_record', 1, ['id' => $course_hour_record_data[0]['course_hour_record_id']], '*');
            if (!$course_hour_record) {
//                Db::rollback();
                throw new NothingMissException();
            }
            //stay_row_num 待排课时
            //scheduled_num 已排课时
            //scheduled_give_num 已排赠送课时
            //cancelled_num 已消耗(上完课程)
            //give_num 赠送课时




            if($course_hour_record['scheduled_give_num']<=0){

            }else{
                //减已排赠送课时
                $scheduled_give_num = $course_hour_record['scheduled_give_num'] - $course_hour;
                if ($scheduled_give_num >= 0) {

                }
            }



            exit;
            if ($scheduled_give_num >= 0) {
                $update_course_hour_record = Crud::setUpdate('zht_course_hour_record', ['id' => $course_hour_record_data[0]['course_hour_record_id']], ['scheduled_give_num' => $scheduled_give_num]);
                if (!$update_course_hour_record) {
                    Db::rollback();
                    throw new UpdateMissException();
                }
            } else {
                //减已排课时
                $scheduled_num = $course_hour_record['scheduled_num'] - abs($scheduled_give_num);
                //加未排课时
                $stay_row_num = $course_hour_record['stay_row_num'] + abs($scheduled_give_num);
                $update_course_hour = [
                    'scheduled_give_num' => $scheduled_give_num,
                    'scheduled_num' => $scheduled_num,
                    'stay_row_num' => $stay_row_num,
                ];
                $update_course_hour_record = Crud::setUpdate('zht_course_hour_record', ['id' => $course_hour_record_data[0]['course_hour_record_id']], $update_course_hour);
                if (!$update_course_hour_record) {
                    Db::rollback();
                    throw new UpdateMissException();
                }
            }
            //删除学生上课详细列表 yx_zht_student_class_list
            $student_class_list = Crud::setUpdate('zht_student_class_list', ['student_member_id' => $v, 'arrange_course_id' => $arrange_course_id], ['is_del' => 2]);
            if (!$student_class_list) {
                Db::rollback();
                throw new UpdateMissException();
            }


            //修改学生订单状态  yx_zht_order
//            $zht_order = Crud::setUpdate('zht_order', ['student_id' => $v], ['status' => 11]);
//            if (!$zht_order) {
//                Db::rollback();
//                throw new UpdateMissException();
//            }
        }
        if ($student_class_list) {
            Db::commit();
            return jsonResponseSuccess($student_class_list);
        }

    }

    //用户请假yx_zht_student_class_list  1等待上课，2已上课，3，请假，4请假已处理，5请假未处理，6.缺课
    public static function leaveStudentClassType($student_class_list_id, $student_class_remarks = '')
    {
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $mem_id = $account_data['mem_id'];
        }
        if (!isset($data['admin_user_id']) || empty($data['admin_user_id'])) {
            $admin_user_id = $account_data['admin_user_id'];
        }
        //验证此数据是否正常
        $student_class_list_data = Crud::getData('zht_student_class_list', 2, ['id' => ['in', $student_class_list_id], 'is_del' => 1], '*');
        if (!$student_class_list_data) {
            throw new NothingMissException();
        }
        //获取这节课时  yx_zht_course_timetable
        $course_timetable_data = Crud::getData('zht_course_timetable', 1, ['id' => $student_class_list_data[0]['course_timetable_id']], 'course_hour');
        if (!$course_timetable_data) {
            throw  new NothingMissException();
        }
        foreach ($student_class_list_data as $k => $v) {
            //如果已修改跳过此循环
            $is_student_class_list = Crud::getData('zht_student_class_list', 2, ['id' => $v['id'], 'student_course_type' => 3], 'id');
            if ($is_student_class_list) {
                return jsonResponses('3000', '选择有误，请重新选择');
                exit;
            }
        }
        foreach ($student_class_list_data as $k => $v) {
            $student_class_type = Crud::setUpdate('zht_student_class_list', ['id' => $v['id']], ['student_course_type' => 3]);
            if (!$student_class_type) {
                throw new UpdateMissException();
//            return jsonResponseSuccess($student_class_type);
            }
            $PinCourseRecord_data = [
                'student_class_list_id' => $v['id'],
                'student_id' => $v['student_id'],
                'student_course_type' => 3,
                'mem_id' => $mem_id,
                'admin_user_id' => $admin_user_id, //管理员ID
                'student_class_remarks' => $student_class_remarks, //备注
            ];
            $info = Crud::setAdd('zht_pin_course_record', $PinCourseRecord_data);
            if (!$info) {
                throw new AddMissException();
            }
            //获取课时记录表  arrange_course_id yx_zht_course_hour_student_class
            $where = [
                'chsc.is_del' => 1,
                'chsc.arrange_course_id' => $v['arrange_course_id'],
            ];
            $join = [
                ['yx_zht_course_hour_record zchr', 'chsc.course_hour_record_id = zchr.id', 'left'], //课时记录表
            ];
            $alias = 'chsc';
            $zht_course_hour_record_data = Crud::getRelationData('zht_course_hour_student_class', 1, $where, $join, $alias, $order = '', $field = 'zchr.*');
            if ($zht_course_hour_record_data) {
                //添加请假课时
                $incs_leave_num = Crud::setIncs('zht_course_hour_record', ['id' => $zht_course_hour_record_data['id']], 'leave_num', $course_timetable_data['course_hour']);
                if (!$incs_leave_num) {
                    throw new UpdateMissException();
                }
                $incs_leave_num = Crud::setIncs('zht_course_hour_record', ['id' => $zht_course_hour_record_data['id']], 'stay_row_num', $course_timetable_data['course_hour']);
                if (!$incs_leave_num) {
                    throw new UpdateMissException();
                }
                //判断如果已排赠送课时是否大于请假课时，如果小于更改体验订单，大于为两个多个订单都为请假中
                $order_update = Crud::setUpdate('zht_order', ['course_hour_record_id' => $zht_course_hour_record_data['id'], 'is_del' => 1], ['leave_type' => 2, 'update_time' => time()]);
                if ($order_update) {
                    return jsonResponseSuccess($order_update);
                }
            } else {
                throw new NothingMissException();
            }
        }


    }

    public static function leaveStudentClassTypes($student_class_list_id)
    {
        //验证此数据是否正常
        $student_class_list_data = Crud::getData('zht_student_class_list', 1, ['id' => $student_class_list_id, 'is_del' => 1], '*');
        if (!$student_class_list_data) {
            throw new NothingMissException();
        }
        //获取这节课时  yx_zht_course_timetable
        $course_timetable_data = Crud::getData('zht_course_timetable', 1, ['id' => $student_class_list_data['course_timetable_id']], 'course_hour');
        if (!$course_timetable_data) {
            throw  new NothingMissException();
        }

        $student_class_type = Crud::setUpdate('zht_student_class_list', ['id' => $student_class_list_id], ['student_course_type' => 3]);
        if (!$student_class_type) {
            throw new UpdateMissException();
//            return jsonResponseSuccess($student_class_type);
        }
        //获取课时记录表  arrange_course_id yx_zht_course_hour_student_class
        $where = [
            'chsc.is_del' => 1,
            'chsc.arrange_course_id' => $student_class_list_data['arrange_course_id'],
        ];
        $join = [
            ['yx_zht_course_hour_record zchr', 'chsc.course_hour_record_id = zchr.id', 'left'], //课时记录表
        ];
        $alias = 'chsc';
        $zht_course_hour_record_data = Crud::getRelationData('zht_course_hour_student_class', 1, $where, $join, $alias, $order = '', $field = 'zchr.*');
        if ($zht_course_hour_record_data) {
            //添加请假课时
            $incs_leave_num = Crud::setIncs('zht_course_hour_record', ['id' => $zht_course_hour_record_data['id']], 'leave_num', $course_timetable_data['course_hour']);
            if (!$incs_leave_num) {
                throw new UpdateMissException();
            }
            //判断如果已排赠送课时是否大于请假课时，如果小于更改体验订单，大于为两个多个订单都为请假中
            $order_update = Crud::setUpdate('zht_order', ['course_hour_record_id' => $zht_course_hour_record_data['id'], 'is_del' => 1], ['leave_type' => 2, 'update_time' => time()]);
            if ($order_update) {
                return jsonResponseSuccess($order_update);
            }
        } else {
            throw new NothingMissException();
        }

    }

    //获取调班班级 yx_zht_arrange_course
    public static function gettransferArrangeCourse($mem_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        $arrange_course = Crud::getData('zht_arrange_course', 2, ['mem_id' => $mem_id, 'is_del' => 1], 'id value,arrange_course_name label');
        if ($arrange_course) {
            return jsonResponseSuccess($arrange_course);
        } else {
            throw new  NothingMissException();
        }
    }

    //进行调班 $student_id $transfer_arrange_course_id 调班ID  $arrange_course_id原班级ID   $transfer_course_timetable_id调课课时ID   $course_timetable_id旧课时ID
    public static function settransferArrangeCourse()
    {
        $data = input();
        $account_data = self::isuserData();
        //验证本班级
        $arrange_course = self::isStudentClass($data['student_id'], $data['arrange_course_id'], $data['student_member_id']);
        //获取本节课订单
        //要调的班级
        self::isArrangeCourse($data['student_id'], $data['arrange_course_id']);
        //验证用户是否调过此班  yx_zht_student_class_list
        $where = [
            'student_id' => $data['student_id'],
            'course_timetable_id' => $data['transfer_course_timetable_id'],
            'arrange_course_id' => $data['transfer_arrange_course_id'],
            'is_del' => 1,
        ];
        $student_class_data = Crud::getData('zht_student_class_list', 1, $where, 'id');
        if ($student_class_data) {
            return jsonResponses('3000', '本节课你已存在');
        }
        //修改本课旧班级信息 yx_zht_student_class
        $where_student_class = [
            'arrange_course_id' => $data['arrange_course_id'],
            'student_id' => $data['student_id'],
            'student_member_id' => $data['student_member_id'],
            'is_del' => 1,
        ];
        if ($arrange_course['course_num'] >= 2) {
            $course_num = $arrange_course['course_num'] - 1;
            $student_class = Crud::setUpdate('zht_student_class', $where_student_class, ['update_time' => time(), 'course_num' => $course_num]);
            if (!$student_class) {
                return jsonResponse('3001', '信息错误请重试');
            }
        } else {
            $student_class = Crud::setUpdate('zht_student_class', $where_student_class, ['update_time' => time(), 'is_del' => 2]);
            if (!$student_class) {
                return jsonResponse('3002', '信息错误请重试');
            }
        }
        //修改旧课时的信息 yx_zht_student_class_list
        $where_student_class_list = [
            'course_timetable_id' => $data['course_timetable_id'],
            'arrange_course_id' => $data['arrange_course_id'],
            'student_id' => $data['student_id'],
            'student_member_id' => $data['student_member_id'],
        ];
        $student_class_list = Crud::setUpdate('zht_student_class_list', $where_student_class_list, ['is_del' => 2, 'update_time' => time()]);

        if (!$student_class_list) {
            return jsonResponse('3003', '信息错误请重试');
        }
        //插入班级
        $add_transfer_student_class = [
            'arrange_course_id' => $data['transfer_arrange_course_id'],
            'student_id' => $data['student_id'],
            'student_member_id' => $data['student_member_id'],
            'mem_id' => $account_data['mem_id'],
            'course_num' => 1,
        ];
        $zht_student_class_id = Crud::setAdd('zht_student_class', $add_transfer_student_class, 2);
        if (!$zht_student_class_id) {
            return jsonResponse('3004', '信息错误请重试');
        }

        //验证是本排课是否和课时记录ID绑定 zht_course_hour_student_class
        $where_course_hour_student_class = [
            'course_hour_record_id' => $data['course_hour_record_id'],
            'arrange_course_id' => $data['transfer_arrange_course_id'],
            'is_del' => 1
        ];
        $course_hour_student_class_data = Crud::getData('zht_course_hour_student_class', 2, $where_course_hour_student_class, 'id');
        if (!$course_hour_student_class_data) {
            $add_course_hour_student_class = [
                'course_hour_record_id' => $data['course_hour_record_id'],
                'arrange_course_id' => $data['transfer_arrange_course_id'],
            ];
            $course_hour_student_class = Crud::setAdd('zht_course_hour_student_class', $add_course_hour_student_class);
            if (!$course_hour_student_class) {
                throw new AddMissException();
            }
        }
        $attend_class_num = Crud::getData('zht_course_timetable', 1, ['id' => $data['transfer_course_timetable_id'], 'is_del' => 1], 'attend_class_num');
        if (!$attend_class_num) {
            return jsonResponse('3005', '输入有误，请重新输入');
        }
        //插入课时列表yx_zht_student_class_list
        $add_transfer_student_class_list = [
            'zht_student_class_id' => $zht_student_class_id,
            'arrange_course_id' => $data['transfer_arrange_course_id'],
            'student_id' => $data['student_id'],
            'student_member_id' => $data['student_member_id'],
            'course_timetable_id' => $data['transfer_course_timetable_id'],
            'attend_class_num' => $attend_class_num['attend_class_num'],
            'course_hour_record_id' => $data['course_hour_record_id'],
        ];
        $transfer_student_class_list = Crud::setAdd('zht_student_class_list', $add_transfer_student_class_list);
        if (!$transfer_student_class_list) {
            return jsonResponse('3006', '输入有误，请重新输入');
        } else {
            return jsonResponseSuccess($transfer_student_class_list);
        }
    }

    //验证学生是否在本班级中  yx_zht_student_class
    public static function isStudentClass($student_id, $arrange_course_id, $student_member_id)
    {
        $where_student_class = [
            'student_id' => $student_id,
            'arrange_course_id' => $arrange_course_id,
            'student_member_id' => $student_member_id,
            'is_del' => 1,
        ];
        $student_class = Crud::getData('zht_student_class', 1, $where_student_class, '*');
        if (!$student_class) {
            return jsonResponse('3000', '信息有误，请重试');
        } else {
            return $student_class;
        }
    }

    //验证班级 yx_zht_arrange_course
    public static function isArrangeCourse($arrange_course_id)
    {
        $where_arrange_course = [
            'id' => $arrange_course_id,
            'is_del' => 1,
        ];
        $arrange_course = Crud::getData('zht_arrange_course', 1, $where_arrange_course, 'id');
        if (!$arrange_course) {
            return jsonResponse('3000', '信息有误，请重试');
        } else {
            return $arrange_course;
        }
    }

    //插入学生添加订单
    public static function addjgStudentOrder($data)
    {

        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
//            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            $where = [
                'id' => $data['course_id'][2],
                'is_del' => 1
            ];
            $course_data = Crud::getData('zht_course', 1, $where, '*');
            if (!$course_data) {
                throw new NothingMissException();
            }

            //添加大订单
            $order_num = time() . rand(10, 99);
            $order_num_data = [
                'order_num' => $order_num,
                'mem_id' => $data['mem_id'],
                //'course_type' => $data['course_type'], //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                'status' => 2, //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                //'order_source' => $data['order_source'], //订单来源，1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                'price' => 0,
                'student_id' => $data['student_id'],
                'course_num_id' => $data['course_id'], //课时ID
            ];
            $order_id = time() . rand(10, 99);
            $order_data = [
                'order_id' => $order_id,
                'order_num' => $order_num,
                'mem_id' => $data['mem_id'],
                'course_id' => $data['course_id'][2], //课程ID
                'course_name' => $course_data['course_name'],
                'course_num' => $data['course_num'],
                'surplus_course_num' => 0,//剩余课时
                'course_start_time' => $course_data['course_start_time'],
                'course_end_time' => $course_data['course_end_time'],
                'course_type' => $data['course_type'], //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                'status' => 3,  //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                //'order_source' => $data['order_source'], //订单来源，1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                'student_id' => $data['student_id'], //学生信息
                'student_member_id' => $data['student_member_id'], //学生信息
                'price' => 0,
                'original_price' => 0,
                'course_ids' => serialize($data['course_id']),
                'arrange_course_id' => $data['arrange_course_id'],
                'course_hour_record_id' => $data['course_hour_record_id'],

            ];
            $order_num_info = Crud::setAdd('zht_order_num', $order_num_data);

            if (!$order_num_info) {
                throw new AddMissException();
            }
            $order_info = Crud::setAdd('zht_order', $order_data);
            if (!$order_info) {
                throw new AddMissException();
            }
            if (isset($data['type']) && $data['type'] == 2) {
//                return $order_info;
                return $order_id;
            }
            return jsonResponseSuccess($order_info);


        } else {
            throw new ISUserMissException();
        }

    }

    //插班学生插入班级 $insert_class_type 1我有课程直接添加，2我有订单课程，不满足课时
    public static function addjgStudentInsertClass($data, $v, $course_num, $course_num_data, $course_hour_record_id = '', $order_id = '')
    {
        $student_class_add = [
            'mem_id' => $data['mem_id'],
            'arrange_course_id' => $data['arrange_course_id'],
            'student_id' => $v['student_id'],
            'student_member_id' => $v['student_member_id'],
            'student_class_type' => $data['student_class_type'],
            'course_num' => $course_num_data['course_num'], //总课程节数
        ];
        $student_class = Crud::setAdd('zht_student_class', $student_class_add, 2);
        //添加排课详情
        if ($student_class) {
            if ($course_num) {
                foreach ($course_num as $kk => $vv) {
                    $add_data = [
                        'order_id' => $order_id,
                        'arrange_course_id' => $data['arrange_course_id'],
                        'zht_student_class_id' => $student_class,
                        'student_id' => $v['student_id'],
                        'student_member_id' => $v['student_member_id'],
                        'student_class_type' => $data['student_class_type'],
                        'course_timetable_id' => $vv, //排课时间ID
                        'course_hour_record_id' => $course_hour_record_id, //记录课时ID
                    ];
                    $zht_student_class_list = Crud::setAdd('zht_student_class_list', $add_data);
                }
//                return jsonResponseSuccess($zht_student_class_list);
                return $zht_student_class_list;
            }
        }

    }

    //验证学是是否是在读学员
    public static function isReadStudent($student_member_id)
    {
        $studet_status = Crud::getData('lmport_student_member', 1, ['id' => $student_member_id, 'is_del' => 1], ['student_status']);
        if ($studet_status) {
            if ($studet_status['student_status'] == 3) {
                return 1;
            } else {
                $studet_status_update = Crud::setUpdate('lmport_student_member', ['id' => $student_member_id, 'is_del' => 1], ['student_status' => 3]);
                if ($studet_status_update) {
                    return $studet_status_update;
                } else {
                    throw new UpdateMissException();
                }
            }
        } else {
            throw new UpdateMissException();
        }

    }

    //获取本课程未按排学生的订单 1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
    public static function getCourseOrders($course_id)
    {
        $where = [
            'o.course_id' => $course_id,
            'o.status' => 2,
            'o.is_del' => 1,
            's.is_del' => 1,
        ];
        $table = 'zht_order';
        $join = [
            ['yx_lmport_student s', 'zo.student_id = s.id', 'left'], //学生
        ];
        $alias = 'zo';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zac.create_time desc', $field = 'o.student_id,s.student_name', 1, 1000);


        $order_data = Crud::getData('zht_order', 2, ['is_del' => 1, 'course_id' => $course_id, 'status' => 2], 'student_id');
    }

    //获取即将上课列表
    public static function getStudentClassListTime($page = 1, $pageSize = 8)
    {
        $account_data = self::isuserData();
        $data = input();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'zac.mem_id' => $data['mem_id'],
            'zscl.is_del' => 1,
        ];

        if (isset($time_data) && !empty($time_data)) {
            $start_time = $time_data[0] / 1000;
            $end_time = $time_data[1] / 1000;
            $where['zct.day_time_start'] = ['between', [$start_time, $end_time]];
        } else {
            $where['zct.day_time_start'] = ['>=', time()];
        }
        (isset($data['course_name']) && !empty($data['course_name'])) && $where['zc.course_name'] = ['like', '%' . $data['course_name'] . '%']; //课程名

        (isset($data['arrange_course_id']) && !empty($data['arrange_course_id'])) && $where['zscl.arrange_course_id'] = $data['arrange_course_id'];//班级选择

        (isset($data['teacher_id']) && !empty($data['teacher_id'])) && $where['zscl.teacher_id'] = $data['teacher_id'][2];//老师ID

        (isset($data['classroom_id']) && !empty($data['classroom_id'])) && $where['zscl.classroom_id'] = $data['classroom_id'][2];//教室ID

        (isset($data['is_confirm']) && !empty($data['is_confirm'])) && $where['zscl.is_confirm'] = $data['is_confirm'];//1,已销课程，2未销课程

        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $table = 'zht_student_class_list';
            $join = [
                ['yx_zht_arrange_course zac', 'zscl.arrange_course_id = zac.id', 'left'], //班级表 course_id
                ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程名
                ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'], //排课时间
                ['yx_teacher t', 'zct.teacher_id = t.id', 'left'], //老师
                ['yx_classroom c', 'zct.classroom_id = c.id', 'left'], //教室
            ];
            $alias = 'zscl';
            $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zac.arrange_course_name,zc.course_img,zc.course_name,zct.attend_class_num,zct.day_time_start,t.teacher_nickname,c.classroom_name,c.province,c.city,c.area,c.address,zscl.student_course_type,zct.day_time,zct.time_slot,zac.arrange_course_num,zscl.course_timetable_id,zct.course_hour,zscl.is_confirm,zscl.arrange_course_id,zscl.id', $page, $pageSize, 'zscl.course_timetable_id');
            if ($info) {
                foreach ($info as $k => $v) { //c.province,c.city,c.area,c.address
                    $info[$k]['caddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                    if ($v['student_course_type'] == 1) { //1等待上课，2已上课，3，请假，4请假已处理，5请假未处理
                        $info[$k]['student_course_type_name'] = '等待上课';
                    } elseif ($v['student_course_type'] == 2) {
                        $info[$k]['student_course_type_name'] = '已上课';
                    } elseif ($v['student_course_type'] == 3) {
                        $info[$k]['student_course_type_name'] = '请假';
                    } elseif ($v['student_course_type'] == 4) {
                        $info[$k]['student_course_type_name'] = '请假已处理';
                    } elseif ($v['student_course_type'] == 5) {
                        $info[$k]['student_course_type_name'] = '请假未处理';
                    }
                    if ($v['student_course_type'] != 1) {
                        if ($v['is_confirm'] == 2) {
                            $info[$k]['is_confirm_name'] = '未销课程';
                        } else {
                            $info[$k]['is_confirm_name'] = '';
                        }
                    }

                    $info[$k]['arrange_course_time'] = date('Y-m-d', $v['day_time']) . ' ' . $v['time_slot'];


                    //获取此节课课程人数  yx_zht_student_class_list
                    $where_student_class_list = [
                        'course_timetable_id' => $v['course_timetable_id'],
                        'is_del' => 1
                    ];
                    $student_num = Crud::getCount('zht_student_class_list', $where_student_class_list);
                    if ($student_num) {
                        $info[$k]['student_num'] = $student_num;
                    }

                }
                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zscl.id', 'zscl.course_timetable_id');
//                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zscl.id');
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                    'pageSize' => (int)$pageSize,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取即将上课列表表头字段
    public static function getStudentClassListTimeField()
    {
        $data = [
            ['prop' => 'arrange_course_name', 'name' => '班级名称', 'width' => '', 'state' => ''],
            ['prop' => 'course_img', 'name' => '课程名称', 'width' => '380', 'state' => ''],
            ['prop' => 'attend_class_num', 'name' => '课次', 'width' => '', 'state' => ''],
            ['prop' => 'arrange_course_time', 'name' => '排课时间', 'width' => '160', 'state' => '1'],
            ['prop' => 'teacher_nickname', 'name' => '授课教师', 'width' => '', 'state' => ''],
            ['prop' => 'classroom_name', 'name' => '上课教室', 'width' => '', 'state' => ''],
            ['prop' => 'caddress', 'name' => '教室地址', 'width' => '', 'state' => ''],
            ['prop' => 'student_course_type_name', 'name' => '课程状态', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //获取本机构班级 yx_zht_arrange_course
    public static function getArrangeCourse($mem_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        $where = [
            'mem_id' => $mem_id,
            'is_del' => 1
        ];
        $info = Crud::getData('zht_arrange_course', 2, $where, 'id,arrange_course_name', '', 1, 100000);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }


    }

    //获取本节应到学员 yx_zht_student_class_list
    public static function getCurrentReachStudent($page = 1, $pageSize = 8)
    {
        $data = input();
        $where = [
            'zscl.is_del' => 1,
            'zscl.arrange_course_id' => $data['arrange_course_id'],
            'zscl.course_timetable_id' => $data['course_timetable_id'],
        ];
        $join = [
            ['yx_zht_student_class_list zscl', 'ls.id = zscl.student_id', 'left'], //学生表
            ['yx_admin_user au', 'zscl.admin_user_id = au.id', 'left'], //管理员
        ];
        $alias = 'ls';
        $info = Crud::getRelationData('lmport_student', 2, $where, $join, $alias, $order = 'ls.id desc ', $field = 'ls.id student_id,ls.student_name,ls.sex,ls.birthday,ls.phone,zscl.student_class_remarks,zscl.student_course_type,au.real_member_name admin_user_name,zscl.id', $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                if ($v['student_course_type'] == 1) { //1等待上课，2已上课，3，请假，4请假已处理，5请假未处理
                    $info[$k]['student_course_type_name'] = '等待上课';
                } elseif ($v['student_course_type'] == 2) {
                    $info[$k]['student_course_type_name'] = '已上课';
                } elseif ($v['student_course_type'] == 3) {
                    $info[$k]['student_course_type_name'] = '请假';
                } elseif ($v['student_course_type'] == 4) {
                    $info[$k]['student_course_type_name'] = '请假已处理';
                } elseif ($v['student_course_type'] == 5) {
                    $info[$k]['student_course_type_name'] = '请假未处理';
                } elseif ($v['student_course_type'] == 6) {
                    $info[$k]['student_course_type_name'] = '缺课';
                }
                if ($v['sex'] == 1) {
                    $info[$k]['sex_name'] = '男';
                } elseif ($v['sex'] == 2) {
                    $info[$k]['sex_name'] = '女';
                } else {
                    $info[$k]['sex_name'] = '未知';
                }
                $info[$k]['remarks'] = '【' . $info[$k]['student_course_type_name'] . '】 ' . $v['student_class_remarks'];
                if (empty($v['admin_user_name'])) {
                    $info[$k]['admin_user_name'] = '-';
                }
                $info[$k]['year_age'] = CalculationAge($v['birthday']);
            }
            $num = Crud::getCountSel('lmport_student', $where, $join, $alias, $field = 'ls.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取本节应到学员字段
    public static function getCurrentReachStudentField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员姓名', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '380', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'student_course_type_name', 'name' => '到课状态', 'width' => '', 'state' => ''],
            ['prop' => 'remarks', 'name' => '操作备注', 'width' => '', 'state' => ''],
            ['prop' => 'admin_user_name', 'name' => '操作人员', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //批量到课和缺课 yx_zht_student_class_list $student_class_list_id $student_course_type //1等待上课，2已上课，3，请假，4.缺课，5休学
    public static function confirmCurrent()
    {
        $data = input();
        $where = [
            'id' => ['in', $data['student_class_list_id']],
            'is_del' => 1
        ];
//        $student_class_list_data = Crud::getData('zht_student_class_list', 2, $where, '*');
        $where_student_class_list = [
            'scl.id' => ['in', $data['student_class_list_id']],
            'scl.is_del' => 1
        ];
        $join = [
            ['yx_zht_order zo', 'scl.order_id = zo.order_id', 'left'], //订单
            ['yx_zht_course_timetable zct', 'scl.course_timetable_id = zct.id', 'left'], //机构
        ];
        $alias = 'scl';
        $student_class_list_data = Crud::getRelationData('zht_student_class_list', 2, $where_student_class_list, $join, $alias, $order = 'scl.id', $field = 'scl.*,zct.attend_class_num,zo.student_id,zo.user_id,zo.course_id', 1, 100000000);
        Db::startTrans();//is_confirm 1,已销课程，2未销课程
        $info = Crud::setUpdate('zht_student_class_list', $where, ['is_confirm' => 1, 'student_course_type' => $data['student_course_type'], 'student_class_remarks' => $data['student_class_remarks']]);
        if (!$info) {
            Db::rollback();
            throw new NothingMissException();
        }
        //每节课进行加减操作
        if ($student_class_list_data) {
            foreach ($student_class_list_data as $k => $v) {
                //求当课时 yx_zht_course_timetable
                $where_course_timetable = [
                    'id' => $v['course_timetable_id'],
                    'is_del' => 1
                ];
                $course_timetable_data = Crud::getData('zht_course_timetable', 1, $where_course_timetable, 'course_hour');
                if (!$course_timetable_data) {
                    Db::rollback();
                    throw new  NothingMissException();
                }
                if ($data['student_course_type'] == 2) { //1等待上课，2已上课，3，请假，4.缺课，5休学
                    $operation_num = 'cancelled_num';
                    $message_type = '2';
                } elseif ($data['student_course_type'] == 4) { //1等待上课，2已上课，3，请假，4.缺课，5休学
                    $operation_num = 'cancelled_num';
                    $message_type = '4';
                } elseif ($data['student_course_type'] == 3) { //1等待上课，2已上课，3，请假，4.缺课，5休学
                    $operation_num = 'stay_row_num';
                    $message_type = '3';
                } elseif ($data['student_course_type'] == 5) { //1等待上课，2已上课，3，请假，4.缺课，5休学
                    $operation_num = 'stay_row_num';
                    $message_type = '5';
                }
                //获取当前进度数（要测试）
                $where_enter = [
                    'student_id' => $v['student_id'],
                    'arrange_course_id' => $v['arrange_course_id'],
                    //'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                    //'is_confirm' => 1, //1,已销课程，2未销课程
                    'is_del' => 1
                ];
                $enter_num = Crud::getCount('zht_student_class_list', $where_enter);
                //操作时此课程还未更改状态
                $enter_num = $enter_num + 1;
                //总节数
                $where_sum_course = [
                    'student_id' => $v['student_id'],
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1
                ];
                $sum_course_num = Crud::getCount('zht_student_class_list', $where_sum_course);
                if ($enter_num == $sum_course_num) {
                    $order_status_update = Crud::setUpdate('zht_order', ['order_id' => $v['order_id']], ['status' => 4]); //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                }

                if ($v['user_id'] == null) {
                    $v['user_id'] = 0;
                }
                if ($v['student_id'] == null) {
                    $v['student_id'] = 0;
                }

                //小程序内部添加通知信息
                $message_data = [
                    'user_id' => $v['user_id'],
                    'student_id' => $v['student_id'],
                    'class_hour' => $v['attend_class_num'],
                    'course_category' => 1,
                    'type' => $message_type,
                ];
                $message_inof = self::addMessage($message_data);
                if (!$message_inof) {
                    Db::rollback();
                    throw new  AddMissException();
                }

                if ($data['student_course_type'] == 2 || $data['student_course_type'] == 4) { //1等待上课，2已上课，3，请假，4.缺课，5休学
                    $operation_num = 'cancelled_num';
                } elseif ($data['student_course_type'] == 3 || $data['student_course_type'] == 5) {
                    $operation_num = 'stay_row_num';
                    //记录请假与休课课时
                    $leave_num_num = Crud::setIncs('zht_course_hour_record', ['id' => $v['course_hour_record_id']], 'leave_num', $course_timetable_data['course_hour']);
                    if (!$leave_num_num) {
                        Db::rollback();
                        throw new  AddMissException();
                    }
                    //减已排课
                    $scheduled_num = Crud::setIncs('zht_course_hour_record', ['id' => $v['course_hour_record_id']], 'scheduled_num', $course_timetable_data['course_hour']);
                    if (!$scheduled_num) {
                        Db::rollback();
                        throw new  AddMissException();
                    }
                }
                //添加销课（确认上课和缺课）与添加待排课（请假和休学）
                $course_hour_record_num = Crud::setIncs('zht_course_hour_record', ['id' => $v['course_hour_record_id']], $operation_num, $course_timetable_data['course_hour']);
                if (!$course_hour_record_num) {
                    Db::rollback();
                    throw new  AddMissException();
                }
            }
            $pin_course_record = self::setPinCourseRecord($data);
            if (!$pin_course_record) {
                Db::rollback();
                throw new  AddMissException();
            }
            Db::commit();
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    //添加课程操作通知 yx_zht_message
    public static function addMessage($data)
    {
        $info = Crud::setAdd('zht_message', $data);
        return $info;
    }


    //销课记录添加操作 yx_zht_pin_course_record  yx_zht_student_class_list 'student_class_remarks'=>$data['student_class_remarks']
    public static function setPinCourseRecord($data)
    {
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if (!isset($data['admin_user_id']) || empty($data['admin_user_id'])) {
            $data['admin_user_id'] = $account_data['admin_user_id'];
        }
        $array = [];
        foreach ($data['student_class_list_id'] as $k => $v) {
            foreach ($data['student_id'] as $kk => $vv) {
                if ($k == $kk) {
                    $array[] = [
                        'student_class_list_id' => $v,
                        'student_id' => $vv,
                    ];
                }
            }
        }
        foreach ($array as $k => $v) {
            $add_data = [
                'mem_id' => $data['mem_id'],
                'admin_user_id' => $data['admin_user_id'], //管理员ID
                'student_class_list_id' => $v['student_class_list_id'], //排课列表ID
                'student_id' => $v['student_id'], //学生ID
                'student_class_remarks' => $data['student_class_remarks'], //备注
            ];
            if (isset($data['student_course_type']) || !empty($data['student_course_type'])) {
                $add_data['student_course_type'] = $data['student_course_type']; //1等待上课，2已上课，3，请假，4请假已处理，5请假未处理，6.缺课
            }
            $info = Crud::setAdd('zht_pin_course_record', $add_data);
        }
        if ($info) {
            return $info;
        } else {
            throw new AddMissException();
        }
    }


    //添加备注
    public static function addPinCourseRecord()
    {
        $data = input();
        $where = [
            'id' => ['in', $data['student_class_list_id']],
            'is_del' => 1
        ];
        $info = Crud::setUpdate('zht_student_class_list', $where, ['student_class_remarks' => $data['student_class_remarks']]);
        if ($info) {
            self::setPinCourseRecord($data);
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取备注
    public static function getPinCourseRecord($student_class_list_id)
    {
        $where = [
            'zpcr.student_class_list_id' => $student_class_list_id,
            'zpcr.is_del' => 1
        ];

        $join = [
            ['yx_admin_user au', 'zpcr.admin_user_id = au.id', 'left'], //管理员表
        ];
        $alias = 'zpcr';
        $info = Crud::getRelationData('zht_pin_course_record', 2, $where, $join, $alias, $order = 'zpcr.create_time desc', $field = 'zpcr.student_course_type,zpcr.student_class_remarks,au.real_member_name,zpcr.create_time', 1, 1000000);
        if ($info) {
            foreach ($info as $k => $v) {
                $info[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['student_course_type'] == 1) { //1等待上课，2已上课，3，请假，4请假已处理，5请假未处理
                    $info[$k]['student_course_type_name'] = '等待上课';
                } elseif ($v['student_course_type'] == 2) {
                    $info[$k]['student_course_type_name'] = '已上课';
                } elseif ($v['student_course_type'] == 3) {
                    $info[$k]['student_course_type_name'] = '请假';
                } elseif ($v['student_course_type'] == 4) {
                    $info[$k]['student_course_type_name'] = '请假已处理';
                } elseif ($v['student_course_type'] == 5) {
                    $info[$k]['student_course_type_name'] = '请假未处理';
                } elseif ($v['student_course_type'] == 6) {
                    $info[$k]['student_course_type_name'] = '缺课';
                }
                if (empty($v['real_member_name']) || !isset($v['real_member_name'])) {
                    $info[$k]['real_member_name'] = '-';
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    //备注字段
    public static function getPinCourseRecordField()
    {
        $data = [
            ['prop' => 'create_time_Exhibition', 'name' => '时间', 'width' => '', 'state' => ''],
            ['prop' => 'student_course_type_name', 'name' => '操作状态', 'width' => '380', 'state' => ''],
            ['prop' => 'real_member_name', 'name' => '操作人员', 'width' => '', 'state' => ''],
            ['prop' => 'student_class_remarks', 'name' => '操作备注', 'width' => '160', 'state' => '1'],
        ];
        return jsonResponseSuccess($data);
    }

    //本学生上课明细
    public static function getStudentArrangeCourseList($page = 1, $pageSize = 8)
    {
        $account_data = self::isuserData();
        $data = input();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'zac.mem_id' => $data['mem_id'],
            'zscl.is_del' => 1,
        ];
        if (isset($time_data) && !empty($time_data)) {
            $start_time = $time_data[0] / 1000;
            $end_time = $time_data[1] / 1000;
            $where['zct.day_time_start'] = ['between', [$start_time, $end_time]];
        } else {
            $where['zct.day_time_start'] = ['>=', time()];
        }
        (isset($data['course_name']) && !empty($data['course_name'])) && $where['zc.course_name'] = ['like', '%' . $data['course_name'] . '%']; //课程名
        (isset($data['student_name']) && !empty($data['student_name'])) && $where['ls.student_name'] = ['like', '%' . $data['student_name'] . '%']; //课程名
        (isset($data['phone']) && !empty($data['phone'])) && $where['ls.phone'] = ['like', '%' . $data['phone'] . '%']; //课程名

        (isset($data['is_confirm']) && !empty($data['is_confirm'])) && $where['zscl.is_confirm'] = $data['is_confirm'];//1,已销课程，2未销课程

        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $table = 'zht_student_class_list';
            $join = [
                ['yx_zht_arrange_course zac', 'zscl.arrange_course_id = zac.id', 'left'], //班级表 course_id
                ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程名
                ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'], //排课时间
                ['yx_lmport_student ls', 'zscl.student_id = ls.id', 'left'], //学生表
            ];
            $alias = 'zscl';
            $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zscl.student_id,ls.id,ls.student_name,ls.year_age,ls.birthday,ls.sex,ls.phone,zc.course_name,zscl.id student_class_list_id,zct.day_time', $page, $pageSize, 'ls.id');
            if ($info) {
                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zscl.id', 'ls.id');
                foreach ($info as $k => $v) {
                    //年龄计算
                    if (!empty($v['birthday'])) {
                        $info[$k]['year_age'] = CalculationAge($v['birthday']);
                    }
                    $info[$k]['arrange_course_time'] = date('Y-m-d', $v['day_time']);
                    //求总课节取数据库每条数
                    $sum_course_second_num = Crud::getCount('zht_student_class_list', ['student_id' => $v['id'], 'is_del' => 1]);
                    //报名课程数
                    $info[$k]['sum_course_num'] = Crud::getCount('zht_student_class', ['student_id' => $v['id'], 'is_del' => 1]);
                    //求进度个数 yx_zht_student_class_list
                    $where_enter = [
                        'student_id' => $v['id'],
                        'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                        'is_del' => 1
                    ];
                    $enter_num = Crud::getCount('zht_student_class_list', $where_enter);
                    $info[$k]['enter_num'] = $enter_num . '/' . $sum_course_second_num;
                    //求待销课数 pin_course
                    $where_pin = [
                        'student_id' => $v['id'],
                        'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                        'is_confirm' => 2, //1,已销课程，2未销课程
                        'is_del' => 1
                    ];
                    $info[$k]['pin_num'] = Crud::getCount('zht_student_class_list', $where_pin);

                    //异常数
                    $where_unusual = [
                        'student_id' => $v['id'],
                        'student_course_type' => ['NOTIN', [1, 2]],  //1等待上课，2已上课，3，请假，4.缺课，5休学
                        'is_del' => 1
                    ];
                    $info[$k]['unusual_num'] = Crud::getCount('zht_student_class_list', $where_unusual);
                    if ($v['sex'] == 1) {
                        $info[$k]['sex_name'] = '男';
                    } elseif ($v['sex'] == 2) {
                        $info[$k]['sex_name'] = '女';
                    }
                }
//                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zscl.id');
                $info_data = [
                    'info' => $info,
                    'num' => $num,
                    'pageSize' => (int)$pageSize,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //本学生上课明细字段
    public static function getStudentArrangeCourseListField()
    {
        $data = [
            ['prop' => 'student_name', 'name' => '学员名称', 'width' => '', 'state' => ''],
            ['prop' => 'year_age', 'name' => '年龄', 'width' => '380', 'state' => ''],
            ['prop' => 'sex_name', 'name' => '性别', 'width' => '', 'state' => ''],
            ['prop' => 'phone', 'name' => '手机号', 'width' => '160', 'state' => '1'],
            ['prop' => 'sum_course_num', 'name' => '报名课程数', 'width' => '160', 'state' => '1'],
            ['prop' => 'enter_num', 'name' => '课程进度', 'width' => '160', 'state' => '1'],
            ['prop' => 'pin_num', 'name' => '待销课数', 'width' => '160', 'state' => '1'],
            ['prop' => 'unusual_num', 'name' => '异常状态（请假/缺课）', 'width' => '160', 'state' => '1'],
        ];
        return jsonResponseSuccess($data);
    }


    //本学生上课明细
    public static function getStudentArrangeCourseInfo($page = 1, $pageSize = 8)
    {
        $data = input();
        $where = [
            'zsc.student_id' => $data['student_id'],
            'zsc.is_del' => 1
        ];
        $table = 'zht_student_class';
        $join = [
            ['yx_zht_student_class_list zscl', 'zsc.id = zscl.zht_student_class_id', 'left'], //学生上课记录表
            ['yx_zht_arrange_course zac', 'zsc.arrange_course_id = zac.id', 'left'], //班级表 course_id
            ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程名
            ['yx_zht_category zca', 'zc.category_id = zca.id', 'left'], //一级课目
            ['yx_category_small zcas', 'zc.category_small_id = zcas.id', 'left'], //二级课目 category_small_name
        ];
        $alias = 'zsc';
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zsc.create_time desc ', $field = 'zsc.arrange_course_id,zsc.student_id,zac.course_num,zac.start_arrange_course,zac.end_arrange_course,zc.course_name,zc.course_img,zca.name category_name,zcas.category_small_name,zsc.arrange_course_id', $page, $pageSize, 'zac.id');
        if ($info) {
            foreach ($info as $k => $v) {
                $info[$k]['arrange_course_time'] = date('Y-m-d H:i:s', $v['start_arrange_course']) . '至' . date('Y-m-d H:i:s', $v['end_arrange_course']);
                $where_enter = [//进度
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                ];
                $enter_num = self::getStudentClassListbNum($where_enter);

                $where_course_num = [//共多少节课程
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                ];
                $course_num = self::getStudentClassListbNum($where_course_num);
                $info[$k]['enter_num'] = $enter_num . '/' . $course_num;
                $where_upper = [//上课数
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'student_course_type' => 2// 1等待上课，2已上课，3，请假，4.缺课,5休学
                ];
                $info[$k]['attend_class_num'] = self::getStudentClassListbNum($where_upper);
                $where_leave = [ //请假数
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'student_course_type' => 3// 1等待上课，2已上课，3，请假，4.缺课,5休学
                ];
                $info[$k]['leave_num'] = self::getStudentClassListbNum($where_leave);


                $where_lack = [ //缺课
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'student_course_type' => 4// 1等待上课，2已上课，3，请假，4.缺课,5休学
                ];
                $info[$k]['lack_num'] = self::getStudentClassListbNum($where_lack);


                $where_pin = [ //待销课
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                    'is_confirm' => 2, //1,已销课程，2未销课程
                ];
                $info[$k]['pin_num'] = self::getStudentClassListbNum($where_pin);

                $where_cease = [ //休学
                    'arrange_course_id' => $v['arrange_course_id'],
                    'is_del' => 1,
                    'student_course_type' => 5// 1等待上课，2已上课，3，请假，4.缺课,5休学
                ];
                $info[$k]['cease_num'] = self::getStudentClassListbNum($where_cease);
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zsc.id', 'zac.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //本学生上课明细字段
    public static function getStudentArrangeCourseInfoField()
    {
        $data = [
            ['prop' => 'course_img', 'name' => '课程名', 'width' => '380', 'state' => ''],
            ['prop' => 'arrange_course_time', 'name' => '课程时间', 'width' => '', 'state' => ''],
            ['prop' => 'enter_num', 'name' => '课程进度', 'width' => '', 'state' => ''],
            ['prop' => 'attend_class_num', 'name' => '到课数', 'width' => '', 'state' => '1'],
            ['prop' => 'leave_num', 'name' => '请假数', 'width' => '', 'state' => '1'],
            ['prop' => 'lack_num', 'name' => '缺课数', 'width' => '', 'state' => '1'],
            ['prop' => 'cease_num', 'name' => '休学数', 'width' => '', 'state' => '1'],
            ['prop' => 'pin_num', 'name' => '待销课', 'width' => '', 'state' => '1'],
        ];
        return jsonResponseSuccess($data);
    }

    //获取本学生本课程信息
    public static function getStudentArrangeCourseCurrent($page = 1, $pageSize = 8)
    {
        $data = input();
        $where = [
            'zsc.arrange_course_id' => $data['arrange_course_id'],
            'zsc.is_del' => 1,
            'zsc.student_id' => $data['student_id'],
        ];
        $table = 'zht_student_class';
        $join = [
            ['yx_zht_arrange_course zac', 'zsc.arrange_course_id = zac.id', 'left'], //班级表 course_id
            ['yx_zht_student_class_list zscl', 'zsc.arrange_course_id = zscl.arrange_course_id', 'left'], //排课信息
            ['yx_zht_order zo', 'zscl.order_id = zo.order_id', 'left'], //订单信息
            ['yx_zht_course zc', 'zac.course_id = zc.id', 'left'], //课程名
            ['yx_zht_category zca', 'zc.category_id = zca.id', 'left'], //一级课目
            ['yx_category_small zcas', 'zc.category_small_id = zcas.id', 'left'], //二级课目 category_small_name
        ];
        $alias = 'zsc';
        $student_class_info = Crud::getRelationData($table, 1, $where, $join, $alias, $order = 'zsc.create_time desc ', $field = 'zo.price,zscl.order_id,zscl.is_confirm,zsc.arrange_course_id,zsc.student_id,zac.course_num,zac.start_arrange_course,zac.end_arrange_course,zc.course_name,zc.course_img,zca.name category_name,zcas.category_small_name,zsc.arrange_course_id', $page, $pageSize);
        if ($student_class_info) {
            $where_enter = [//进度
                'arrange_course_id' => $data['arrange_course_id'],
                'student_id' => $data['student_id'],
                'is_del' => 1,
                'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
            ];
            $enter_num = self::getStudentClassListbNum($where_enter);
            $where_upper = [//上课数
                'arrange_course_id' => $data['arrange_course_id'],
                'student_id' => $data['student_id'],
                'is_del' => 1,
                'student_course_type' => 2// 1等待上课，2已上课，3，请假，4.缺课,5休学
            ];
            $attend_class_num = self::getStudentClassListbNum($where_upper);
            $where_pin = [ //待销课
                'arrange_course_id' => $data['arrange_course_id'],
                'student_id' => $data['student_id'],
                'is_del' => 1,
                'course_enter_type' => 2, //1此节课时间没有过，2时间已过（获取进度数）
                'is_confirm' => 2, //1,已销课程，2未销课程
            ];
            $pin_num = self::getStudentClassListbNum($where_pin);
            //异常数
            $where_unusual = [
                'arrange_course_id' => $data['arrange_course_id'],
                'student_id' => $data['student_id'],
                'student_course_type' => ['NOTIN', [1, 2]],  //1等待上课，2已上课，3，请假，4.缺课，5休学
                'is_del' => 1
            ];
            $unusual_num = self::getStudentClassListbNum($where_unusual);

            $timetable_data = Crud::getData('zht_course_timetable', 2, ['arrange_course_id' => $data['arrange_course_id'], 'is_del' => 1], 'attend_class_num', 'attend_class_num desc');
            $student_class_data = [
                'enter_num' => $enter_num . '/' . $timetable_data[0]['attend_class_num'],
                'attend_class_num' => $attend_class_num . '/' . $timetable_data[0]['attend_class_num'],
                'pin_num' => $pin_num,
                'unusual_num' => $unusual_num,
                'course_img' => $student_class_info['course_img'],
                'course_name' => $student_class_info['course_name'],
                'category_name' => $student_class_info['category_name'],
                'category_small_name' => $student_class_info['category_small_name'],
                'price' => $student_class_info['price'],
            ];
        } else {
            throw new NothingMissException();
        }
        $table = 'zht_student_class_list';
        $where = [
            'zscl.arrange_course_id' => $data['arrange_course_id'],
            'zscl.is_del' => 1,
            'zscl.student_id' => $data['student_id'],
        ];
        $join = [
            ['yx_zht_arrange_course zac', 'zscl.arrange_course_id = zac.id', 'left'], //班级表 course_id
            ['yx_zht_course_timetable zct', 'zscl.course_timetable_id = zct.id', 'left'], //排课时间
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'], //老师表
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'], //教室表
            ['yx_admin_user au', 'zscl.admin_user_id = au.id', 'left'], //管理员
        ];
        $alias = 'zscl';
        $status_arrange_course_list = Crud::getRelationData($table, 2, $where, $join, $alias, $order = 'zscl.create_time desc', $field = 'zac.arrange_course_name,zscl.id,zscl.student_id,au.real_member_name,zscl.student_course_type,zscl.is_confirm,zscl.student_class_remarks,zct.attend_class_num,zct.course_hour,zct.day_time_start,zct.time_slot,t.teacher_nickname,c.classroom_name', $page, $pageSize);
        if ($status_arrange_course_list) {
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zscl.id');
            foreach ($status_arrange_course_list as $k => $v) {
                if ($v['is_confirm'] == 2) { //1,已销课程，2未销课程
                    $is_confirm_name = '未销课程';
                } else {
                    $is_confirm_name = '';
                }
                if ($v['student_course_type'] == 1) { //1等待上课，2已上课，3，请假，4请假已处理，5请假未处理
                    $status_arrange_course_list[$k]['student_course_type_name'] = '等待上课' . '  ' . $is_confirm_name;
                } elseif ($v['student_course_type'] == 2) {
                    $status_arrange_course_list[$k]['student_course_type_name'] = '已上课';
                } elseif ($v['student_course_type'] == 3) {
                    $status_arrange_course_list[$k]['student_course_type_name'] = '请假';
                } elseif ($v['student_course_type'] == 4) {
                    $status_arrange_course_list[$k]['student_course_type_name'] = '缺课';
                } elseif ($v['student_course_type'] == 5) {
                    $status_arrange_course_list[$k]['student_course_type_name'] = '休学';
                }
                $status_arrange_course_list[$k]['arrange_course_time'] = date('Y-m-d H:i:s', $v['day_time_start']) . ' ' . $v['time_slot'];
            }
            $info_data = [
                'info' => $status_arrange_course_list,
                'student_class_data' => $student_class_data,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取本学生本课程信息字段
    public static function getStudentArrangeCourseCurrentField()
    {
        $data = [
            ['prop' => 'attend_class_num', 'name' => '课次', 'width' => '', 'state' => ''],
            ['prop' => 'course_hour', 'name' => '课时', 'width' => '', 'state' => ''],
            ['prop' => 'arrange_course_time', 'name' => '排课时间', 'width' => '', 'state' => ''],
            ['prop' => 'arrange_course_name', 'name' => '班级名称', 'width' => '', 'state' => '1'],
            ['prop' => 'teacher_nickname', 'name' => '授课老师', 'width' => '', 'state' => '1'],
            ['prop' => 'classroom_name', 'name' => '上课教室', 'width' => '', 'state' => '1'],
            ['prop' => 'student_course_type_name', 'name' => '到课状态', 'width' => '', 'state' => '1'],
            ['prop' => 'student_class_remarks', 'name' => '操作备注', 'width' => '', 'state' => '1'],
            ['prop' => 'real_member_name', 'name' => '操作员', 'width' => '', 'state' => '1'],
        ];
        return jsonResponseSuccess($data);
    }


    //获取数目
    public static function getStudentClassListbNum($where)
    {
        $num = Crud::getCount('zht_student_class_list', $where);
        return $num;
    }


}