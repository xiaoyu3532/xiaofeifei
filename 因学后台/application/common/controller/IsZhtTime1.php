<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/14 0014
 * Time: 11:37
 */

namespace app\common\controller;

use app\common\model\Crud;

class IsZhtTime
{
    //返回前端可选时间段
    //$type 1为批量修改返回，2为单个时间段修改
    //$someday 修改单个时间段的某一天
    public static function isTimeReturn($data)
    {
        //获取天数
        $Section_time_array = self::getDay($data['timetable_time'][0] / 1000, $data['timetable_time'][1] / 1000);
        //去除未选中的星期时间
        $Week_data = self::getWeek($data, $Section_time_array);
        //将时间添加时间段
        $Timeslot_data = self::timeSlot($data, $Week_data);
        //返回前台数据 验证是否可以排课
        $Plan_course = self::planCourse($data, $Timeslot_data);


        return $Plan_course;
    }

    //存课程时间进行筛选
    public static function TimeScreen($data)
    {
        $day_num = self::getDay($data['start_time'], $data['end_time']);
        $merge_array = [];
        foreach ($data['selection_array']['morning'] as $mk => $mv) {
            $merge_array[] = [
                'time_slot' => $mv,
                'type' => 1,
            ];
        }
        foreach ($data['selection_array']['afternoon'] as $mk => $mv) {
            $merge_array[] = [
                'time_slot' => $mv,
                'type' => 2,
            ];
        }
        foreach ($data['selection_array']['night'] as $mk => $mv) {
            $merge_array[] = [
                'time_slot' => $mv,
                'type' => 3,
            ];
        }

        //添加每天的信息
        $list_array = [];
        foreach ($day_num as $dk => $dv) {
            foreach ($merge_array as $mk => $mv) {
                $list_array [] = [
                    'day' => $dv,
                    'time_slot' => $mv['time_slot'],
                    'type' => $mv['type']
                ];
            }
        }
        return $list_array;

    }

    public static function TimeScreens($data)
    {
        $day_num = self::getDay($data['start_time'], $data['end_time']);

        $merge_array = [];
        foreach ($data['CourseObj'] as $k => $v) {
            foreach ($v['morning'] as $mk => $mv) {
                if ($mv['current'] == 1) {
                    $merge_array[] = $mv;
                }
            }
            foreach ($v['afternoon'] as $mk => $mv) {
                if ($mv['current'] == 1) {
                    $merge_array[] = $mv;
                }
            }
            foreach ($v['night'] as $mk => $mv) {
                if ($mv['current'] == 1) {
                    $merge_array[] = $mv;
                }
            }
        }

        //添加每天的信息
        $list_array = [];
        foreach ($day_num as $dk => $dv) {
            $aa = [];
            foreach ($merge_array as $mk => $mv) {
                $aa [] = [
                    'day' => $dv,
                    'time_slot' => $mv['time_slot'],
                    'type' => $mv['type'],
                    'current' => $mv['current'],
                ];
            }
            $list_array = $aa;
        }
        return $list_array;

    }

    //使用开始时间与结束时间算出时间
    public static function getDay($start_time, $end_time)
    {
        $i = 0;
        $section_time_array = []; //区间天数
        while ($start_time <= $end_time) {
//            $arr[$i]=date('Ymd',$start_time); //转成时间
            $section_time_array[$i] = (int)$start_time;
            $start_time = strtotime('+1 day', $start_time);
            $i++;
        }
        return $section_time_array;
    }

    //修改筛选接口
    public static function getselection($day, $course_id)
    {
        if ($course_id) {
            $selection_array = [];
            $where = [
                'course_id' => $course_id,
                'is_del' => 1,
                'day' => $day,
//                    'type' => $i, //1上午，2下午，3中午
            ];
            $table = 'course_timetable';
            $array = Crud::getData($table, $type = 2, $where, $field = 'time_slot,day,type', $order = '', $page = '1', $pageSize = '10000');
            if ($array) {
                foreach ($array as $k => $v) {
                    if ($v['type'] == 1) {
                        $selection_array['morning'][] = $v['time_slot'];
                    }
                    if ($v['type'] == 2) {
                        $selection_array['afternoon'][] = $v['time_slot'];
                    }
                    if ($v['type'] == 3) {
                        $selection_array['night'][] = $v['time_slot'];
                    }
                }
                if (empty($selection_array['morning'])) {
                    $selection_array['morning'] = [];
                }
                if (empty($selection_array['afternoon'])) {
                    $selection_array['afternoon'] = [];
                }
                if (empty($selection_array['night'])) {
                    $selection_array['night'] = [];
                }
            } else {
                $selection_array = [
                    'morning' => [],
                    'afternoon' => [],
                    'night' => [],
                ];
            }
        } else {
            $selection_array = [
                'morning' => [],
                'afternoon' => [],
                'night' => [],
            ];
        }
        return $selection_array;


    }

