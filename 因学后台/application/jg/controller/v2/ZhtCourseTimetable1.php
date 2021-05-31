<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/19
 * Time: 12:54
 */

namespace app\jg\controller\v2;


use app\common\controller\IsZhtTime;
use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\ErrorMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Db;

class ZhtCourseTimetable extends BaseController
{
    //添加排课表
    //mem_id 机构ID arrange_course_name 排课程名称  arrange_course_num 班级人数 course_id 课程ID course_num 课程节数  course_hour 课时长度 time_table_array 课程表排课时间
    public static function setZhtCourseTimetable()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input(); //yx_zht_arrange_course
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id']; //yx_zht_arrange_course
            }
            $table = request()->controller();
            //验证课时
            $course_num = self::isCourseHour($data, 3);
            if (!is_array($course_num)) {
                return $course_num;
            }
            //验证是否被别人占用
            self::isTimeTable($data);
            //添加排课信息
            Db::startTrans();
            $arrange_course_data = [
                'mem_id' => $data['mem_id'],
                'arrange_course_name' => $data['arrange_course_name'], //班级名称(排课名称)
                'arrange_course_num' => $data['arrange_course_num'],  //班级人数
                'start_arrange_course' => $data['timetable_time'][0] / 1000,  //开始时间
                'end_arrange_course' => $data['timetable_time'][1] / 1000,  //结束时间
                'course_id' => $data['course_id'][2], //课程ID
                'course_category_id' => $data['course_id'][0],//课程一级ID
                'classroom_category_small_id' => $data['course_id'][1],//课程二级ID
                'course_hour' => $data['course_hour'], //课时
                'course_num' => $course_num['course_num'], //课时
                'course_num_id' => $data['course_id'][3], //课时ID
                'classroom_id' => serialize($data['classroom_id']), //教室分类和ID
                'teacher_id' => serialize($data['teacher_id']), //老师分类和ID
                'time_slot' => serialize($data['time_slot']), //时间段
                'timetable_time' => serialize($data['timetable_time']), //教室分类和ID
                'valuetime' => serialize($data['valuetime']),
                'week' => serialize($data['week']),
            ];
            //取第一条上课程表的上课老师与教室
            $one_teacher_classroom = Crud::getData('zht_course_timetable', 2, ['identifier_time' => $data['identifier_time'], 'is_del' => 1], 'classroom_id,teacher_id', 'id asp', 1, 1000);
            if ($one_teacher_classroom) {
                $arrange_course_data['day_classroom_id'] = $one_teacher_classroom[0]['classroom_id'];
                $arrange_course_data['day_teacher_id'] = $one_teacher_classroom[0]['teacher_id'];
            }
            $arrange_course_id = Crud::setAdd('zht_arrange_course', $arrange_course_data, 2);
            if (!$arrange_course_id) {
                Db::rollback();
                throw new AddMissException();
            }

            //验证这样是否有没有排好的数据
            $where_timetable_data = [
                'is_del' => 1,
                'identifier_time' => $data['identifier_time'],
                'classroom_id' => ['=', ''],
                'teacher_id' => ['=', ''],
            ];
            $zht_course_timetable_data = Crud::getData($table, 2, $where_timetable_data, 'id');
            if ($zht_course_timetable_data) {
                return jsonResponse('3000', '请完善排班信息');
            }

            //添加排课时间
            $arrange_course_where = [
                'is_del' => 1,
                'identifier_time' => $data['identifier_time'],
            ];
            $arrange_course_data = [
                'mem_id' => $data['mem_id'],
                'arrange_course_id' => $arrange_course_id,
            ];
            $zht_course_timetable = Crud::setUpdate($table, $arrange_course_where, $arrange_course_data);
            if (!$zht_course_timetable) {
                Db::rollback();
                throw new AddMissException();
            } else {
                Db::commit();

                return jsonResponseSuccess($arrange_course_id);
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //整体修改
    public static function editZhtCourseTimetable()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input(); //yx_zht_arrange_course
            //验证课时
            $course_num = self::isCourseHour($data, 3);
            if (!is_array($course_num)) {
                return $course_num;
            }
            //验证是否被别人占用
            self::isTimeTable($data);
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id']; //yx_zht_arrange_course
            }
            $table = request()->controller();
            $arrange_course_data = [
//                'mem_id' => $data['mem_id'],
//                'arrange_course_name' => $data['arrange_course_name'], //班级名称(排课名称)
//                'arrange_course_num' => $data['arrange_course_num'],  //班级人数
//                'start_arrange_course' => $data['timetable_time'][0] / 1000,  //开始时间
//                'end_arrange_course' => $data['timetable_time'][1] / 1000,  //结束时间
//                'course_id' => $data['course_id'][2], //课程ID
//                'course_category_id' => $data['course_id'][0],//课程一级ID
//                'classroom_category_small_id' => $data['course_id'][1],//课程二级ID
//                'course_num' => $data['course_num'], //课程节数
//                'course_hour' => $data['course_hour'], //课时
//                'classroom_id' => serialize($data['classroom_id']), //教室分类和ID
//                'teacher_id' => serialize($data['teacher_id']), //老师分类和ID
//                'time_slot' => serialize($data['time_slot']), //时间段
//                'timetable_time' => serialize($data['timetable_time']), //教室分类和ID
//                'valuetime' => serialize($data['valuetime']),
//                'week' => serialize($data['week']),
                'update_time' => time()
            ];
            //取第一条上课程表的上课老师与教室
            $one_teacher_classroom = Crud::getData('zht_course_timetable', 2, ['identifier_time' => $data['identifier_time'], 'is_del' => 1], 'classroom_id,teacher_id', 'id asp', 1, 1000);
            if ($one_teacher_classroom) {
                $arrange_course_data['day_classroom_id'] = $one_teacher_classroom[0]['classroom_id'];
                $arrange_course_data['day_teacher_id'] = $one_teacher_classroom[0]['teacher_id'];
            }

            if (isset($data['arrange_course_name']) && !empty($data['arrange_course_name'])) {
                $arrange_course_data['arrange_course_name'] = $data['arrange_course_name'];
            }
            if (isset($data['arrange_course_num']) && !empty($data['arrange_course_num'])) {
                $arrange_course_data['arrange_course_num'] = $data['arrange_course_num'];
            }
            $arrange_data = Crud::setUpdate('zht_arrange_course', ['id' => $data['id']], $arrange_course_data);
            if (!$arrange_data) {
                throw new AddMissException();
            }
            //添加排课信息
