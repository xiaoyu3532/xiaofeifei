<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/14 0014
 * Time: 11:37
 */

namespace app\common\controller;

use app\common\model\Crud;
use think\Db;

class IsZhtTime
{
    //返回前端可选时间段
    //$type 1为批量修改返回，2为单个时间段修改
    //$someday 修改单个时间段的某一天
    public static function isTimeReturn($data)
    {
        //获取天数
        $Section_time_array = self::getDay($data['timetable_time'][0] / 1000, $data['timetable_time'][1] / 1000);
        if (empty($Section_time_array)) {
            return jsonResponse('3000', '时间选择有误');
        }
        //去除未选中的星期时间
        $Week_data = self::getWeek($data, $Section_time_array);
        if (empty($Week_data)) {
            return jsonResponse('3000', '选择星期有误');
        }
        //将时间添加时间段
        $Timeslot_data = self::timeSlot($data, $Week_data);
        if (empty($Timeslot_data)) {
            return jsonResponse('3000', '选择时间段有误');
        }
        //返回前台数据 验证是否可以排课
        $Plan_course = self::planCourse($data, $Timeslot_data);
        if (empty($Plan_course)) {
            return jsonResponse('3000', '时间选择有误');
        }
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

    //返回星期数字 $time_data
    public static function getWeekNum($time_data)
    {
//        $weekarray=array("日","一","二","三","四","五","六");
        $weekarray = array("0", "1", "2", "3", "4", "5", "6");
//        $week = $weekarray[date("w",strtotime("2011-11-11"))];
        $week = $weekarray[date("w", $time_data)];

        return $week;

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
        if (isset($data['course_id'][2]) && !empty($data['course_id'][2])) {
            $course_id = $data['course_id'][2];
        } else {
            $course_id = '';
        }

        //将时间段与时间组合
        $time_array = [];
        foreach ($time_slot as $k => $v) { //时间段循环
            foreach ($week_data as $kk => $vv) {
                $time_array[] = [
                    'classroom_id' => $classroom_id,//教室ID
                    'teacher_id' => $teacher_id,//老师ID
                    'course_id' => $course_id,//课程ID
                    'week' => $vv['week'], //星期几
                    'day_time' => $vv['day_time'], //当前时间
                    'day_time_start' => $vv['day_time'] + $v[0]['time'],//当前开始时间
                    'day_time_end' => $vv['day_time'] + $v[1]['time'],//当前开始时间
                    'day' => $vv['day'], //当前时间展示
                    'start_time_slot' => $v[0]['time'], //开始时间段
                    'end_time_slot' => $v[1]['time'],  //结束时间段
                    'time_slot' => $v[0]['slot'] . '-' . $v[1]['slot'], //时间段展示区间
                ];
            }
        }
        $time_array = array_sort($time_array, 'day', SORT_ASC);
        $course_num = Crud::getData('zht_course_num', 1, ['id' => $data['course_id'][3], 'is_del' => 1], 'course_section_num');
        //求本课有几节课课
        $class_num = $course_num['course_section_num'] / $data['course_hour'];
        if ($class_num <= 0) {
            return jsonResponse('3000', '输入有误，请重新输入');
        }

        //求排课总课时
        $time_data = count($time_array) - $class_num;
        if ($time_data > 0) {
            for ($i = 0; $i < $time_data; $i++) {
                array_pop($time_array);
            }
        }
        return $time_array;
    }

    //将第天的时间段转成时间戳多个
    public static function timeSlotTime($data = '')
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
        //将时间段与时间组合
        $time_array = [];
        foreach ($time_slot as $k => $v) { //时间段循环
            $time_array[] = [
                'week' => $vv['week'], //星期几
                'day_time' => $vv['day_time'], //当前时间
                'day' => $vv['day'], //当前时间展示
                'start_time_slot' => $v[0]['time'], //开始时间段
                'end_time_slot' => $v[1]['time'],  //结束时间段
                'time_slot' => $v[0]['slot'] . '-' . $v[1]['slot'], //时间段展示区间
            ];
        }
        return $time_slot;
    }

