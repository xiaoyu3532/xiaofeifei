<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/26 0026
 * Time: 19:55
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\Time;

class ZhtDistribution extends BaseController
{
    //获取机构普通分销员
    public static function getZhtDistributionList($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'zdr.mem_id' => $data['mem_id'],
            'zdr.is_del' => 1,
            'zdr.exclusive_type' => $data['exclusive_type'] //1分销员，2专属分销员
        ];
        $order = 'zdr.id desc';
        isset($data['user_name']) && !empty($data['user_name']) && $where['u.name'] = ['like', '%' . $data['user_name'] . '%'];
        isset($data['share_num']) && !empty($data['share_num']) && $order = 'zdr.share_num desc';
        isset($data['visit_num']) && !empty($data['visit_num']) && $order = 'zdr.visit_num desc';
        isset($data['deal_sum_user_num']) && !empty($data['deal_sum_user_num']) && $order = 'zdr.deal_sum_user_num desc';
        isset($data['deal_sum_price']) && !empty($data['deal_sum_price']) && $order = 'zdr.deal_sum_price desc';
        isset($data['sum_commission']) && !empty($data['sum_commission']) && $order = 'zdr.sum_commission desc';
        isset($data['basics_sum_commission']) && !empty($data['basics_sum_commission']) && $order = 'zdr.basics_sum_commission desc';
        isset($data['exclusive_sum_commission']) && !empty($data['exclusive_sum_commission']) && $order = 'zdr.exclusive_sum_commission desc';
        $join = [
            ['yx_user u', 'zdr.user_id = u.id', 'left'], //用户表
        ];
        $alias = 'zdr';
        $info = Crud::getRelationData('zht_distribution_relation', $type = 2, $where, $join, $alias, $order, $field = 'zdr.*,u.name', $page, $pageSize);
        if ($info) {
//            foreach ($info as $k => $v) {
//                $info[$k]['sum_commission'] = $v['basics_sum_commission'] + $v['exclusive_sum_commission'];
//            }
            $num = Crud::getCountSelNun('zht_distribution_relation', $where, $join, $alias, $field = 'zdr.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new  NothingMissException();
        }
    }

    //获取机构普通分销员字段
    public static function getZhtDistributionListField($exclusive_type = 1)
    {
        $data = [
            ['prop' => 'name', 'name' => '用户名', 'width' => '', 'state' => ''],
            ['prop' => 'share_num', 'name' => '转发分享量', 'width' => '', 'state' => ''],
            ['prop' => 'visit_num', 'name' => '链接访问量', 'width' => '', 'state' => ''],
            ['prop' => 'deal_sum_user_num', 'name' => '成交用户量', 'width' => '', 'state' => '1'],
            ['prop' => 'deal_sum_price', 'name' => '成交金额', 'width' => '', 'state' => ''],
            ['prop' => 'sum_commission', 'name' => '累计佣金', 'width' => '', 'state' => ''],
        ];
        if ($exclusive_type == 2) {
            $data1 = [
                ['prop' => 'basics_sum_commission', 'name' => '基础佣金', 'width' => '', 'state' => ''],
                ['prop' => 'exclusive_sum_commission', 'name' => '专属佣金', 'width' => '', 'state' => ''],
            ];
            $data = array_merge($data, $data1);
        }
        return jsonResponseSuccess($data);
    }