//            Db::startTrans();
            //添加排课时间
            $arrange_course_where = [
                'is_del' => 1,
                'identifier_time' => $data['identifier_time'],
                'arrange_course_id' => null
            ];
            $arrange_course_ids = Crud::getData($table, 2, $arrange_course_where, 'id');
            if ($arrange_course_ids) {
                $arrange_course_datas = [
                    'mem_id' => $data['mem_id'],
                    'arrange_course_id' => $data['id'],
                ];
                $zht_course_timetable = Crud::setUpdate($table, $arrange_course_where, $arrange_course_datas);
                if (!$zht_course_timetable) {
                    throw new AddMissException();
                } else {
//                Db::commit();
                    return jsonResponseSuccess($data['id']);
                }
            } else {
                if ($arrange_data) {
                    return jsonResponseSuccess($data['id']);
                }
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取返回排课时间
    public static function getTimetableTime($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $identifier_time = IsZhtTime::isTimeReturn($data);
        if (empty($identifier_time)) {
            return $identifier_time;
        }
        if ((int)$identifier_time == 0) {
            return $identifier_time;
        }


        $where = [
            'zct.identifier_time' => $identifier_time,
            'zct.is_del' => 1
        ];

        $join = [
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'], //right
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'], //right
        ];
        $alias = 'zct';
        $time_table_data = Crud::getRelationData('zht_course_timetable', $type = 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zct.*,t.teacher_nickname,c.classroom_name,c.province,c.city,c.area,c.address', $page, $pageSize);
        if ($time_table_data) {
            foreach ($time_table_data as $k => $v) {
                $time_table_data[$k]['classroom_address'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $identifier_time = $v['identifier_time'];
                $time_table_data[$k]['timetable_time_name'] = $v['day_time'] * 1000;
                $time_slot = explode("-", $v['time_slot']);
                $time_table_data[$k]['time_slot_name'] = $time_slot;
                $time_table_data[$k]['timetable_time'] = [$v['start_time_slot'] * 1000, $v['end_time_slot'] * 1000];
            }
            $num = Crud::getCountSelNun('zht_course_timetable', $where, $join, $alias, 'zct.id');
            $info_data = [
                'info' => $time_table_data,
                'identifier_time' => $identifier_time,
                'num' => $num,
                'pageSize' => 8,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            return jsonResponse('3000', '操作有误请重新输入1');
        }
    }

    //返回数据库中的课程表
    public static function getTimetableTimeData($page = 1, $pageSize = 8, $identifier_time = '', $course_id = '', $time_data = '', $arrange_course_id = '')
    {
        $where = [
            'zct.is_del' => 1
        ];
        isset($arrange_course_id) && !empty($arrange_course_id) && $where['zct.arrange_course_id'] = $arrange_course_id;
        isset($identifier_time) && !empty($identifier_time) && $where['zct.identifier_time'] = $identifier_time;
        isset($course_id) && !empty($course_id) && $where['zct.course_id'] = $course_id;
        if (isset($time_data) && !empty($time_data)) {
            $start_time = $time_data[0] / 1000;
            $end_time = $time_data[1] / 1000;
            $where['zct.day_time'] = ['between', [$start_time, $end_time]];
        }
        $join = [
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'], //right
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'], //right
        ];
        $alias = 'zct';
        $time_table_data = Crud::getRelationData('zht_course_timetable', $type = 2, $where, $join, $alias, $order = 'zct.day_time_start', $field = 'zct.*,t.teacher_nickname,c.classroom_name,c.province,c.city,c.area,c.address', $page, $pageSize);
        if ($time_table_data) {
            foreach ($time_table_data as $k => $v) {
                $time_table_data[$k]['classroom_address'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $time_table_data[$k]['timetable_time_name'] = $v['day_time'] * 1000;
                $time_slot = explode("-", $v['time_slot']);
                $time_table_data[$k]['time_slot_name'] = $time_slot;
                $time_table_data[$k]['timetable_time'] = [$v['start_time_slot'] * 1000, $v['end_time_slot'] * 1000];
                $time_table_data[$k]['timetable_time'] = [$v['start_time_slot'] * 1000, $v['end_time_slot'] * 1000];
                $time_table_data[$k]['course_id'] = [
                    '0' => $v['course_category_id'],
                    '1' => $v['classroom_category_small_id'],
                    '2' => $v['course_id'],
                    '3' => $v['course_num_id'],
                ];
            }
            $num = Crud::getCountSelNun('zht_course_timetable', $where, $join, $alias, 'zct.id');
            $info_data = [
                'info' => $time_table_data,
                'num' => $num,
                'pageSize' => 8,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new  NothingMissException();
        }
    }

    //返回课表字段
    public static function getTimetableTimeField()
    {
        $data = [
            ['prop' => 'day', 'name' => '上课日期', 'width' => '', 'state' => ''],
            ['prop' => 'time_slot', 'name' => '上课时间', 'width' => '', 'state' => ''],
            ['prop' => 'course_hour', 'name' => '课时', 'width' => '160', 'state' => '1'],
            ['prop' => 'teacher_nickname', 'name' => '授课老师', 'width' => '', 'state' => ''],
            ['prop' => 'classroom_name', 'name' => '上课教室', 'width' => '100', 'state' => ''],
            ['prop' => 'classroom_address', 'name' => '教室地址', 'width' => '100', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //规定时间获取本机构可选的教室
    public static function getTimeClassroom()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            //获取本机教室
            $Classroom = Crud::getData('classroom', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,name');
            if (!$Classroom) {
                throw new NothingMissException();
            }
            $time_slot_array = self::getTimeSlot($data);
            $where = [
                'mem_id' => $data['mem_id'],
                'day_time' => $time_slot_array['day_time'],
                'start_time_slot' => ['between', [$time_slot_array['start_time_slot'], $time_slot_array['end_time_slot']]],
                'end_time_slot' => ['between', [$time_slot_array['start_time_slot'], $time_slot_array['end_time_slot']]],
                'is_del' => 1
            ];
            $array = Crud::getData('zht_course_timetable', 2, $where, 'classroom_id');
            $classroom_id = [];
            if ($array) {
                foreach ($array as $k => $v) {
                    if ($v['classroom_id']) {
                        $classroom_id[] = $v['classroom_id'];
                    }

                }
            }
            //求差集
            foreach ($Classroom as $k => $v) if (in_array($v['id'], $classroom_id)) unset($Classroom[$k]);

            $Classroom_ids = [];
            foreach ($Classroom as $k => $v) {
                $Classroom_ids[] = $v['id'];
            }
            if ($array && is_array($array)) {
                $arr1 = ['0' => $array[0]['classroom_id']];
                $Classroom_ids = array_merge($Classroom_ids, $arr1);
            }


            $where = [
                'is_del' => 1,
                'type' => 1,
            ];
            $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
            if ($info) {
                $mem_data = self::isuserData();
                if (!isset($mem_id) || empty($mem_id)) {
                    $mem_id = $mem_data['mem_id'];
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
                                'classroom_type_name' => ['like', '%' . $vv['label'] . '%'],
                                'id' => ['in', $Classroom_ids]
                            ];
                            $curriculum_info = Crud::getData('classroom', $type = 2, $where, $field = 'id value,classroom_name label', $order = '', $page = '1', $pageSize = '1000');
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                        }
                    } else {
                        $info[$k]['children'] = [];
                    }

                }
//            foreach ($Teacher as $k => $v) if (in_array($v['id'], $teacher_id)) unset($Teacher[$k]);
                return jsonResponseSuccess($info);
            }
        } else {
            throw new ISUserMissException();
        }

    }

    //规定时间获取本机构可选的老师
    public static function getTimeTeacher()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            //获取本机老师
            $Teacher = Crud::getData('teacher', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,teacher_nickname');
            if (!$Teacher) {
                throw new NothingMissException();
            }

            $time_slot_array = self::getTimeSlot($data);
            $where = [
                'mem_id' => $data['mem_id'],
                'day_time' => $time_slot_array['day_time'],
                'start_time_slot' => ['between', [$time_slot_array['start_time_slot'], $time_slot_array['end_time_slot']]],
                'end_time_slot' => ['between', [$time_slot_array['start_time_slot'], $time_slot_array['end_time_slot']]],
                'is_del' => 1
            ];
            $array = Crud::getData('zht_course_timetable', 2, $where, 'classroom_id,teacher_id');
//            $array = Crud::getData('zht_course_timetable', 2, '', 'classroom_id,teacher_id');
            $teacher_id = [];
            if ($array) {
                foreach ($array as $k => $v) {
                    if ($v['teacher_id']) {
                        $teacher_id[] = $v['teacher_id'];
                    }
                }
            }
            //求差集
            foreach ($Teacher as $k => $v) if (in_array($v['id'], $teacher_id)) unset($Teacher[$k]);
            $Teacher_ids = [];
            foreach ($Teacher as $k => $v) {
                $Teacher_ids[] = $v['id'];
            }

            if ($array && is_array($array)) {
                $arr1 = ['0' => $array[0]['teacher_id']];
                $Teacher_ids = array_merge($Teacher_ids, $arr1);
            }

            $where = [
                'is_del' => 1,
                'type' => 1,
            ];
            $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
            if ($info) {
                $mem_data = self::isuserData();
                if (!isset($mem_id) || empty($mem_id)) {
                    $mem_id = $mem_data['mem_id'];
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
                                'teacher_type_name' => ['like', '%' . $vv['label'] . '%'],
                                'id' => ['in', $Teacher_ids]
                            ];
                            $curriculum_info = Crud::getData('teacher', $type = 2, $where, $field = 'id value,teacher_nickname label', $order = '', $page = '1', $pageSize = '1000');
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                        }
                    } else {
                        $info[$k]['children'] = [];
                    }

                }
//            foreach ($Teacher as $k => $v) if (in_array($v['id'], $teacher_id)) unset($Teacher[$k]);
                return jsonResponseSuccess($info);
            }
        } else {
            throw new ISUserMissException();
        }

    }

    public static function getTimeClassroomTeachers()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            //获取本机教室
            $Classroom = Crud::getData('classroom', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,name');
            if (!$Classroom) {
                throw new NothingMissException();
            }
            //获取本机老师
            $Teacher = Crud::getData('teacher', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,name');
            if (!$Teacher) {
                throw new NothingMissException();
            }
            $time_slot_array = self::getTimeSlot($data);
            $array = [];
            foreach ($time_slot_array as $k => $v) {
                $where = [
                    'mem_id' => $data['mem_id'],
                    'day_time' => $v['day_time'],
                    'start_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]],
                    'end_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]],
                    'is_del' => 1
                ];
                $array[] = Crud::getData('zht_course_timetable', $where, 'classroom_id,teacher_id');
            }
            $classroom_id = [];
            $teacher_id = [];
            if ($array) {
                foreach ($array as $k => $v) {
                    if ($v['classroom_id']) {
                        $classroom_id[] = $v['classroom_id'];
                    }
                    if ($v['$teacher_id']) {
                        $teacher_id[] = $v['$teacher_id'];
                    }
                }
            }
            //求差集
            foreach ($Classroom as $k => $v) if (in_array($v['id'], $classroom_id)) unset($Classroom[$k]);


            //$Classroom
            foreach ($Teacher as $k => $v) if (in_array($v['id'], $teacher_id)) unset($Teacher[$k]);
            $info = [
                'classroom' => $Classroom,
                'teacher' => $Teacher,
            ];
            return jsonResponseSuccess($info);
        } else {
            throw new ISUserMissException();
        }

    }


    //添加单条时间 ['classroom_id'][2]
    public static function addTimeTableSingle()
    {
        $data = input();
        $timeSlotTime = self::isTimeSlotOverlap($data);
        if (!is_array($timeSlotTime)) {
            return $timeSlotTime;
        }
        $course_hour = self::isCourseHourOne($data, 1);
        if (!is_array($course_hour)) {
            return $course_hour;
        }
        $data['course_category_id'] = $data['course_id'][0];
        $data['course_category_small_id'] = $data['course_id'][1];
        $data['course_id'] = $data['course_id'][2];
        $data['classroom_category_id'] = $data['classroom_id'][0];
        $data['classroom_category_small_id'] = $data['classroom_id'][1];
        $data['classroom_id'] = $data['classroom_id'][2];
        $data['teacher_category_id'] = $data['teacher_id'][0];
        $data['teacher_category_small_id'] = $data['teacher_id'][1];
        $data['teacher_id'] = $data['teacher_id'][2];
        $data['day'] = $timeSlotTime['day'];
        $data['day_time'] = $timeSlotTime['day_time'];
        $data['day_time_start'] = $timeSlotTime['day_time'] + $timeSlotTime['start_time_slot'];
        $data['day_time_end'] = $timeSlotTime['day_time'] + $timeSlotTime['end_time_slot'];
        $data['week'] = IsZhtTime::getWeekNum($data['timetable_time'][0] / 1000);
        $data['start_time_slot'] = $timeSlotTime['start_time_slot'];
        $data['end_time_slot'] = $timeSlotTime['end_time_slot'];
        $data['time_slot'] = $timeSlotTime['start_time_slot_see'] . '-' . $timeSlotTime['end_time_slot_see'];  //时间段展示区间
        $timetable_data = Crud::setAdd('zht_course_timetable', $data);
        if ($timetable_data) {
            return jsonResponseSuccess($timetable_data);
        } else {
            throw new AddMissException();
        }
    }

    //编辑单条时间 ['classroom_id'][2]
    public static function editTimeTableSingle()
    {
        $data = input();
        $timeSlotTime = self::isTimeSlotOverlap($data);
        if (!is_array($timeSlotTime)) {
            return $timeSlotTime;
        }
        $data['day'] = $timeSlotTime['day'];
        $data['day_time'] = $timeSlotTime['day_time'];
        $data['week'] = IsZhtTime::getWeekNum($timeSlotTime['day_time']);
        $data['start_time_slot'] = $timeSlotTime['start_time_slot'];
        $data['end_time_slot'] = $timeSlotTime['end_time_slot'];
        $day_time_start = date('Y-m-d', $timeSlotTime['day_time']) . $timeSlotTime['start_time_slot_see'];
        $day_time_end = date('Y-m-d', $timeSlotTime['day_time']) . $timeSlotTime['end_time_slot_see'];
        $data['day_time_start'] = strtotime($day_time_start);
        $data['day_time_end'] = strtotime($day_time_end);
        $data['time_slot'] = $timeSlotTime['start_time_slot_see'] . '-' . $timeSlotTime['end_time_slot_see'];  //时间段展示区间
        $data['classroom_category_id'] = $data['classroom_id'][0];
        $data['classroom_category_small_id'] = $data['classroom_id'][1];
        $data['classroom_id'] = $data['classroom_id'][2];
        $data['teacher_category_id'] = $data['teacher_id'][0];
        $data['teacher_category_small_id'] = $data['teacher_id'][1];
        $data['teacher_id'] = $data['teacher_id'][2];
//        $data['course_category_id'] = $data['classroom_id'][0];
//        $data['course_category_small_id'] = $data['classroom_id'][1];
        $course_hour_num = Crud::getData('zht_course_timetable', 1, ['id' => $data['time_id'], 'is_del' => 1], 'course_hour');
        $course_hour = self::isCourseHour($data, 2, $course_hour_num['course_hour']);
        if (!is_array($course_hour)) {
            return $course_hour;
        }
        $timetable_data = Crud::setUpdate('zht_course_timetable', ['id' => $data['time_id']], $data);
        if ($timetable_data) {
            return jsonResponseSuccess($timetable_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //删除单条时间
    public static function delTimeTableSingle($time_id)
    {
        $del_timetable = Crud::setUpdate('zht_course_timetable', ['id' => ['in', $time_id]], ['is_del' => 2, 'update_time' => time()]);
        if ($del_timetable) {
            return jsonResponseSuccess($del_timetable);
        } else {
            throw new DelMissException();
        }
    }


    //重组规定时间获取(当前时间与时间段组合)
    public static function getTimeSlot($data)
    {
//        $data = input();
        if (is_array($data['timetable_time'])) {
            $timetable_time = $data['timetable_time'][0] / 1000;
        } else {
            $timetable_time = $data['timetable_time'] / 1000;
        }

        //获取时间段
        $Timeslot_data = IsZhtTime::timeSlotone($data);
        $time_array = [
            'day_time' => $timetable_time,
            'day' => date('Y-m-d', $timetable_time),
            'start_time_slot' => $Timeslot_data['start_time_slot'],
            'end_time_slot' => $Timeslot_data['end_time_slot'],
            'start_time_slot_see' => $Timeslot_data['start_time_slot_see'],
            'end_time_slot_see' => $Timeslot_data['end_time_slot_see'],
        ];
        return $time_array;
    }

    //验证单个添加与修改时间是否重叠
    public static function isTimeSlotOverlap($data)
    {

        $timeSlotTime = self::getTimeSlot($data);
        $start_time_slot = $timeSlotTime['start_time_slot'] + $timeSlotTime['day_time'];
        $end_time_slot = $timeSlotTime['end_time_slot'] + $timeSlotTime['day_time'];

        //获取当前时间和当前教室和老师数据
        $where = [
            'classroom_id' => $data['classroom_id'][2],
            'teacher_id' => $data['teacher_id'][2],
            'day_time' => $timeSlotTime['day_time'],
            'arrange_course_id' => ['<>', ''],
            'is_del' => 1
        ];
        $last_time_data = Crud::getData('zht_course_timetable', 2, $where, '*', '', 1, 100000);
        if ($last_time_data) {
            //获取本节课时间是否在本节课时间内存在
            $where_identifier = [
                'classroom_id' => $data['classroom_id'][2],
                'teacher_id' => $data['teacher_id'][2],
                'day_time' => $timeSlotTime['day_time'],
                'is_del' => 1,
                'identifier_time' => $data['identifier_time']
            ];
            $identifier_time_data = Crud::getData('zht_course_timetable', 2, $where_identifier, '*', '', 1, 100000);
            if ($identifier_time_data) {
                //


                return jsonResponse('3000', '时间区间重叠，请重试');
            }
            foreach ($last_time_data as $k => $v) {
                $data_time = self::is_time_cross($start_time_slot, $end_time_slot, $v['day_time_start'], $v['day_time_end']);
                if ($data_time == true) {
                    return jsonResponse('3000', '时间区间重叠，请重试');
                }
            }
        } else {
            $where_identifier = [
                'classroom_id' => $data['classroom_id'][2],
                'teacher_id' => $data['teacher_id'][2],
                'day_time' => $timeSlotTime['day_time'],
                'is_del' => 1,
                'identifier_time' => $data['identifier_time']
            ];
            $identifier_time_data = Crud::getData('zht_course_timetable', 2, $where_identifier, '*', '', 1, 100000);
            if ($identifier_time_data) {
                foreach ($identifier_time_data as $k => $v) {
                    $data_time = self::is_time_cross($start_time_slot, $end_time_slot, $v['day_time_start'], $v['day_time_end']);
                    if ($data_time == true) {
                        return jsonResponse('3000', '时间区间重叠，请重试');
                    }
                }
            }
        }
        return $timeSlotTime;
    }


    //判断两个时间区间是否重叠
    public static function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')
    {
        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 > 0) {
                return false;
            } elseif ($status2 < 0) {
                return true;
            } else {
                return false;
            }
        } elseif ($status < 0) {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 > 0) {
                return true;
            } else if ($status2 < 0) {
                return false;
            } else {
                return false;
            }
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 == 0) {
                return false;
            } else {
                return true;
            }
        }
    }

    //验证课时是否正确（有没有超出或不足）$type 1为添加2为修改
    public static function isCourseHour($data, $type_hour = 1, $course_hour_num = '')
    {
        if (is_array($data['course_id']) && isset($data['course_id'][3]) && !empty($data['course_id'][3])) {
            $course_num_id = $data['course_id'][3];
        }
        if (isset($data['course_num']) && !empty($data['course_num'])) {
            $course_num = $data['course_num'];
        } else {
            //查看课程节数
            $course_section_num = Crud::getData('zht_course_num', 1, ['id' => $course_num_id, 'is_del' => 1], 'course_section_num');
            $course_num = $course_section_num['course_section_num'];
        }
        $table = request()->controller();
        //求本课有几节课课
        $arrange_course_where = [
            'is_del' => 1,
            'identifier_time' => $data['identifier_time'],
        ];
        $Course_hour = Crud::getData($table, 2, $arrange_course_where, 'course_hour', '', 1, 10000000);
        $sum_class_num = 0;
        if ($Course_hour) {
            foreach ($Course_hour as $k => $v) {
                $sum_class_num += $v['course_hour'];
            }
        } else {
            return jsonResponse('3001', '输入有误，请重新输入');
        }
        if ($type_hour == 1) {
            $sum_class_num = $sum_class_num + $data['course_hour'];
        } elseif ($type_hour == 2) {
            $sum_class_num = $sum_class_num + $data['course_hour'] - $course_hour_num;
        }
        //加当前修改的
        if ($course_num < $sum_class_num) {
            $num = $sum_class_num - $course_num;
            return jsonResponse('3002', '你选择的课次超' . $num . '课时');
        } elseif ($course_num > $sum_class_num) {
            if ($type_hour == 2) {
                $array = [$course_num];
                return $array;
            } else {
                $num = $course_num - $sum_class_num;
                return jsonResponse('3002', '你选择的课次少于' . $num . '课时');
            }
        } else {
            $array = ['course_num' => $course_num];
            return $array;
        }
    }

    //验证课时是否正确（有没有超出或不足）$type 1为添加2为修改
    public static function isCourseHourOne($data, $type_hour = 1, $course_hour_num = '')
    {
        if (is_array($data['course_id']) && isset($data['course_id'][3]) && !empty($data['course_id'][3])) {
            $course_num_id = $data['course_id'][3];
        }
        if (isset($data['course_num']) && !empty($data['course_num'])) {
            $course_num = $data['course_num'];
        } else {
            //查看课程节数
            $course_section_num = Crud::getData('zht_course_num', 1, ['id' => $course_num_id, 'is_del' => 1], 'course_section_num');
            $course_num = $course_section_num['course_section_num'];
        }
        $table = request()->controller();
        //求本课有几节课课
        $arrange_course_where = [
            'is_del' => 1,
            'identifier_time' => $data['identifier_time'],
        ];
        $Course_hour = Crud::getData($table, 2, $arrange_course_where, 'course_hour', '', 1, 10000000);
        $sum_class_num = 0;
        if ($Course_hour) {
            foreach ($Course_hour as $k => $v) {
                $sum_class_num += $v['course_hour'];
            }
        } else {
            return jsonResponse('3001', '输入有误，请重新输入');
        }
        if ($type_hour == 1) {
            $sum_class_num = $sum_class_num + $data['course_hour'];
        } elseif ($type_hour == 2) {
            $sum_class_num = $sum_class_num + $data['course_hour'] - $course_hour_num;
        }
        //加当前修改的
        if ($course_num < $sum_class_num) {
            $num = $sum_class_num - $course_num;
            return jsonResponse('3002', '你选择的课次超' . $num . '课时');
        } elseif ($course_num >= $sum_class_num) {
            $array = ['course_num' => $course_num];
            return $array;
        }
    }

    //验证课时是否被占用
    public static function isTimeTable($data)
    {
        $table = request()->controller();
        $arrange_course_where = [
            'is_del' => 1,
            'identifier_time' => $data['identifier_time'],
        ];
        //在此验证此时间时否已被别人占用
        $isTimeTable = Crud::getData($table, 2, $arrange_course_where, '*', '', 1, 1000000);
        if (!$isTimeTable) {
            return jsonResponse('3000', '数据有误请重试');
        }
        //验证数据是否完整
        foreach ($isTimeTable as $k => $v) {
            if ($v['classroom_id'] == '' || $v['teacher_id'] == '') {
                return jsonResponse('3000', '数据不完整，请完善');
            }
        }

        //循环对比此时间是否被别人占用
        foreach ($isTimeTable as $k => $v) {
            $where = [
                'mem_id' => ['<>', ''],
                'arrange_course_id' => ['<>', ''],
                'classroom_id' => $v['classroom_id'], //教室ID
                'teacher_id' => $v['teacher_id'], //老师ID
                'day_time_start' => $v['day_time_start'], //时间段开始时间
                'day_time_end' => $v['day_time_end'], //时间段结束始时间
                'is_del' => 1,
            ];
            $course_timetable_data = Crud::getData('zht_course_timetable', 1, $where, 'id,identifier_time');
            if ($course_timetable_data) {
                if ($course_timetable_data['identifier_time'] != $data['identifier_time']) {
                    Crud::setUpdate('zht_course_timetable', ['id' => $v['id']], ['classroom_id' => 0, 'teacher_id' => 0]);
                }
            }
        }

        if ($course_timetable_data) {
            return jsonResponse('2000', '时间被占用');
        }
    }


}