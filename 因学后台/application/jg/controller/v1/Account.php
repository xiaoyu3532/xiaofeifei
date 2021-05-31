<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 11:49
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\common\controller\Time;
use app\pc\controller\v1\LoginFinance as LoginFinance;

class Account extends BaseController
{
    //获取本机构流水
    public static function getjgAccount()
    {
        //验证密码
        $data = input();
        $LoginFinance = LoginFinance::getpcLoginFinance($data);
        if($LoginFinance !=1000){
            return $LoginFinance;
        }
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            //用户订单求金额
            $where = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]]  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];
            $table = 'order';
            $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
            //分润后的总钱
            $sum_price = ShareBenefit::getjgShareBenefit($order_data);

            $where1 = [
                'mid' => $mem_data['mem_id'],
                'status' => 6  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];
            $order_data = Crud::getData($table, 2, $where1, 'price,name,cou_status', '', 1, 10000);
            //分润后的可提的钱
            $out_price = ShareBenefit::getjgShareBenefit($order_data);

            //冻结钱
            $frozen_price = $sum_price - $out_price;

            //获取体验名额
            $where2 = [
                'mid' => $mem_data['mem_id'],
                'status' => 8,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'see_type' => 2, //1名额已购买，2未购买
                'cou_status' => 2 //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
            ];
            $table = 'order';
            $experience_num = Crud::getCounts($table, $where2);
            if (!$experience_num) {
                $experience_num = 0;
            }
            $info = [
                'sum_price' => $sum_price, //总金额
                'out_price' => $out_price, //可提金额
                'frozen_price' => $frozen_price,  //冻结金额
                'experience_num' => $experience_num, //体验课名额
            ];
            return jsonResponseSuccess($info);
        }else{
            throw new ISUserMissException();
        }
    }

    //展示机构支出条型图
    public static function getjgDataimg($start_time = '', $end_time = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
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
                $week = Time::getWeekMyActionAndEnd($time);
                //获取本周的每天
                $day_array = Time::getWeekDay($week['week_start'], $week['week_end']);
            }
            //获取本周的开始结束时间
            $new = [];
            foreach ($day_array as $k => $v) {
                $arr[$k] = date('d', $v);
                $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
            }
            foreach ($new as $k => $v) {
                //收入
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
                //分润后的总钱
                $income[$k] = ShareBenefit::getjgShareBenefit($order_data);
                //支出
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'type' => ['in', [3, 4]],  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                    'status' => 1, //1完成，2未完成，3提出失败
                ];
                $table1 = request()->controller();
                $expenditure[$k] = Crud::getSum($table1, $where, 'price');
            }
            $info = [
                'day' => $arr,
                'income' => $income,
                'expenditure' => $expenditure,
            ];
            return jsonResponseSuccess($info);
        }
    }

    //列表展示
    public static function getjgAccountlist($page = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            //获取机构名称
            $member_data = Crud::getData('member', 1, ['uid' => $mem_data['mem_id'], 'is_del' => 1, 'status' => 1], 'cname');
            $where = [
                'a.mid' => $mem_data['mem_id'],
                'a.status' => 1, //1完成，2未完成，3提出失败
                'a.is_del' => 1,
            ];
            $table = request()->controller();
            $join = [
                ['yx_user u', 'a.uid = u.id', 'left'], //用户表
            ];
            $alias = 'a';
            //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'a.create_time desc', $field = 'a.id,a.type,a.price,u.name,a.create_time', $page);
            if (!$info) {
                throw new NothingMissException();
            } else {
                foreach ($info as $k => $v) {
                    if ($v['type'] == 1 || $v['type'] == 5) {
                        $info[$k]['name'] = $member_data['cname'];
                    }
                }
                $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'a.id');
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

    //机构首页核心数据
    public static function getjgIndexStatistics()
    {

        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            //获取所有学生数量
            $user_num = Crud::getCount('user_member_belong', ['is_del' => 1, 'mem_id' => $mem_data['mem_id']]);
            //缴费人数
            $user_num_pay = Crud::getGroupCount('order', ['status' => ['in', [2, 5, 6]], 'mid' => $mem_data['mem_id']], 'uid');

            //缴费金额
            $where = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'is_del' => 1
            ];
            $order_data = Crud::getData('order', 2, $where, 'price,name,cou_status', '', 1, 10000);
            $total_price = ShareBenefit::getjgShareBenefit($order_data);
            //获取当天缴费
            $day_time = Time::getDay();
            $where1 = [
                'create_time' => ['between', [$day_time['start_time'], $day_time['end_time']]],
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'is_del' => 1

            ];
            $order_data_day = Crud::getData('order', 2, $where1, 'price,name,cou_status', '', 1, 10000);//1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
            $getday = ShareBenefit::getjgShareBenefit($order_data_day);


            //获取本月缴费情况
            $month_time = Time::getMonth();
            $where2 = [
                'create_time' => ['between', [$month_time['month_start'], $month_time['month_end']]],
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'is_del' => 1
            ];
            $order_data_month = Crud::getData('order', 2, $where2, 'price,name,cou_status', '', 1, 10000);//1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
            $getmonth = ShareBenefit::getjgShareBenefit($order_data_month);

            //获取班级
            $classroom_num = Crud::getCount('classroom', ['type' => 1, 'is_del' => 1, 'mem_id' => $mem_data['mem_id']]);


            //上课人数
            $class_num = Crud::getGroupCount('order', ['status' => 5, 'mid' => $mem_data['mem_id']], 'uid');  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款

            $data = [
                'user_num' => $user_num, //获取所有学生数量
                'user_num_pay' => $user_num_pay, //缴费人数
                'experience_num' => $user_num_pay, //体验人数
                'total_price' => $total_price,   //缴费金额
                'getday' => $getday,      //获取当天缴费
                'getmonth' => $getmonth, //获取本月缴费情况
                'classroom_num' => $classroom_num, //教室
                'class_num' => $class_num, //上课人数
            ];
            return jsonResponseSuccess($data);
        }


    }

    //机构首页图表统计
    //$img_type 1日收入 2月收入 3年收入 累计收入
    public static function getjgIndexStatisticsImg($img_type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $time = time();
            //获取本周的开始结束时间
            if ($img_type == 1) {
                $week = Time::getWeekMyActionAndEnd($time);
                //获取本周的每天
                $day_array = Time::getWeekDay($week['week_start'], $week['week_end']);
                //获取本周的开始结束时间
                $new = [];
                foreach ($day_array as $k => $v) {
                    $arr[$k] = date('d', $v);
                    $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                    $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
                }
            } elseif ($img_type == 2) {
                $year_time = time();
                $year = date('Y', $year_time);
                //本月收益
                $Y = $year; //获取年，示例，真实环境从前端获取数据
                $new = [];
                for ($i = 1; $i <= 12; $i++) {
                    $month = $Y . "-" . $i; //当前年月
                    $v = $i - 1;
                    $arr[$v] = $i; //当前年月
                    $month_start = strtotime($month); //指定月份月初时间戳
                    $new[$v]['start'] = strtotime($month); //指定月份月初时间戳
                    $month_end = strtotime("+1month", $month_start) - 1; //指定月份月末时间戳
                    $new[$v]['end'] = strtotime("+1month", $month_start) - 1; //指定月份月末时间戳
                }
            } elseif ($img_type == 3) {

                //平台开始使用的年
                $setart_year = 2019;
                //现在年份
                $year_time = time();
                $year = date('Y', $year_time);
                $new = [];
                $year_num = $year - $setart_year;
                if ($year_num > 0) {
                    $v = 0;
                    for ($i = 2019; $i <= $year; $i++) {
                        $arr[$v] = $i; //当前年月
                        $beginThisyear = strtotime($i . '-1-1');
                        $endThisyear = strtotime($i . '-12-31');
                        $new[$v]['start'] = mktime(0, 0, 0, date("m", $beginThisyear), date("d", $beginThisyear), date("Y", $beginThisyear));
                        $new[$v]['end'] = mktime(23, 59, 59, date("m", $endThisyear), date("d", $endThisyear), date("Y", $endThisyear));
                        $v++;
                    }
                } else {
                    throw  new NothingMissException();
                }
            }

            foreach ($new as $k => $v) {
                //收入
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
                //分润后的总钱
                $income[$k] = ShareBenefit::getjgShareBenefit($order_data);
                //支出
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'type' => ['in', [3, 4]],  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                    'status' => 1, //1完成，2未完成，3提出失败
                ];
                $table1 = request()->controller();
                $expenditure[$k] = Crud::getSum($table1, $where, 'price');
            }
            $info = [
                'day' => $arr,
                'income' => $income,
                'expenditure' => $expenditure,
            ];
            return jsonResponseSuccess($info);
        }
    }

    //机构首页财务明细
    public static function getjgIndexFinance()
    {

        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $time = time();
            //获取本周的开始结束时间
//            $week = Time::getWeekMyActionAndEnd($time);
            //获取本周开始时间和结束时间
            $day_array = Time::getMonth();
            $where = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$day_array['month_start'], $day_array['month_end']]],
            ];
            $table = 'order';
            $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
            //分润后的总钱 本月收入
            $month_income = ShareBenefit::getjgShareBenefit($order_data);

            //本月结算（提现）
            $month = Time::getMonth();
            $where1 = [
                'mid' => $mem_data['mem_id'],
                'type' => 10,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
                'status' => 1,//1完成，2未完成，3提出失败
                'is_del' => 1,
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $table = 'account';
            $month_Settlement = Crud::getSum($table, $where1, 'price');

            //本月退款
            $where2 = [
                'mid' => $mem_data['mem_id'],
                'type' => 4,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
                'status' => 1,//1完成，2未完成，3提出失败
                'is_del' => 1,
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $table = 'account';
            $month_refund = Crud::getSum($table, $where2, 'price');

            //本月支出（充值）
            $where3 = [
                'mid' => $mem_data['mem_id'],
                'type' => 5,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
                'status' => 1,//1完成，2未完成，3提出失败
                'is_del' => 1,
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $table = 'account';
            //本月退款
            $month_recharge = Crud::getSum($table, $where3, 'price');

            $data = [
                'month_income' => $month_income, //分润后的总钱 本月收入
                'month_Settlement' => $month_Settlement, //本月结算（提现）
                'month_refund' => $month_refund, //本月退款
                'month_recharge' => $month_recharge, //本月支出（充值）
            ];
            return jsonResponseSuccess($data);
        }
    }

    //数据分析 （数据统计）
    //$account_type1为本周 2为本月
    public static function getCumulative($account_type = 1)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {

            //收入
//            if($account_type ==1){
//
//            }elseif ($account_type ==2){
            $month = Time::getMonth();
            $month['month_start'] = strtotime($month['month_start']);
            $month['month_end'] = strtotime($month['month_end']);
            $where = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
//            }

            $table = 'order';
            $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
            //分润后的总钱
            if ($order_data) {
                $income = ShareBenefit::getjgShareBenefit($order_data);
            } else {
                $income = 0;
            }


            //缴费人数
            $where1 = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $uid_num = Crud::getGroupCount($table, $where1, 'uid');
            if ($uid_num) {
                $average_price = $income / $uid_num;
            } else {
                $average_price = 0;
            }

            //累计人数
            $user_num = Crud::getCount('user_member_belong', ['is_del' => 1, 'mem_id' => $mem_data['mem_id']]);
            //新增人数
//            $day_time = Time::getDay();
            $where2 = [
                'is_del' => 1,
                'type' => 1,
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $add_num = Crud::getCount($table, 2, $where2);
            //退款
            $where3 = [
                'mid' => $mem_data['mem_id'],
                'type' => 4,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
                'status' => 1, //1完成，2未完成，3提出失败
            ];
            $table1 = request()->controller();
            $refund = Crud::getSum($table1, $where3, 'price');

            //体验人数
            $where4 = [
                'mid' => $mem_data['mem_id'],
                'status' => 8,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $experience_num = Crud::getCount($table, $where4);
            //支付人数
            $where5 = [
                'mid' => $mem_data['mem_id'],
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$month['month_start'], $month['month_end']]],
            ];
            $pay_num = Crud::getGroupCount($table, $where5, 'mid');

            $info = [
                'income' => $income,//累计金额
                'uid_num' => $uid_num,//缴费人数
                'average_price' => $average_price,//人均缴费
                'user_num' => $user_num,//累计人数
                'add_num' => $add_num,//新增人数
                'refund' => $refund,//退款
                'experience_num' => $experience_num,//体验人数
                'pay_num' => $pay_num,//支付人数
            ];


            return jsonResponseSuccess($info);
        }
    }

    //数据分析 （财务数据）
    public static function getjgFinance()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $time = time();
            //获取本周的开始结束时间
            $week = Time::getWeekMyActionAndEnd($time);
            //获取本周的每天
            $day_array = Time::getWeekDay($week['week_start'], $week['week_end']);
            //获取本周的开始结束时间
            $new = [];
            foreach ($day_array as $k => $v) {
                $arr[$k] = date('d', $v);
                $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
            }
            foreach ($new as $k => $v) {
                $where1 = [
                    'mid' => $mem_data['mem_id'],
                    'type' => 10,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
                    'status' => 1,//1完成，2未完成，3提出失败
                    'is_del' => 1,
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'account';
                $withdrawal[$k] = Crud::getSum($table, $where1, 'price');


                //支出
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'type' => 8,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                    'status' => 1, //1完成，2未完成，3提出失败
                ];
                $table1 = request()->controller();
                $refund[$k] = Crud::getSum($table1, $where, 'price');
            }
            $info = [
                'day' => $arr,
                'withdrawal' => $withdrawal,
                'refund' => $refund,
            ];
            return jsonResponseSuccess($info);

        }

    }

    //数据分析 （订单数据）
    public static function getjgAnalysisOrder()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $year_time = time();
            $year = date('Y', $year_time);
            //本月收益
            $Y = $year; //获取年，示例，真实环境从前端获取数据
            $new = [];
            for ($i = 1; $i <= 12; $i++) {
                $month = $Y . "-" . $i; //当前年月
                $v = $i - 1;
                $arr[$v] = $i; //当前年月
                $month_start = strtotime($month); //指定月份月初时间戳
                $new[$v]['start'] = strtotime($month); //指定月份月初时间戳
                $month_end = strtotime("+1month", $month_start) - 1; //指定月份月末时间戳
                $new[$v]['end'] = strtotime("+1month", $month_start) - 1; //指定月份月末时间戳
            }
            foreach ($new as $k => $v) {
                //收入
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $num[$k] = Crud::getGroupCount($table, $where, 'uid');
            }
            $info = [
                'day' => $arr,
                'num' => $num,
            ];
            return jsonResponseSuccess($info);
        }
    }

    //订单分析 （订单笔数）
    public static function getjgOrdernum()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $time = time();
            //获取本周的开始结束时间
            $week = Time::getWeekMyActionAndEnd($time);
            //获取本周的每天
            $day_array = Time::getWeekDay($week['week_start'], $week['week_end']);
            $new = [];
            foreach ($day_array as $k => $v) {
                $arr[$k] = date('d', $v);
                $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
            }
            foreach ($new as $k => $v) {
                //收入
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $order_num[$k] = Crud::getCount($table, $where);
            }
            $info = [
                'day' => $arr,
                'order_num' => $order_num,
            ];
            return jsonResponseSuccess($info);
        }
    }

    //订单分析 (课程数据)
    public static function getjgAnalysisCategory()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.mid' => $mem_data['mem_id'],
            ];

            $join = [
                ['yx_category ca', 'c.cid =ca.id ', 'left'],  //学生信息
            ];
            $alias = 'c';
            $table = 'curriculum';
            $category_num = Crud::getRelationData($table, 2, $where, $join, $alias, $order = '', $field = 'ca.name,c.cid', 1, 100000, 'c.cid');

            $list_data = [];
            foreach ($category_num as $k => $v) {
                $where1 = [
                    'is_del' => 1,
                    'type' => 1,
                    'mid' => $mem_data['mem_id'],
                    'cid' => $v['cid']
                ];
                $count = Crud::getCount($table, $where1);
                if ($count) {
                    $category_num[$k]['count'] = $count;
                } else {
                    $category_num[$k]['count'] = 0;
                }
                $list_data[] = [
                    'value' => $count,
                    'name' => $v['name'],
                ];
            }
            return jsonResponseSuccess($list_data);
        }

    }

    //订单分析 (订单分析)
    public static function getjgOrderAnalysis()
    {
        //获取本周开始时间及每天的时间
        $time_data = Time::getTimeWeekDay();
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            foreach ($time_data['new'] as $k => $v) {
                //未支付
                $where = [
                    'mid' => $mem_data['mem_id'],
                    'status' => 1,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $notPay[$k] = Crud::getCount($table, $where);

                //支付
                $where1 = [
                    'mid' => $mem_data['mem_id'],
                    'status' => 2,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $alreadyPay[$k] = Crud::getCount($table, $where1);

                //退款
                $where2 = [
                    'mid' => $mem_data['mem_id'],
                    'status' => 4,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $refund[$k] = Crud::getCount($table, $where2);
            }
            $info = [
                'day' => $time_data['arr'],
                'notPay' => $notPay,  //未支付
                'alreadyPay' => $alreadyPay, //支付
                'refund' => $refund,  //退款
            ];
            return jsonResponseSuccess($info);
        }
    }

    //订单分析 (订单分析)
    public static function getjsOrderNumAnalysis()
    {
        //获取本周开始时间及每天的时间
        $time_data = Time::getTimeWeekDay();
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            foreach ($time_data['new'] as $k => $v) {
                //支付
                $where1 = [
                    'mid' => $mem_data['mem_id'],
                    'status' => 2,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                    'create_time' => ['between', [$v['start'], $v['end']]],
                ];
                $table = 'order';
                $alreadyPay[$k] = Crud::getCount($table, $where1);
            }
            $info = [
                'day' => $time_data['arr'],
                'alreadyPay' => $alreadyPay, //支付
            ];
            return jsonResponseSuccess($info);
        }
    }

    //订单分析（课程订单，订单分类分析）
    public static function getjsOrderCategory()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'o.is_del' => 1,
                'cu.is_del' => 1,
//                'c.is_del' => 1,
                'ca.is_del' => 1,
                'o.mid' => $mem_data['mem_id'],
                'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];

            $join = [
                ['yx_course c', 'o.cid =c.id ', 'left'],  //课程
                ['yx_curriculum cu', 'c.curriculum_id =cu.id ', 'left'],  //学生信息
                ['yx_category ca', 'cu.cid =ca.id ', 'left'],  //学生信息
            ];
            $alias = 'o';
            $table = 'order';
            $category_num = Crud::getRelationData($table, 2, $where, $join, $alias, $order = '', $field = 'ca.name,ca.id', 1, 100000, 'ca.id');
            $list_data = [];
            foreach ($category_num as $k => $v) {
                $where1 = [
                    'o.is_del' => 1,
                    'cu.is_del' => 1,
                    'ca.is_del' => 1,
                    'o.status' => ['in', [2, 5, 6, 8]],
                    'ca.id' => $v['id']
                ];
                $count = Crud::getCountSel($table, $where1, $join, $alias, $field = '*', $group = '');
                if ($count) {
                    $category_num[$k]['count'] = $count;
                } else {
                    $category_num[$k]['count'] = 0;
                }
                $list_data[] = [
                    'value' => $count,
                    'name' => $v['name'],
                ];
            }
            return jsonResponseSuccess($list_data);
        }
    }

    //订单分析（年龄分析）
    public static function getjsOrderAge()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'o.is_del' => 1,
                's.is_del' => 1,
                'o.mid' => $mem_data['mem_id'],
                'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];

            $join = [
                ['yx_student s', 'o.student_id =s.id ', 'left'],  //课程
            ];
            $alias = 'o';
            $table = 'order';
            $category_num = Crud::getRelationData($table, 2, $where, $join, $alias, $order = '', $field = 's.age', 1, 100000, 's.age');

            //求报名总数
            $where1 = [
                's.is_del' => 1,
                'o.is_del' => 1,
                'o.status' => ['in', [2, 5, 6, 8]],
            ];
            $total_count = Crud::getCountSel($table, $where1, $join, $alias, $field = '*', $group = '');
            if (!$total_count) {
                $total_count = 0;
            }
            if (!$category_num) {
                throw new NothingMissException();
            }