    //获取勾选项
    public static function getTtimeParagraph()
    {
        for ($ii = 1; $ii <= 32; $ii++) {
            if ($ii <= 12) {
                $type = 1;
            } elseif (12 < $ii && $ii <= 24) {
                $type = 2;
            } elseif (24 < $ii) {
                $type = 3;
            }
            $time_paragraph_array[] = [
                'time_slot' => $ii,
                'type' => $type,
                'selection' => 2, //1选中(灰色)，2正常
                'current' => 2, //1选中，2正常
            ];
        }
        return $time_paragraph_array;
    }

    //返回本课程已选时间段
    public static function getTitmselection($data, $time_paragraph_array, $type = 1, $someday = '')
    {
        if (!empty($data['course_id'])) {
            if ($type == 1) {
                $day = self::getDay($data['start_time'], $data['end_time']);
                $where = [
                    'course_id' => $data['course_id'],
                    'is_del' => 1,
                    'day' => $day[0],
                ];
            } elseif ($type == 2) {
                $where = [
                    'course_id' => $data['course_id'],
                    'is_del' => 1,
                    'day' => $someday,
                ];
            }

            $table = 'course_timetable';
            $array = Crud::getData($table, $type = 2, $where, $field = 'time_slot', $order = '', $page = '1', $pageSize = '10000');
            if ($array) {
                foreach ($array as $k => $v) {
                    foreach ($time_paragraph_array as $pk => $pv) {
                        if ($v['time_slot'] == $pv['time_slot']) {
                            $time_paragraph_array[$pk]['selection'] = 2;
                        }
                    }
                }
                return $time_paragraph_array;
            } else {
                return $time_paragraph_array;
            }
        }
    }

    //返回前端可选时间段
    //$type 1为批量修改返回，2为单个时间段修改
    //$someday 修改单个时间段的某一天
    //classroom_type 1机构教室，2综合体教室，3社区教室
    public static function isTimeReturns($data)
    {
        //获取时间区间数组
        $time_paragraph_array = self::getTtimeParagraph($data);
        (isset($course_id) && !empty($course_id)) && $where['course_id'] = $course_id; //课程ID
        (isset($data['classroom_id']) && !empty($data['classroom_id'])) && $where['classroom_id'] = $data['classroom_id']; //教室ID
        (isset($data['teacher_id']) && !empty($data['teacher_id'])) && $where['teacher_id'] = $data['teacher_id']; //老师ID
        if (isset($data['classroom_type']) && !empty($data['classroom_type'])) {
            if ($data['classroom_type'] == 2) {
                $where = [
                    'is_del' => 1,
                    'day' => $data['someday'],
                    'classroom_type' => 2,
                ];
            } elseif ($data['classroom_type'] == 3) {
                $where = [
                    'is_del' => 1,
                    'day' => $data['someday'],
                    'classroom_type' => 3,
                ];
            }

        } else {
            $where = [
                'is_del' => 1,
                'day' => $data['someday'],
            ];
        }
        $table = 'course_timetable';
        //获取某天不可以选的
        $selection_array = Crud::getData($table, $type = 2, $where, $field = 'day,time_slot,type', $order = '', $page = '1', $pageSize = '100000');

        //返回前端不能选择的时间段
        foreach ($time_paragraph_array as $tpk => $tpv) {
            foreach ($selection_array as $stk => $stv) {
                if ($tpv['time_slot'] == $stv['time_slot']) {
                    $time_paragraph_array[$tpk]['selection'] = 1;
                }
            }
        }
        //如果修改本课程时，选中字段可以修改
        if (!empty($data['course_id'])) {
            $time_paragraph_array = self::getTitmselection($data, $time_paragraph_array, $type = 2, $data['someday']);
        }
        //进行上下午晚上分组
        $last_array = [];
        foreach ($time_paragraph_array as $tpak => $tpav) {
            if ($tpav['type'] == 1) {
                $last_array['morning'][] = $tpav;
            } elseif ($tpav['type'] == 2) {
                $last_array['afternoon'][] = $tpav;
            } elseif ($tpav['type'] == 3) {
                $last_array['night'][] = $tpav;
            }
        }
        //组合数组
        $selection_array = self::getselection($data['someday'], $data['course_id']); //开始第一天,课程ID
        $return_data = [
            'format_array' => $last_array,
            'selection_array' => $selection_array,
        ];
        return $return_data;
    }

    //返回选重星期时间
    public static function getWeek($data, $section_time_array)
    {
//        $weekarray=array("日","一","二","三","四","五","六");
        $weekarray = array("0", "1", "2", "3", "4", "5", "6");
        $week_data = [];

        foreach ($data['week'] as $k => $v) {
            foreach ($section_time_array as $kk => $vv) {
                $day = date('Y-m-d', $vv);
                if ($v == $weekarray[date("w", strtotime($day))]) {
                    $week_data[] = [
                        'week' => $v,
                        'day_time' => $vv,
                        'day' => $day,
                    ];
                }
            }
        }
        return $week_data;

    }