    //获取个人用户分销的信息
    public static function getgetZhtDistribution($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'zd.share_id' => $data['user_id'],
            'zd.mem_id' => $data['mem_id'],
            'zd.is_del' => 1,
            'zd.distribution_type' => 1, //1,分享用户获取佣金，2完成当月目标获取佣金
        ];
        if ((isset($data['time_data']) && !empty($data['time_data']))) {
            $start_time = $data['time_data'][0] / 1000;
            $end_time = $data['time_data'][1] / 1000;
            $where['zd.create_time'] = ['between', [$start_time, $end_time]];
        }
        $join = [
            ['yx_user u', 'zd.shared_id = u.id', 'left'], //用户表
            ['yx_zht_activity za', 'zd.activity_id = za.id', 'left'], //活动表
            ['yx_zht_course zc', 'za.course_id = zc.id', 'left'], //课程表

        ];
        $alias = 'zd';
        $info = Crud::getRelationData('zht_distribution', $type = 2, $where, $join, $alias, $order = 'zd.id desc', $field = 'zd.*,u.name,za.activity_img,za.activity_title,zc.course_name', $page, $pageSize);
        if ($info) {
            $where_distribution = [
                'share_id' => $data['user_id'],
                'mem_id' => $data['mem_id'],
                'is_del' => 1,
                'distribution_type' => 2, //1,分享用户获取佣金，2完成当月目标获取佣金
            ];
            if ((isset($data['time_data']) && !empty($data['time_data']))) {
                $start_time = $data['time_data'][0] / 1000;
                $end_time = $data['time_data'][1] / 1000;
                $where_distribution['create_time'] = ['between', [$start_time, $end_time]];
            }
            //目录完成佣金
            $month_commission = Crud::getSum('zht_distribution', $where_distribution, 'month_commission');
            $sum_commission = 0;
            foreach ($info as $k => $v) {
                $sum_commission += ($v['basics_commission'] + $v['exclusive_commission']);
                $info[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $sum_commission = $sum_commission + $month_commission;
            $num = Crud::getCountSelNun('zht_distribution', $where, $join, $alias, $field = 'zd.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
                'sum_commission' => $sum_commission,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }

    //获取个人用户分销的信息字段
    public static function getgetZhtDistributionField($exclusive_type = 1)
    {
        $data = [
            ['prop' => 'create_time_Exhibition', 'name' => '展示时间', 'width' => '', 'state' => ''],
            ['prop' => 'activity_img', 'name' => '活动信息', 'width' => '380', 'state' => ''],
            ['prop' => 'course_name', 'name' => '课程名称', 'width' => '', 'state' => ''],
            ['prop' => 'name', 'name' => '成交用户', 'width' => '', 'state' => '1'],
            ['prop' => 'deal_price', 'name' => '成交金额', 'width' => '', 'state' => ''],
            ['prop' => 'basics_commission', 'name' => '基础佣金', 'width' => '', 'state' => ''],
        ];
        if ($exclusive_type == 2) {
            $data1 = [
                ['prop' => 'exclusive_commission', 'name' => '专属每单佣金', 'width' => '', 'state' => ''],
                ['prop' => 'month_commission', 'name' => '专属月绩佣金', 'width' => '', 'state' => ''],
            ];
            $data = array_merge($data, $data1);
        }
        return jsonResponseSuccess($data);
    }

    //设置为专属分销员  exclusive_single_commission 专属单笔佣金 target_num 每月目标数 month_commission 每月完成目标月佣金 exclusive_type 1分销员，2专属分销员
    public static function setDistribution()
    {
        //获取本分销员信息
        $data = input();
        $distribution_data = Crud::getData('zht_distribution_relation', 1, ['id' => ['in', $data['distribution_id']], 'is_del' => 1], '*');
        if ($distribution_data) {
            $distribution_relation = Crud::setUpdate('zht_distribution_relation', ['id' => ['in', $data['distribution_id']], 'is_del' => 1], $data);
            if ($distribution_relation) {
                return jsonResponseSuccess($distribution_relation);
            } else {
                throw new  UpdateMissException();
            }
        } else {
            throw new  UpdateMissException();
        }
    }

    //撤销专属员工
    public static function setRevokeDistribution()
    {
        //获取本分销员信息
        $data = input();
        $distribution_data = Crud::getData('zht_distribution_relation', 1, ['id' => ['in', $data['distribution_id']], 'is_del' => 1], '*');
        if ($distribution_data) {
            $distribution_relation = Crud::setUpdate('zht_distribution_relation', ['id' => ['in', $data['distribution_id']], 'is_del' => 1], ['exclusive_type' => 1]);
            if ($distribution_relation) {
                return jsonResponseSuccess($distribution_relation);
            } else {
                throw new  UpdateMissException();
            }
        } else {
            throw new  UpdateMissException();
        }
    }

    //获取排序接口
    public static function getorderFieldList($exclusive_type = 1)
    {
        $data = [
            ['value' => 'share_num', 'label' => '转发分享量'],
            ['value' => 'visit_num', 'label' => '链接访问量'],
            ['value' => 'deal_sum_user_num', 'label' => '成交用户量'],
            ['value' => 'deal_sum_price', 'label' => '成交金额'],
            ['value' => 'sum_commission', 'label' => '累计佣金'],
        ];
        if ($exclusive_type == 2) {
            $data1 = [
                ['value' => 'basics_sum_commission', 'label' => '基础佣金'],
                ['value' => 'exclusive_sum_commission', 'label' => '专属佣金'],
            ];
            $data = array_merge($data, $data1);
        }
        return jsonResponseSuccess($data);
    }

    //获取本机构活动列表统计
    public static function getMemberActivityDistribution($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'mem_id' => $data['mem_id'],
            'is_del' => 1,
        ];
        if ((isset($data['time_data']) && !empty($data['time_data']))) {
            $start_time = $data['time_data'][0] / 1000;
            $end_time = $data['time_data'][1] / 1000;
            $where['zd.create_time'] = ['between', [$start_time, $end_time]];
        }
        isset($data['activity_title']) && !empty($data['activity_title']) && $where['activity_title'] = ['like', '%' . $data['activity_title'] . '%'];
        $activity_data = Crud::getData('zht_activity', 2, $where, '*', '', $page, $pageSize);
        if ($activity_data) {
            //求每月单笔完成量  yx_zht_month_commission
            $where_month_commission = [
                'mem_id' => $data['mem_id'],
                'distribution_type' => 2, //1,分享用户获取佣金，2完成当月目标获取佣金
                'is_del' => 1
            ];
            if ((isset($data['time_data']) && !empty($data['time_data']))) {
                $start_time = $data['time_data'][0] / 1000;
                $end_time = $data['time_data'][1] / 1000;
                $where_month_commission['create_time'] = ['between', [$start_time, $end_time]];
            }
            $sum_month_commission = Crud::getSum('zht_distribution', $where_month_commission, 'month_commission');

            $sum_visit_num = 0; //总浏览量
            $sum_share_num = 0; //总转发量
            $sum_deal_user_num = 0; //总交易量
            $sum_deal_price = 0; //总成交金额
            $sum_commission = 0; //总公佣额
            foreach ($activity_data as $k => $v) {
                if ($v['activity_type'] == 1) {
                    $activity_data[$k]['activity_type_name'] = '接龙工具';
                } elseif ($v['activity_type'] == 2) {
                    $activity_data[$k]['activity_type_name'] = '砍价工具';
                }
                $sum_visit_num += $v['visit_num'];
                $sum_share_num += $v['share_num'];
                $sum_deal_user_num += $v['deal_sum_user_num'];
                $sum_deal_price += $v['deal_sum_price'];
                $sum_commission += $v['basics_sum_commission'] + $v['exclusive_sum_commission'] + $sum_month_commission;
            }
            $sum_income_price = $sum_deal_price - $sum_commission; //总收入额
            $activity_info = [
                'sum_visit_num' => round($sum_visit_num, 2),
                'sum_share_num' => round($sum_share_num, 2),
                'sum_deal_user_num' => round($sum_deal_user_num, 2),
                'sum_deal_price' => round($sum_deal_price, 2),
                'sum_commission' => round($sum_commission, 2),
                'sum_income_price' => round($sum_income_price, 2),
            ];
            $num = Crud::getCount('zht_distribution', $where);
            $info_data = [
                'info' => $activity_data,
                'num' => $num,
                'pageSize' => (int)$pageSize,
                'sum_commission' => $sum_commission,
                'activity_info' => $activity_info
            ];
            return jsonResponseSuccess($info_data);

        } else {
            throw new NothingMissException();
        }


    }

    //获取本机构活动列表统计字段
    public static function getMemberActivityDistributionField()
    {
        $data = [
            ['prop' => 'create_time_Exhibition', 'name' => '活动时间', 'width' => '', 'state' => ''],
            ['prop' => 'activity_img', 'name' => '活动信息', 'width' => '380', 'state' => ''],
            ['prop' => 'visit_num', 'name' => '浏览量', 'width' => '', 'state' => ''],
            ['prop' => 'share_num', 'name' => '转发分享量', 'width' => '', 'state' => '1'],
            ['prop' => 'deal_sum_user_num', 'name' => '用户成交量', 'width' => '', 'state' => ''],
            ['prop' => 'deal_sum_price', 'name' => '成交金额', 'width' => '', 'state' => ''],
            ['prop' => 'basics_sum_commission', 'name' => '普通佣金', 'width' => '', 'state' => ''],
            ['prop' => 'exclusive_sum_commission', 'name' => '专属累计佣金', 'width' => '', 'state' => ''],
            ['prop' => 'income_price', 'name' => '实际收益', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //获取本机构活动列表统计条图型
    public static function getMemberActivityDistributionChart()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if ((isset($data['time_data']) && !empty($data['time_data']))) {
            $day_start_end['start_time'] = $data['time_data'][0] / 1000;
            $day_start_end['end_time'] = $data['time_data'][1] / 1000;
        } else {
            $time = time();
            //获取本周的开始结束时间
            $day_start_end = Time::getSixDay($time);
        }
        //获取本周的每天
        $day_array = Time::getEveryDay($day_start_end['start_time'], $day_start_end['end_time']);
        $new = [];
        foreach ($day_array as $k => $v) {
            $arr[$k] = date('d', $v);
            $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
            $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
        }
        foreach ($new as $k => $v) {
            $where = [
                'mem_id' => $data['mem_id'],
                'create_time' => ['between', [$v['start'], $v['end']]],
                'is_del' => 1,
            ];
            $sum_visit_num = 0; //总浏览量
            $sum_share_num = 0; //总转发量
            $sum_deal_user_num = 0; //总交易量
            $sum_deal_price = 0; //总成交金额
            $sum_commission = 0; //总分佣额
            $activity_data = Crud::getData('zht_activity', 2, $where, '*', '', 1, 100000000);
            $where_month_commission = [
                'mem_id' => $data['mem_id'],
                'distribution_type' => 2, //1,分享用户获取佣金，2完成当月目标获取佣金
                'is_del' => 1,
                'create_time' => ['between', [$v['start'], $v['end']]],
            ];
            $sum_month_commission = Crud::getSum('zht_distribution', $where_month_commission, 'month_commission');
            foreach ($activity_data as $kk => $vv) {
                $sum_visit_num += $vv['visit_num'];
                $sum_share_num += $vv['share_num'];
                $sum_deal_user_num += $vv['deal_sum_user_num'];
                $sum_deal_price += $vv['deal_sum_price'];
                $sum_commission += $vv['basics_sum_commission'] + $vv['exclusive_sum_commission'];
            }
            $sum_commission = $sum_commission + $sum_month_commission;
            $sum_income_price = $sum_deal_price - $sum_commission; //总收入额
            $activity_info[$k] = [
                'sum_visit_num' => $sum_visit_num,
                'sum_share_num' => $sum_share_num,
                'sum_deal_user_num' => $sum_deal_user_num,
                'sum_deal_price' => $sum_deal_price,
                'sum_commission' => $sum_commission,
                'sum_income_price' => $sum_income_price,
            ];
        }
        $sum_visit_num_data = [];
        $sum_share_num_data = [];
        $sum_deal_user_num_data = [];
        $sum_deal_price_data = [];
        $sum_commission_data = [];
        $sum_income_price_data = [];
        foreach ($activity_info as $k => $v) {
            $sum_visit_num_data[] = $v['sum_visit_num'];
            $sum_share_num_data[] = $v['sum_share_num'];
            $sum_deal_user_num_data[] = $v['sum_deal_user_num'];
            $sum_deal_price_data[] = $v['sum_deal_price'];
            $sum_commission_data[] = $v['sum_commission'];
            $sum_income_price_data[] = $v['sum_income_price'];
        }
        $return_array = [
            'datalist' => [
                'legend' => ['总浏览量', '总转发量', '总成交量', '总成交额(元)', '总分佣额(元)', '总收入额(元)'],
                'xAxis' => $arr,
                'series' => [
                    [
                        'name' => '总浏览量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_visit_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#EA5514"
                            ]
                        ]

                    ],
                    [
                        'name' => '总转发量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_share_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#FFA227"
                            ]
                        ]

                    ],
                    [
                        'name' => '总成交量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_deal_user_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#FFC133"
                            ]
                        ]

                    ],
                    [
                        'name' => '总成交额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_deal_price_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#C4E35A"
                            ]
                        ]

                    ],
                    [
                        'name' => '总分佣额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_commission_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#65D8F8"
                            ]
                        ]

                    ],
                    [
                        'name' => '总收入额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_income_price_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#00a0ea"
                            ]
                        ]

                    ],
                ]
            ]
        ];
        return jsonResponseSuccess($return_array);
    }

    //获取本活动的收益
    public static function getActivityDistribution($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
//            'mem_id' => $data['mem_id'],
            'is_del' => 1,
            'id' => $data['activity_id']
        ];
        $activity_data = Crud::getData('zht_activity', 1, $where, '*');
        if ($activity_data) {
            if ($activity_data['activity_type'] == 1) {
                $activity_data['activity_type_name'] = '接龙工具';
            } elseif ($activity_data['activity_type'] == 2) {
                $activity_data['activity_type_name'] = '砍价工具';
            }
            if ($activity_data['activity_start_time'] > time()) {
                $activity_data['activity_type'] = '未开始';
            } elseif ($activity_data['activity_start_time'] < time() && $activity_data['activity_end_time'] > time()) {
                $activity_data['activity_type'] = '进行中';
            } elseif ($activity_data['activity_end_time'] < time()) {
                $activity_data['activity_type'] = '结束';
            }
            $activity_info = [
                'activity_title' => $activity_data['activity_title'],
                'activity_img' => $activity_data['activity_img'],
                'activity_type' => $activity_data['activity_type'],
                'activity_type_name' => $activity_data['activity_type_name'],
                'activity_id' => $activity_data['id'],
                'share_num' => $activity_data['share_num'],//转发量
                'visit_num' => $activity_data['visit_num'],//访问量
                'deal_sum_user_num' => $activity_data['deal_sum_user_num'],//成交量
                'deal_sum_price' => $activity_data['deal_sum_price'],//成交金额
                'sum_commission' => $activity_data['basics_sum_commission'] + $activity_data['exclusive_sum_commission'],//总佣金
                'income_price' => $activity_data['income_price'],//实付金额
            ];

            $order = 'zaud.id desc';
            isset($data['share_num']) && !empty($data['share_num']) && $order = 'zaud.share_num desc';
            isset($data['visit_num']) && !empty($data['visit_num']) && $order = 'zaud.visit_num desc';
            isset($data['deal_sum_user_num']) && !empty($data['deal_sum_user_num']) && $order = 'zaud.deal_sum_user_num desc';
            isset($data['deal_sum_price']) && !empty($data['deal_sum_price']) && $order = 'zaud.deal_sum_price desc';
            isset($data['sum_commission']) && !empty($data['sum_commission']) && $order = 'zaud.sum_commission desc';
            isset($data['basics_sum_commission']) && !empty($data['basics_sum_commission']) && $order = 'zaud.basics_sum_commission desc';
            isset($data['exclusive_sum_commission']) && !empty($data['exclusive_sum_commission']) && $order = 'zaud.exclusive_sum_commission desc';

            //求用户
            $where_distribution = [
                'zaud.activity_id' => $data['activity_id'],
                'zaud.is_del' => 1
            ];
            isset($data['user_name']) && !empty($data['user_name']) && $where_distribution['u.name'] = ['like', '%' . $data['user_name'] . '%'];
            $join = [
                ['yx_user u', 'zaud.user_id = u.id', 'left'], //用户表
            ];
            $alias = 'zaud';
            $info = Crud::getRelationData('zht_activity_user_distribution', $type = 2, $where_distribution, $join, $alias, $order, $field = 'zaud.*,u.name', $page, $pageSize);
            $num = Crud::getCountSelNun('zht_activity_user_distribution', $where_distribution, $join, $alias, $field = 'zaud.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
                'activity_info' => $activity_info,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取本活动的收益字段
    public static function getActivityDistributionField()
    {
        $data = [
            ['prop' => 'name', 'name' => '用户名', 'width' => '', 'state' => ''],
            ['prop' => 'share_num', 'name' => '转发分享量', 'width' => '', 'state' => '1'],
            ['prop' => 'visit_num', 'name' => '浏览量', 'width' => '', 'state' => ''],
            ['prop' => 'deal_sum_user_num', 'name' => '用户成交量', 'width' => '', 'state' => ''],
            ['prop' => 'deal_sum_price', 'name' => '成交金额', 'width' => '', 'state' => ''],
            ['prop' => 'basics_sum_commission', 'name' => '普通佣金', 'width' => '', 'state' => ''],
            ['prop' => 'exclusive_sum_commission', 'name' => '专属累计佣金', 'width' => '', 'state' => ''],
            ['prop' => 'month_commission', 'name' => '专属月佣金', 'width' => '', 'state' => ''],
//            ['prop' => 'income_price', 'name' => '实际收益', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //获取获取本活动的排序接口
    public static function getActivityDistributionFieldList()
    {
        $data = [
            ['value' => 'share_num', 'label' => '转发分享量'],
            ['value' => 'visit_num', 'label' => '链接访问量'],
            ['value' => 'deal_sum_user_num', 'label' => '成交用户量'],
            ['value' => 'deal_sum_price', 'label' => '成交金额'],
            ['value' => 'basics_sum_commission', 'label' => '普通佣金'],
            ['value' => 'exclusive_sum_commission', 'label' => '专属累计佣金'],
            ['value' => 'month_commission', 'label' => '专属月佣金'],
        ];
        return jsonResponseSuccess($data);
    }

    //获取某一活动的统计图
    public static function getActivityDistributionChart()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if ((isset($data['time_data']) && !empty($data['time_data']))) {
            $day_start_end['start_time'] = $data['time_data'][0] / 1000;
            $day_start_end['end_time'] = $data['time_data'][1] / 1000;
        } else {
            $time = time();
            //获取本周的开始结束时间
            $day_start_end = Time::getSixDay($time);
        }
        //获取本周的每天
        $day_array = Time::getEveryDay($day_start_end['start_time'], $day_start_end['end_time']);
        $new = [];
        foreach ($day_array as $k => $v) {
            $arr[$k] = date('d', $v);
            $new[$k]['start'] = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
            $new[$k]['end'] = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
        }
        foreach ($new as $k => $v) {
            $where = [
                'mem_id' => $data['mem_id'],
                'activity_id' => $data['activity_id'],
                'create_time' => ['between', [$v['start'], $v['end']]],
                'is_del' => 1,
            ];
            $sum_visit_num = 0; //总浏览量
            $sum_share_num = 0; //总转发量
            $sum_deal_user_num = 0; //总交易量
            $sum_deal_price = 0; //总成交金额
            $sum_commission = 0; //总分佣额
            $activity_data = Crud::getData('zht_activity_user_distribution', 2, $where, '*', '', 1, 100000000);
            foreach ($activity_data as $kk => $vv) {
                $sum_visit_num += $vv['visit_num'];
                $sum_share_num += $vv['share_num'];
                $sum_deal_user_num += $vv['deal_sum_user_num'];
                $sum_deal_price += $vv['deal_sum_price'];
                $sum_commission += $vv['sum_commission'];
            }
            $sum_income_price = $sum_deal_price - $sum_commission; //总收入额
            $activity_info[$k] = [
                'sum_visit_num' => $sum_visit_num,
                'sum_share_num' => $sum_share_num,
                'sum_deal_user_num' => $sum_deal_user_num,
                'sum_deal_price' => $sum_deal_price,
                'sum_commission' => $sum_commission,
                'sum_income_price' => $sum_income_price,
            ];
        }
        $sum_visit_num_data = [];
        $sum_share_num_data = [];
        $sum_deal_user_num_data = [];
        $sum_deal_price_data = [];
        $sum_commission_data = [];
        $sum_income_price_data = [];
        foreach ($activity_info as $k => $v) {
            $sum_visit_num_data[] = $v['sum_visit_num'];
            $sum_share_num_data[] = $v['sum_share_num'];
            $sum_deal_user_num_data[] = $v['sum_deal_user_num'];
            $sum_deal_price_data[] = $v['sum_deal_price'];
            $sum_commission_data[] = $v['sum_commission'];
            $sum_income_price_data[] = $v['sum_income_price'];
        }
        $return_array = [
            'datalist' => [
                'legend' => ['总浏览量', '总转发量', '总成交量', '总成交额(元)', '总分佣额(元)', '总收入额(元)'],
                'xAxis' => $arr,
                'series' => [
                    [
                        'name' => '总浏览量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_visit_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#EA5514"
                            ]
                        ]

                    ],
                    [
                        'name' => '总转发量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_share_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#FFA227"
                            ]
                        ]

                    ],
                    [
                        'name' => '总成交量',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_deal_user_num_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#FFC133"
                            ]
                        ]

                    ],
                    [
                        'name' => '总成交额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_deal_price_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#C4E35A"
                            ]
                        ]

                    ],
                    [
                        'name' => '总分佣额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_commission_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#65D8F8"
                            ]
                        ]

                    ],
                    [
                        'name' => '总收入额(元)',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => $sum_income_price_data,
                        'itemStyle' => [
                            'normal' => [
                                'color' => "#00a0ea"
                            ]
                        ]

                    ],
                ]
            ]
        ];

        return jsonResponseSuccess($return_array);
    }


}