    //获取时间段
    public static function timeSlotone($data)
    {

//        $data['time_slot'] = ['6:00', '6:30'];
//        $data['time_slot'] = [ //模拟传入值
//            '0' => ['0' => 1587285000, '1' => 1587286800],  //时间段 2020-04-19 16:30:00 时间段 2020-04-19 17:00:00
//            '1' => ['0' => 1587294000, '1' => 1587301200],  //时间段 2020-04-19 19:00:00 时间段 2020-04-19 21:00:00
//        ];
        //获取时间段 传入时间段获取当前的
        $when_present = date('Y-m-d', time());
        $start_time_slot = strtotime($when_present . ' ' . $data['time_slot'][0]);
        $end_time_slot = strtotime($when_present . ' ' . $data['time_slot'][1]);
        //time_slot: [["06:30", "07:00"], ["07:30", "08:00"]]
        //传入时间段当天的开始时间
        $time_day = strtotime($when_present);

        //求时间段
        $start_time_slot_data = $start_time_slot - $time_day;
        $end_time_slot_data = $end_time_slot - $time_day;
        $time_slot = [
            'start_time_slot' => $start_time_slot_data,
            'end_time_slot' => $end_time_slot_data,
//            'start_time_slot_see' => date('H:i', $start_time_slot),
//            'end_time_slot_see' => date('H:i', $end_time_slot),

            'start_time_slot_see' => $data['time_slot'][0],
            'end_time_slot_see' => $data['time_slot'][1],
        ];
        return $time_slot;
    }

    //返回前台数据 验证是否可以排课
    public static function planCourse($data, $Timeslot_data)
    {
        //验证是否可以排课
        foreach ($Timeslot_data as $k => $v) {
            $where = [
                'mem_id' => ['<>', ''],
                'arrange_course_id' => ['<>', ''],
//                'course_id' => $v['course_id'], //课程ID
//                'classroom_id' => $v['classroom_id'], //教室ID
//                'teacher_id' => $v['teacher_id'], //老师ID
                'day_time' => $v['day_time'], //当前时间
                'start_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]], //时间段开始时间
                'end_time_slot' => ['between', [$v['start_time_slot'], $v['end_time_slot']]], //时间段结束始时间
                'is_del' => 1,
            ];
            $whereor["teacher_id"] = $v['teacher_id'];
            $whereor["classroom_id"] = $v['classroom_id'];

            //验证此阶段时间内老师和教室是否被占用，如果占用此教室和老师为空
            $course_timetable_data = Db::name('zht_course_timetable')->where($where)->where(function ($q) use ($whereor) {
                $q->whereOr($whereor);
            })->select();


//            $course_timetable_data = Crud::getData('zht_course_timetable', 1, $where, 'id');
//            $course_timetable_data = Db::name('zht_course_timetable')->where($where)->whereOr($whereor)->find();
//            $course_timetable_data = Crud::getDataWhereOr('zht_course_timetable', 1, $where, $whereor, 'id');
            if ($course_timetable_data) {
                $Timeslot_data[$k]['classroom_id'] = '';
                $Timeslot_data[$k]['teacher_id'] = '';
            }
        }
        //求课时
        $course_num = Crud::getData('zht_course_num', 1, ['id' => $data['course_id'][3], 'is_del' => 1], 'course_section_num');
        //组合数组返回前台将空的以'-'代替
        $identifier_time = time() . rand(10, 99);
        //教室和老师不是必填（在此不是必填）
        if (!isset($data['classroom_id']) || empty($data['classroom_id'])) {
            $data['classroom_id'][0] = '';
            $data['classroom_id'][1] = '';
        }
        if (!isset($data['teacher_id']) || empty($data['teacher_id'])) {
            $data['teacher_id'][0] = '';
            $data['teacher_id'][1] = '';
        }


        foreach ($Timeslot_data as $k => $v) {
            $data_add = [
                'mem_id' => $data['mem_id'],
                'identifier_time' => $identifier_time, //标识符
                'course_id' => $v['course_id'], //课程ID
                'course_category_id' => $data['course_id'][0], //课程一级分类
                'course_category_small_id' => $data['course_id'][1], //课程二级分类
                'course_num_id' => $data['course_id'][3], //课时ID
                'course_num' => $course_num['course_section_num'], //总课时
                'classroom_id' => $v['classroom_id'], //教室ID
                'classroom_category_id' => $data['classroom_id'][0], //教室一级分类
                'classroom_category_small_id' => $data['classroom_id'][1], //教室二级分类
                'teacher_id' => $v['teacher_id'], //老师ID
                'teacher_category_id' => $data['teacher_id'][0], //老师一级分类
                'teacher_category_small_id' => $data['teacher_id'][1], //老师二级分类
                'day' => $v['day'], //展示天
                'day_time' => $v['day_time'],
                'day_time_start' => $v['day_time_start'],
                'day_time_end' => $v['day_time_end'],
                'week' => $v['week'],
                'course_hour' => $data['course_hour'],
                'start_time_slot' => $v['start_time_slot'],
                'end_time_slot' => $v['end_time_slot'],
                'time_slot' => $v['time_slot'],
            ];
            $course_timetable_inof = Crud::setAdd('zht_course_timetable', $data_add);
        }
        if ($course_timetable_inof) {
            return $identifier_time;
        } else {
            return jsonResponse('3000', '设置星期与时间不符合');
        }
//        return $Timeslot_data;
    }


}