    //将每天赋时间段
    public static function timeSlot($data, $week_data)
    {
        //获取时间段 传入时间段获取当前的
        $when_present = date('Y-m-d', time());
        foreach ($data['time_slot'] as $k => $v) {
            $data['time_slot'][$k] = [
                '0' => strtotime($when_present . ' ' . $v[0]),
                '1' => strtotime($when_present . ' ' . $v[1]),
            ];
            //time_slot: [["06:30", "07:00"], ["07:30", "08:00"]]
        }
        //传入时间段当天的开始时间
        $time_day = strtotime($when_present);
        //求时间段
        $time_slot = [];
        foreach ($data['time_slot'] as $k => $v) {
            foreach ($v as $kk => $vv) {
                $slot = $vv - $time_day;
                $time_slot[$k][$kk]['time'] = $slot; //时间段
                $time_slot[$k][$kk]['slot'] = date('H:i', $vv); //展示用

            }
        }
//        dump($time_slot);exit;
        if (isset($data['classroom_id'][2]) && !empty($data['classroom_id'][2])) {
            $classroom_id = $data['classroom_id'][2];
        } else {
            $classroom_id = '';
        }
        if (isset($data['teacher_id'][2]) && !empty($data['teacher_id'][2])) {
            $teacher_id = $data['teacher_id'][2];
        } else {
            $teacher_id = '';
        }

        //将时间段与时间组合
        $time_array = [];
        foreach ($time_slot as $k => $v) { //时间段循环
            foreach ($week_data as $kk => $vv) {
                $time_array[] = [
                    'classroom_id' => $classroom_id,//教室ID
                    'teacher_id' => $teacher_id,//老师ID
                    'week' => $vv['week'], //星期几
                    'day_time' => $vv['day_time'], //当前时间
                    'day' => $vv['day'], //当前时间展示
                    'start_time_slot' => $v[0]['time'], //开始时间段
                    'end_time_slot' => $v[1]['time'],  //结束时间段
                    'time_slot' => $v[0]['slot'] . '-' . $v[1]['slot'], //时间段展示区间
                ];
            }
        }
        $time_array = array_sort($time_array, 'day', SORT_ASC);
        return $time_array;
    }

    //获取时间段
    public static function timeSlotone($data)
    {
        $data['time_slot'] = [ //模拟传入值
            '0' => ['0' => 1587285000, '1' => 1587286800],  //时间段 2020-04-19 16:30:00 时间段 2020-04-19 17:00:00
            '1' => ['0' => 1587294000, '1' => 1587301200],  //时间段 2020-04-19 19:00:00 时间段 2020-04-19 21:00:00
        ];
        //获取时间段 传入时间段获取当前的
        $day = date('Y-m-d', $data['time_slot'][0][0]);
        //传入时间段当天的开始时间
        $time_day = strtotime($day);
        //求时间段
        $time_slot = [];
        foreach ($data['time_slot'] as $k => $v) {
            foreach ($v as $kk => $vv) {
                $slot = $vv - $time_day;
                $time_slot[$k][$kk]['time'] = $slot; //时间段
                $time_slot[$k][$kk]['slot'] = date('H:i', $vv); //展示用

            }
        }

        return $time_slot;
    }

    public static function planCourse($data, $Timeslot_data)
    {
        //验证是否可以排课
        foreach ($Timeslot_data as $k => $v) {
            $where = [
                'classroom_id' => $v['classroom_id'], //教室ID
                'teacher_id' => $v['teacher_id'], //老师ID
                'day_time' => $v['day_time'], //当前时间
                'start_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]], //时间段开始时间
                'end_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]], //时间段结束始时间
                'is_del' => 1,
            ];
            $course_timetable_data = Crud::getData('zht_course_timetable', 1, $where, 'id');
            if ($course_timetable_data) {
                $Timeslot_data[$k]['classroom_id'] = '';
                $Timeslot_data[$k]['teacher_id'] = '';
            }
        }
        //组合数组返回前台将空的以'-'代替
        foreach ($Timeslot_data as $k => $v) {
            $teacher_where = [
                'id' => $v['teacher_id'], //老师ID
                'is_del' => 1,
            ];
            $teacher_data = Crud::getData('teacher', 1, $teacher_where, 'name');
            if ($teacher_data && !empty($teacher_data['name'])) {
                $Timeslot_data[$k]['teacher_name'] = $teacher_data['name'];
            } else {
                $Timeslot_data[$k]['teacher_name'] = '-';
            }
            $teacher_where = [
                'id' => $v['classroom_id'], //教室ID
                'is_del' => 1,
            ];
            $classroom_data = Crud::getData('classroom', 1, $teacher_where, 'name,province,city,area,address');
            if ($classroom_data && !empty($classroom_data['name'])) {
                $Timeslot_data[$k]['classroom_name'] = $classroom_data['name'];
            } else {
                $Timeslot_data[$k]['classroom_name'] = '-';
            }
            if ($classroom_data && !empty($classroom_data['province'])) {
                $Timeslot_data[$k]['classroom_address'] = $classroom_data['province'] . $classroom_data['city'] . $classroom_data['area'] . $classroom_data['address'];
            } else {
                $Timeslot_data[$k]['classroom_address'] = '-';
            }
            $Timeslot_data[$k]['course_hour'] = $data['course_hour'];
        }
        return $Timeslot_data;
    }


}