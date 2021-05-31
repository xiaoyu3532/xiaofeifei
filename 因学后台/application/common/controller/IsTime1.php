<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/14 0014
 * Time: 11:37
 */

namespace app\common\controller;

use app\common\model\Crud;

class IsTime
{
    //返回前端可选时间段
    //$type 1为批量修改返回，2为单个时间段修改
    //$someday 修改单个时间段的某一天
    public static function isTimeReturn($data)
    {
        $section_time_array = self::getDay($data['start_time'], $data['end_time']);
        //获取时间区间数组
        $time_paragraph_array = self::getTtimeParagraph($data);
        (isset($course_id) && !empty($course_id)) && $where['course_id'] = $course_id; //课程ID
        (isset($data['classroom_id']) && !empty($data['classroom_id'])) && $where['classroom_id'] = $data['classroom_id']; //教室ID
        (isset($data['teacher_id']) && !empty($data['teacher_id'])) && $where['teacher_id'] = $data['teacher_id']; //老师ID
        if(isset($data['classroom_type']) && !empty($data['classroom_type'])){
            if($data['classroom_type'] ==2){
                $where = [
                    'is_del' => 1,
                    'classroom_type' => 2,
                ];
            }elseif ($data['classroom_type'] ==3){
                $where = [
                    'is_del' => 1,
                    'classroom_type' => 3,
                ];
            }

        }else{
            $where = [
                'is_del' => 1,
                'classroom_type' => 1,
            ];
        }


        $table = 'course_timetable';
        $selection_array = Crud::getData($table, $type = 2, $where, $field = 'day,time_slot,type', $order = '', $page = '1', $pageSize = '100000');
        if(!$selection_array){

        }
//        dump($selection_array);

        //获取不能添加的天数与时间段
        $selection_time_slot = [];
        foreach ($section_time_array as $sk => $sv) {
            foreach ($selection_array as $sek => $sev) {
                if ($sv == $sev['day']) {
                    $selection_time_slot [] = $sev['time_slot'];
                }
            }
        }

        //返回前端不能选择的时间段
        foreach ($time_paragraph_array as $tpk => $tpv) {
            foreach ($selection_time_slot as $stk => $stv) {
                if ($tpv['time_slot'] == $stv) {
                    $time_paragraph_array[$tpk]['selection'] = 1;
                }
            }
        }
        //如果修改本课程时，选中字段可以修改
        if (!empty($data['course_id'])) {
            $time_paragraph_array = self::getTitmselection($data, $time_paragraph_array);
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
        $selection_array = self::getselection($section_time_array[0], $data['course_id']); //开始第一天,课程ID
        $return_data = [
            'format_array' => $last_array,
            'selection_array' => $selection_array,
        ];
        return $return_data;
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
            }else{
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
        if(isset($data['classroom_type']) && !empty($data['classroom_type'])){
            if($data['classroom_type'] ==2){
                $where = [
                    'is_del' => 1,
                    'day' => $data['someday'],
                    'classroom_type' => 2,
                ];
            }elseif ($data['classroom_type'] ==3){
                $where = [
                    'is_del' => 1,
                    'day' => $data['someday'],
                    'classroom_type' => 3,
                ];
            }

        }else{
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
            $time_paragraph_array = self::getTitmselection($data, $time_paragraph_array,$type = 2, $data['someday']);
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


}