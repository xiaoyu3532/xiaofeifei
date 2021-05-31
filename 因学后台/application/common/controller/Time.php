<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 14:12
 */

namespace app\common\controller;


class Time
{    //获取指定某一天的本周开始时间与结束时间
    public static function getWeekMyActionAndEnd($time = '', $first = 1)
    {
        //当前日期
        if (!$time) {
            $time = time();
        }
        $sdefaultDate = date("Y-m-d", $time);
        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w = date('w', strtotime($sdefaultDate));
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days'));
        //本周结束日期
        $week_end = date('Y-m-d', strtotime("$week_start +6 days"));
        $week_start = strtotime($week_start);
        $week_end = strtotime($week_end) + 86399;
        return array("week_start" => $week_start, "week_end" => $week_end);
    }

    //获取本周的每天
    public static function getWeekDay($week_start, $week_end)
    {
        for ($i = $week_start; $i < $week_end; $i += 86400) {
            $year = $i;
            $Timedata[] = $i;
//            $Timedata[] = [
//                'time' => $i,
//                'day' => date('j', $i),
//            ];
        }
        return $Timedata;
    }

    //获取时间区间的每一天
    public static function getEveryDay($week_start, $week_end)
    {
        for ($i = $week_start; $i <= $week_end; $i += 86400) {
            $year = $i;
            $Timedata[] = $i;
//            $Timedata[] = [
//                'time' => $i,
//                'day' => date('j', $i),
//            ];
        }
        return $Timedata;
    }

    //获取当天的开始时间与结束时间
    public static function getDay()
    {
        $start_time = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end_time = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $data = [
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
        return $data;
    }

    //获取本月的开始结束时间
    public static function getMonth()
    {
        $month_start = date('Y-m-d H:i:s', strtotime("first day of this month 00:00:00"));
        $month_end = date('Y-m-d H:i:s', strtotime("last day of this month 23:59:59"));
        $data = [
            'month_start' => $month_start,
            'month_end' => $month_end,
        ];
        return $data;
    }

    //获取本周的开始时间及每天的开始时间和结束时间
    public static function getTimeWeekDay($start_time = '', $end_time = '')
    {
        if ((!empty($start_time) && !empty($end_time))) {
            $start_time = $start_time / 1000;
            $end_time = $end_time / 1000;

            $days = ($end_time - $start_time) / 86400 + 1;

            // 保存每天日期
            $day_array = array();
            for ($i = 0; $i < $days; $i++) {
                $day_array[] = $start_time + (86400 * $i);
            }
        } else {
            $time = time();
            //获取本周的开始结束时间
            $week = self::getWeekMyActionAndEnd($time);
            //获取本周的每天
            $day_array = self::getWeekDay($week['week_start'], $week['week_end']);
        }
        //获取本周的开始结束时间
        $new = [];
        foreach ($day_array as $k => $v) {
            $arr[$k] = date('d', $v);
            $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
            $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
        }
        $time_data = [
            'arr' => $arr,
            'new' => $new
        ];
        return $time_data;
    }

    //获取指定某一天的前6天
    public static function getSixDay($time = '')
    {
        //当前日期
        if (!$time) {
            $time = time();
        }
        $end_day = date("Y-m-d", $time);
        $end_time = strtotime($end_day);
        $start_time = $end_time - 518400;
        $day_array = [
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];
        return $day_array;
    }

}