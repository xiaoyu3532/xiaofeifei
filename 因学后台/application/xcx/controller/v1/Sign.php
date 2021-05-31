<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 21:19
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use think\Db;
class Sign extends BaseController
{
    //获取签到列表
    //Selection 1你已签到，2未签到
    public static function getSign($user_id)
    {
        //获取今天的开始时间与结束时间
        $day_time = self::getSignNum($user_id, 1);
        //获取我本周的签到情况
        $week_time = self::getWeekMyActionAndEnd();
        for ($i = $week_time['week_start']; $i < $week_time['week_end']; $i += 86400) {
            $Timedata[] = [
                'time' => $i,
                'day' => date('j', $i),
                'Selection' => 2,
            ];
        }
        $where = [
            'is_del' => 1,
            'user_id' => $user_id,
            'time' => ['between', [$week_time['week_start'], $week_time['week_end']]]
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*');
        if ($info) {
            foreach ($Timedata as $k => $v) {
                foreach ($info as $kk => $vv) {
                    if ($v['time'] == $vv['time']) {
                        $Timedata[$k]['Selection'] = 1;
                    }
                }
            }
        }
        if ($Timedata) {
            if ($day_time['Selection'] == 1) {
                $data = [
                    'time' => $Timedata,
                    'Selection' => 1,
                    'sign_num' => $day_time['sign_num']
                ];
            } else {
                $data = [
                    'time' => $Timedata,
                    'Selection' => 2,
                    'sign_num' => 0
                ];
            }
            return jsonResponseSuccess($data);
        } else {
            throw new  NothingMissException();
        }

    }

    //添加签到
    public static function addSign($user_id)
    {
        $day_time = self::getSignNum($user_id, 1);
        if ($day_time['Selection'] == 1) {
            return jsonResponse('2000', '今天你已签到过了,明天在来', $day_time);
        } else {
            //获取我本周的签到情况
            $week_time = self::getWeekMyActionAndEnd();
            $where = [
                'is_del' => 1,
                'user_id' => $user_id,
                'time' => ['between', [$week_time['week_start'], $week_time['week_end']]]
            ];
            $table = request()->controller();
            $info = Crud::getData($table, $type = 2, $where, $field = '*');
            if ($info) {
                $info_length = count($info) - 1;
                $sign_type_num = $info[$info_length]['sign_type'];
                if ($sign_type_num == 7) {
                    $sign_type = 1;
                } else {
                    $sign_type = $info[$info_length]['sign_type'] + 1;
                }
            } else {
                $sign_type = 1;
            }
            $now_time = strtotime(date("Y-m-d", time()));

            $data = [
                'user_id' => $user_id,
                'time' => $now_time,
                'sign_type' => $sign_type,
            ];
            Db::startTrans();
            $day_time = Crud::setAdd($table, $data, $type = 1);
            if(!$day_time){
                Db::rollback();
            }
            if ($day_time) {
                //添加积分
                $where1 = [
                    'id' => $user_id
                ];
                $table1 = 'user';
                $inc_integral = Crud::setIncs($table1, $where1, 'integral', 10);
                if ($inc_integral) {
                    Db::commit();
                    return jsonResponseSuccess($day_time);
                }else{
                    Db::rollback();
                    throw new AddMissException();
                }
            } else {
                throw new AddMissException();
            }
        }
    }

    //获取你已签到多少天
    public static function getSignNum($user_id, $Sign_type = 0)
    {
        //获取今天的开始时间与结束时间
        $time_data = self::getNowTime();
        $where = [
            'is_del' => 1,
            'user_id' => $user_id,
            'time' => ['between', [$time_data["time_star"], $time_data["time_end"]]]
        ];
        $table = request()->controller();
        $day_time = Crud::getData($table, $type = 1, $where, $field = '*', 'time desc');
        //获取签到天数
        $where1 = [
            'is_del' => 1,
            'user_id' => $user_id,
        ];
        $sign_num = Crud::getCount($table, $where1);
        if (!$sign_num) {
            $sign_num = 0;
        }
        if ($day_time) {
            $data = [
                'Selection' => 1,
                'sign_num' => $sign_num,
            ];
            if ($Sign_type == 1) {
                return $data;
            } else {
                return jsonResponse('2000', '今天你已签到过了,明天在来', $data);
            }

        } else {
            $data = [
                'Selection' => 2,
                'sign_num' => $sign_num,
            ];
            if ($Sign_type == 1) {
                return $data;
            } else {
                return jsonResponse('1000', '可以签到', $data);
            }

        }
    }

    //获取指定某一天的本周开始时间与结束时间
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

    //获取今天的天始时间与结束时间
    public static function getNowTime()
    {
        $star_time = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $end_time = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1);
        $time["star"] = strtotime($star_time);
        $time["end"] = strtotime($end_time);
        $data = [
            'time_star' => $time["star"],
            'time_end' => $time["end"],
        ];
        return ($data);
    }

}