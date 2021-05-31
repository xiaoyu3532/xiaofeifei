<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 16:20
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use app\jg\controller\v1\ShareBenefit;
use app\common\controller\Time;
use app\lib\exception\UpdateMissException;
use think\Db;
use app\pc\controller\v1\LoginFinance as LoginFinance;

class Account extends BaseController
{
    //获取流水
    public static function getpcAccount()
    {
        $data = input();
        $LoginFinance = LoginFinance::getpcLoginFinance($data);
        if($LoginFinance !=1000){
            return $LoginFinance;
        }

        //用户订单求金额
        $where = [
            'type' => ['in', [1, 2]],  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            'status' => 1, //1完成，2未完成，3提出失败
            'is_del' => 1,
        ];
        $table = request()->controller();
        $order_data = Crud::getData($table, 2, $where, 'price,cou_status', 'create_time desc', 1, 10000);
        //分润后的总钱
        $mem_income = ShareBenefit::getShareBenefit($order_data);

        $where = [
            'type' => 5,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            'status' => 1, //1完成，2未完成，3提出失败
            'is_del' => 1,
            'types' => 2,//1普通用户，2机构充值 （邀请也用此字段区分）
        ];
        $member_recharge = Crud::getSum($table, $where, 'price');
        if (!$member_recharge) {
            $member_recharge = 0;
        }

        //机构总收入
        $member_income = $mem_income + $member_recharge;

        //用户总收入（充会员）
        $where = [
            'type' => 5,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            'status' => 1, //1完成，2未完成，3提出失败
            'is_del' => 1,
            'types' => 1,//1普通用户，2机构充值 （邀请也用此字段区分）
        ];
        $user_income = Crud::getSum($table, $where, 'price');
        if (!$user_income) {
            $user_income = 0;
        }

        //总和
        $where = [
            'type' => ['in', [1, 2, 5]],  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            'status' => 1, //1完成，2未完成，3提出失败
            'is_del' => 1,
        ];
        $sum_income = Crud::getSum($table, $where, 'price');
        if (!$sum_income) {
            $sum_income = 0;
        }

        //总支出
        $where = [
            'type' => ['in', [3, 4]],  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
            'status' => 1, //1完成，2未完成，3提出失败
            'is_del' => 1,
        ];
        $sum_expenditure = Crud::getSum($table, $where, 'price');
        if (!$sum_expenditure) {
            $sum_expenditure = 0;
        }

        $info = [
            'member_income' => $member_income,  //机构总收入
            'user_income' => $user_income,  //用户总收入
            'sum_income' => $sum_income,  //总收入
            'sum_expenditure' => $sum_expenditure,  //支出收入
        ];

        return jsonResponseSuccess($info);
    }