//            $list_data = [];
            $less_three = 0; //小于等于3岁的用户
            $less_eight = 0; //小于等于8岁的用户
            $less_twelve = 0; //小于等于12岁的用户
            $less_Seventeen = 0; //小于等于17岁的用户
            $greater_eighteen = 0; //大于等于18岁的用户
            foreach ($category_num as $k => $v) {
                $where2 = [
                    's.is_del' => 1,
                    'o.is_del' => 1,
                    'o.status' => ['in', [2, 5, 6, 8]],
                    's.age' => $v['age']
                ];
                $count = Crud::getCountSel($table, $where2, $join, $alias, $field = '*', $group = '');
                if (!$total_count) {
                    $total_count = 0;
                }

//                dump($v['age']);
                if (0 < $v['age'] && $v['age'] <= 3) {
                    $less_three += $count;
                    $less_three_Proportion = round($less_three / $total_count, 2);
                    if(!$less_three_Proportion){
                        $less_three_Proportion = 0;
                    }
//                    $name = '0~3';
                }else{
                    $less_three_Proportion = 0;
//                    $name = '0~3';
                }
                if (3 < $v['age'] && $v['age'] <= 8) {
                    $less_eight += $count;
                    $less_eight_Proportion = round($less_eight / $total_count, 2);
                    if(!$less_eight_Proportion){
                        $less_eight_Proportion =0;
                    }
//                    $name = '3~8';
                }
                else{
                    $less_eight_Proportion =0;
                }
                if (8 < $v['age'] && $v['age'] <= 12) {
                    $less_twelve += $count;
                    $less_twelve_Proportion = round($less_twelve / $total_count, 2);
                    if(!$less_twelve_Proportion){
                        $less_twelve_Proportion = 0;
                    }
//                    $name = '8~12';

                }else{
                    $less_twelve_Proportion = 0;
                }
                if (12 < $v['age'] && $v['age'] <= 17) {
                    $less_Seventeen += $count;
                    $less_Seventeen_Proportion = round($less_Seventeen / $total_count, 2);
                    if(!$less_Seventeen_Proportion){
                        $less_Seventeen_Proportion =0;
                    }
//                    $name = '12~17';
                }else{
                    $less_Seventeen_Proportion =0;
                }
                if (18 < $v['age']) {
                    $greater_eighteen += $count;
                    $greater_eighteen_Proportion = round($greater_eighteen / $total_count, 2);
                    if(!$greater_eighteen_Proportion){
                        $greater_eighteen_Proportion = 0;
                    }
//                    $name = '18以上';
                }else{
                    $greater_eighteen_Proportion = 0;
                }
            }
            $list_data = [
                [
                    'value' => $less_three_Proportion,
                    'name' => '0~3',
                ],
                [
                    'value' => $less_eight_Proportion,
                    'name' => '4~8',
                ],
                [
                    'value' => $less_twelve_Proportion,
                    'name' => '9~12',
                ],
                [
                    'value' => $less_Seventeen_Proportion,
                    'name' => '13~17',
                ],
                [
                    'value' => $greater_eighteen_Proportion,
                    'name' => '18以上',
                ]
            ];


            foreach ($list_data as $key => $row) {
                $volume[$key]  = $row['value'];
            }
            array_multisort($volume, SORT_DESC, $list_data);
            return jsonResponseSuccess($list_data);
        }else{
            throw new ISUserMissException();
        }
    }


}