    //展示机构支出条型图
    public static function getpcDataimg($start_time = '', $end_time = '')
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
                'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$v['start'], $v['end']]],
            ];
            $table = 'order';
            $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
            //分润后的总钱
            $income[$k] = ShareBenefit::getjgShareBenefit($order_data);
            //支出
            $where = [
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

    //列表展示
    public static function getpcAccountlist($page = 1)
    {
        //获取机构名称
        $where = [
            'a.status' => 1, //1完成，2未完成，3提出失败
            'a.is_del' => 1,
        ];
        $table = request()->controller();
        $join = [
            ['yx_user u', 'a.uid = u.id', 'left'], //用户表
            ['yx_member m', 'a.mid = m.uid', 'left'], //机构表
        ];
        $alias = 'a';
        //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'a.create_time desc', $field = 'a.id,a.type,a.price,u.name,a.create_time,m.cname', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                if ($v['type'] == 1 || $v['type'] == 5) {
                    $info[$k]['name'] = $v['cname'];
                }
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'a.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }

    }

    //提现列表
    public static function getpcMemberWithdrawalList($page = 1, $type = 9, $time = '', $cname = '')
    {
        $where = [
            'a.type' => $type,  //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
            'a.is_del' => 1,
            'a.types' => 2, //1用户，2机构
        ];
        (isset($cname) && !empty($cname)) && $where['m.name'] = ['like', '%' . $cname . '%'];
        if (isset($time['time']) && !empty($time['time'])) {
            $start_time = $time[0] / 1000;
            $end_time = $time[1] / 1000;
            $where = [
                'a.create_time' => ['between', [$start_time, $end_time]]
            ];
        }
        $table = request()->controller();
        $alias = 'a';
        $join = [
            ['yx_member m', 'a.mid = m.uid', 'left'], //机构id
            ['yx_user u', 'a.uid = u.id', 'left'], //用户表
        ];
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'a.create_time desc', $field = 'a.id,m.cname,u.name uname,u.phone,a.create_time,a.type,a.price,m.balance,m.frozen_balance', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                $surplus_price = $v['balance'] - $v['price'];
                $info[$k]['surplus_price'] = $surplus_price;
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'a.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //修改提现状态
    public static function setWithdrawalType($order_id, $type)
    {
        $where = [
            'id' => $order_id,
        ];
        $table = request()->controller();
        $account_update = Crud::setUpdate($table, $where, ['type' => $type]);
        if (!$account_update) {
            throw new UpdateMissException();
        } else {
            if ($type == 10) {//1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
                $where1 = [
                    'a.is_del' => 1,
                    'a.order_id' => $order_id
                ];
                $join = [
                    ['yx_member m', 'a.mid = m.uid', 'left'],
                ];
                $field = ['a.price,m.balance,m.frozen_balance,m.uid'];
                $alias = 'o';
                $account_data = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
                if ($account_data) {
                    $price = $account_data['balance'] - $account_data['frozen_balance'];
                    if ($price <= 0) {
                        throw new UpdateMissException();
                    }
                    //减余额
                    $last_price = $price - $account_data['price'];
                    if ($last_price > 0) {
                        $del_balance = Crud::setDecs('member', ['uid' => $account_data['uid']], 'balance', $last_price);
                        if ($del_balance) {
                            return jsonResponseSuccess($del_balance);
                        } else {
                            throw new UpdateMissException();
                        }
                    } else {
                        throw new UpdateMissException();
                    }
                } else {
                    throw new UpdateMissException();
                }
            } else {
                return jsonResponseSuccess($account_update);
            }

        }
    }

    //获取机构充值列表
    public static function getpcRechargeList($page = 1, $cname = '')
    {
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];
        $where = [
            'a.type' => ['in', ['1,5']],//1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝
            'a.status' => 1,//1完成，2未完成，3提出失败
            'a.types' => 2,//1普通用户，2机构充值 （邀请也用此字段区分）
        ];
        $table = request()->controller();
        $alias = 'a';
        $join = [
            ['yx_member m', 'a.mid = m.uid', 'left'], //机构id
        ];
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'a.create_time desc', $field = 'm.cname,m.ismember,m.balance,a.type,a.mid,m.give_type', $page);
        if ($info) {
            foreach ($info as $k => $v) {
                //判断是充值会员，会员不显示名额
                if ($v['type'] == 5) {
                    $info[$k]['re_num'] = '';
                    $info[$k]['give_num'] = '';
                    $info[$k]['surplus'] = '';
                } else {
                    //获取总剩余名额（加赠送名额）
                    $member_data = Db::name('member')->where(['uid' => $v['mid'], 'is_del' => 1, 'type' => 3, 'status' => 1])->field('uid,balance,ismember,give_type')->find();
                    $res = self::getNums($member_data, 1); //获取剩余名称
                    $info[$k]['re_num'] = $res['re_num'];
                    //获取赠送名额
                    if ($v['give_type'] == 1) {
                        $give_num = Db::name('give_num')->where(['mid' => $v['mid'], 'is_del' => 1])->field('num')->find();
                        if (!$give_num) {
                            $give_num['num'] = 0;
                        }
                    } else {
                        $give_num['num'] = 0;
                    }
                    $info[$k]['give_num'] = $give_num['num'];
                    $info[$k]['surplus'] = $res['re_num'] - $give_num['num'];
                }

            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'a.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);

        }

    }

    public static function getNums($res, $fs_type)
    { //$fs_type 1为计算一条，2为计算多条
        //计算名额单价
        $user = Db::name('user_price')->where(['is_del' => 1])->field('price')->find();
        //计算优惠
        $discount = Db::name('discount')->where(['is_del' => 1])->field('discount')->find();
        //计算名称
        if ($fs_type == 1) {
            if ($res['give_type'] == 1) { //give_type 1有赠送名称，2无赠送名称
                //查询赠送名额数量
                $num = Db::name('give_num')->where(['mid' => $res['uid'], 'is_del' => 1])->field('num')->find();
                if (!$num) {
                    $num = 0;
                }
                if ($res['ismember'] == 1) {//1是会员，2非会员
                    $res['re_num'] = intval($res['balance'] / ($user['price'] * $discount['discount']) + $num['num']);
                } elseif ($res['ismember'] == 2) {
                    $res['re_num'] = intval($res['balance'] / $user['price'] + $num['num']);
                }
            } elseif ($res['give_type'] == 2) {
                if ($res['ismember'] == 1) {
                    $res['re_num'] = intval($res['balance'] / ($user['price'] * $discount['discount']));
                } elseif ($res['ismember'] == 2) {
                    $res['re_num'] = intval($res['balance'] / $user['price']);
                }
            }
            return $res;
        } elseif ($fs_type == 2) {
            foreach ($res as $k => $v) {
                if ($v['give_type'] == 1) { //give_type 1有赠送名称，2无赠送名称
                    //查询赠送名额数量
                    $num = Db::name('give_num')->where(['mid' => $v['uid'], 'is_del' => 1])->field('num')->find();
                    if (!$num) {
                        $num = 0;
                    }
                    //算佣金
                    if ($v['ismember'] == 1) {  //1是会员，2非会员
                        $res[$k]['re_num'] = intval($v['balance'] / ($user['price'] * $discount['discount']) + $num['num']);
                    } elseif ($v['ismember'] == 2) {
                        $res[$k]['re_num'] = intval($v['balance'] / $user['price'] + $num['num']);
                    }
                } elseif ($v['give_type'] == 2) {
                    //算佣金
                    if ($v['ismember'] == 1) {  //1是会员，2非会员
                        $res[$k]['re_num'] = intval($v['balance'] / ($user['price'] * $discount['discount']));
                    } elseif ($v['ismember'] == 2) {
                        $res[$k]['re_num'] = intval($v['balance'] / $user['price']);
                    }
                }
            }
            return $res;
        }

    }


    //平台首页核心数据
    public static function getpcIndexStatistics()
    {

        //获取所有学生数量
        $user_num = Crud::getCount('user', ['type' => 1, 'is_del' => 1]);

        //缴费人员
        $user_num_pay = Crud::getGroupCount('order', ['status' => ['in', [2, 5, 6]]], 'uid');

        //缴费金额
        $total_price = Crud::getSum('account', ['type' => ['in', [1, 2, 5]], 'status' => 1, 'is_del' => 1], 'price'); //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款，9申请提现，10提现成功，11提现拒绝

        //获取当天缴费
        $day_time = Time::getDay();
        $where = [
            'create_time' => ['between', [$day_time['start_time'], $day_time['end_time']]],
            'type' => ['in', [1, 2, 5]],
            'status' => 1,
            'is_del' => 1
        ];
        $getday = Crud::getSum('account', $where, 'price');

        //获取本月缴费情况
        $month_time = Time::getMonth();
        $where = [
            'create_time' => ['between', [$month_time['month_start'], $month_time['month_end']]],
            'type' => ['in', [1, 2, 5]],
            'status' => 1,
            'is_del' => 1
        ];
        $getmonth = Crud::getSum('account', $where, 'price');

        //获取班级
        $ordinary_classroom = Crud::getCount('classroom', ['type' => 1, 'is_del' => 1]);
        //社区教室
        $community_classroom = Crud::getCount('community_classroom', ['type' => 1, 'is_del' => 1]);
        //教室总和
        $classroom_num = $ordinary_classroom + $community_classroom;

        //上课人数
        $class_num = Crud::getGroupCount('order', ['status' => 5], 'uid');  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款

        $data = [
            'user_num' => $user_num, //获取所有学生数量
            'user_num_pay' => $user_num_pay, //缴费人员
            'total_price' => $total_price,   //缴费金额
            'getday' => $getday,      //获取当天缴费
            'getmonth' => $getmonth, //获取本月缴费情况
            'classroom_num' => $classroom_num, //教室
            'class_num' => $class_num, //上课人数
        ];
        return jsonResponseSuccess($data);
    }

    //平台用户分析条图
    public static function getpcUserNumImg()
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
                'is_del' => 1,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'type' => 1,  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'create_time' => ['between', [$v['start'], $v['end']]],
            ];
            $table = 'user';
            $order_num[$k] = Crud::getCount($table, $where);

        }
        $info = [
            'day' => $arr,
            'order_num' => $order_num,
        ];
        return jsonResponseSuccess($info);
    }

    //平台用户
    public static function getpcUserNum()
    {
        //学生数量
        $user_num = Crud::getCount('user', ['is_del' => 1, 'type' => 1]);
        $where = [
            'status' => ['in', [2, 5, 6]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
        ];
        $table = 'order';
        $order_data = Crud::getData($table, 2, $where, 'price,name,cou_status', '', 1, 10000);
        //分润后的总钱
        $price_data = ShareBenefit::getjgShareBenefit($order_data);

        $info = [
            'user_num' => $user_num,
            'price_data' => $price_data,
        ];
        return jsonResponseSuccess($info);
    }

    //平台收入

}