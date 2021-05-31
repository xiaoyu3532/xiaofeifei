<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/7 0007
 * Time: 19:52
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Controller;
use think\Db;

class Automatic extends Controller
{
    //课程表自动排第几课attend_class_num
    public static function CourseTimetableSort()
    {
        $time_table = Crud::getData('zht_course_timetable', 2, ['is_del' => 1], 'arrange_course_id', '', '1', '100000000000', 'arrange_course_id');
        if ($time_table) {
            foreach ($time_table as $k => $v) {
                $time_table_list = Crud::getData('zht_course_timetable', 2, ['arrange_course_id' => 19, 'is_del' => 1], 'id,day,arrange_course_id', 'day_time_start', '1', '10000');
                if ($time_table_list) {
                    $i = 0;
                    foreach ($time_table_list as $kk => $vv) {
                        $i++;
                        $data = Crud::setUpdate('zht_course_timetable', ['id' => $vv['id']], ['attend_class_num' => $i]);
                        dump($data);
                    }
                }
            }
        }

    }

    //删除没有机构ID的排课表 排表时间加10分种
    public static function delZhtCourseTimetable()
    {
        //求出没有机构ID排课表
        $where = [
            'is_del' => 1,
        ];
        $time_table = Crud::getData('zht_course_timetable', 2, $where, 'id,create_time,arrange_course_id,mem_id', '', 1, 1000000);
        if ($time_table) {
            foreach ($time_table as $k => $v) {
                $last_create_time = $v['create_time'] + 600;
                if ($v['mem_id'] == '' || $v['arrange_course_id'] == '') {
                    if (time() > $last_create_time) {
                        $a = Crud::setUpdate('zht_course_timetable', ['id' => $v['id']], ['is_del' => 2]);
                    }
                    dump($a);
                }

            }
        }


    }

    //自动消课
    public static function Cancelclass()
    {
        //将所有所有大于当前时间的课程表取出来
        $where_course_timetable = [
            'day_time_start' => ['<=', time()],
            'is_del' => 1,
            'arrange_course_id' => ['<>', '']
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where_course_timetable, 'id', '', 1, 1000000);
        if ($course_timetable) {
            foreach ($course_timetable as $k => $v) {
                //yx_zht_student_class_list
                $where_student_class_list = [
                    'course_timetable_id' => $v['id'],
//                'student_course_type' => 1,  //1未上课，2已上课，3，请假，4请假已处理，5请假未处理
                    'is_del' => 1
                ];
                $student_class_list = Crud::getData('zht_student_class_list', 2, $where_student_class_list, 'id,is_confirm', '', 1, 1000000);
                if ($student_class_list) {
                    //修改课程已上课状态
                    foreach ($student_class_list as $kk => $vv) {
                        if ($vv['is_confirm'] != 1) {
                            Crud::setUpdate('zht_student_class_list', ['id' => $vv['id']], ['is_confirm' => 2]); //1,已销课程，2未销课程
                        }
                        Crud::setUpdate('zht_student_class_list', ['id' => $vv['id']], ['course_enter_type' => 2]); //1此节课时间没有过，2时间已过（获取进度数）
                    }
                }
            }
        }


    }

    //统计上了几节课
    public static function Alreadyon()
    {
        //将所有所有大于当前时间的课程表取出来
        $now_time = time();
        $front_time = time() - 3600; //前一小时
        $where_course_timetable = [
            'day_time_start' => ['between', [$front_time, $now_time]],
            'is_del' => 1,
            'arrange_course_id' => ['<>', ''],
//            'is_statistics' => 2, //1已统计，2未统计（统计是否上课）
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where_course_timetable, '*', '', 1, 1000000);
        if ($course_timetable) {
            foreach ($course_timetable as $k => $v) {
                $arrange_course_update = Crud::setUpdate('zht_arrange_course', ['id' => $v['arrange_course_id']], ['finish_course_num' => $v['attend_class_num']]);
                dump($arrange_course_update);
            }
        }


    }


    public static function Cancelclasss()
    {
        //将所有所有大于当前时间的课程表取出来
        $where_course_timetable = [
            'day_time_start' => ['<=', time()],
            'is_del' => 1,
            'arrange_course_id' => ['<>', '']
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where_course_timetable, '*', '', 1, 1000000);
        foreach ($course_timetable as $k => $v) {
            //yx_zht_student_class_list
            $where_student_class_list = [
                'course_timetable_id' => $v['id'],
                'student_course_type' => 1,  //1未上课，2已上课，3，请假，4请假已处理，5请假未处理
                'is_del' => 1
            ];
            $student_class_list = Crud::getData('zht_student_class_list', 2, $where_student_class_list, '*', '', 1, 1000000);
            if ($student_class_list) {
                //修改课程已上课状态
                foreach ($student_class_list as $kk => $vv) {
                    Crud::setUpdate('zht_student_class_list', ['id' => $vv['id']], ['student_course_type' => 2]);
                    $where_course_hour_student_class = [
                        'zchsc.arrange_course_id' => $vv['arrange_course_id'],
                        'zchsc.is_del' => 1,
                        'zchr.student_id' => $vv['student_id']
                    ];
                    $table = 'zht_course_hour_student_class';
                    $join = [
                        ['yx_zht_course_hour_record zchr', 'zchsc.course_hour_record_id = zchr.id', 'left'], //课程记录ID
                    ];
                    $alias = 'zchsc';
                    $info = Crud::getRelationData($table, 1, $where_course_hour_student_class, $join, $alias, $order = 'zchr.create_time', $field = 'zchr.*');
                    Crud::setIncs('zht_course_hour_record', ['id' => $info['id']], 'cancelled_num');
                }
            }
        }
//        dump($course_timetable);

    }

    //上课提醒
    public static function AttendClassRemind()
    {
        $now_time = time();
//        $front_time = time() - 3600; //前一小时
        $front_time = time() - 172800; //前二天
        //将所有所有大于当前时间的课程表取出来
        $where_course_timetable = [
            'day_time_start' => ['between', [$front_time, $now_time]],
            'is_remind' => 2, //1通知，2未通知(提示上课）
            'is_del' => 1,
            'arrange_course_id' => ['<>', '']
        ];
        $course_timetable = Crud::getData('zht_course_timetable', 2, $where_course_timetable, '*', '', 1, 1000000);
        foreach ($course_timetable as $k => $v) {
//            Db::startTrans();
            //yx_zht_student_class_lis
            $where_student_class_list = [
                'scl.course_timetable_id' => $v['id'],
                'scl.is_del' => 1
            ];
            $join = [
                ['yx_zht_order zo', 'scl.order_id = zo.order_id', 'left'], //订单
                ['yx_zht_course_timetable zct', 'scl.course_timetable_id = zct.id', 'left'], //机构
            ];
            $alias = 'scl';
            $info = Crud::getRelationData('zht_student_class_list', 2, $where_student_class_list, $join, $alias, $order = 'scl.id', $field = 'scl.id,scl.order_id,zct.attend_class_num,zo.student_id,zo.user_id,zo.course_id', 1, 100000000);
            if ($info) { //yx_zht_message
                foreach ($info as $kk => $vv) {
                    if (!isset($vv['user_id']) || empty($vv['user_id']) || $vv['user_id'] == null) {
                        $vv['user_id'] = 0;
                    }
                    if (!isset($vv['student_id']) || empty($vv['student_id']) || $vv['student_id'] == null) {
                        $vv['student_id'] = 0;
                    }
                    $add_message = [
                        'user_id' => $vv['user_id'],
                        'course_id' => $vv['course_id'],
                        'student_id' => $vv['student_id'],
                        'class_hour' => $vv['attend_class_num'],
                        'course_category' => 2,//1线上 2线下
                        'type' => 1,//上课提醒
                    ];
                    // 添加上课提醒
                    $message_info = Crud::setAdd('zht_message', $add_message);
                    if (!$message_info) {
//                        Db::rollback();
                        throw new AddMissException();
                    }
                }
                //修改课时状态
                $update_course_timetable = Crud::setUpdate('zht_course_timetable', ['id' => $v['id']], ['is_remind' => 1]);
                if (!$update_course_timetable) {
//                    Db::rollback();
                    throw new UpdateMissException();
                }
//                Db::commit();
                dump($message_info);
                dump($update_course_timetable);
            }

        }

    }

    //查看活动当前状态
    public static function setActivityType()
    {
        $info = Crud::getData('zht_activity', 2, ['is_del' => 1], 'id,activity_start_time,activity_end_time,status');
        if ($info) {
            foreach ($info as $k => $v) {
                if ($v['activity_start_time'] > time()) {
                    $update = ['status' => 1];
                } elseif ($v['activity_start_time'] < time() && $v['activity_end_time'] > time()) {
                    $update = ['status' => 2];
                } elseif ($v['activity_end_time'] < time()) {
                    $update = ['status' => 3];
                }
                if ($v['status'] == 3) {
                    $update = ['status' => 3];
                }
                $aa = Crud::setUpdate('zht_activity', ['id' => $v['id']], $update);
            }
            dump($aa);
        }
    }

    //计算专属目标佣金
    public static function statisticsExclusiveTargetCommission()
    {
        $where = [
            'exclusive_type' => 2,//1分销员，2专属分销员
            'is_del' => 1
        ];
        $distribution_relation_data = Crud::getData('zht_distribution_relation', 2, $where, '*');
        if (!$distribution_relation_data) {
            throw new NothingMissException();
        }
        //获取当月的开绐结束时间
        $start_timestamp = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $end_timestamp = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        foreach ($distribution_relation_data as $k => $v) {
            $distribution_where = [
                'share_id' => $v['user_id'],
                'is_del' => 1,
                'mem_id' => $v['mem_id'],
                'commission_type' => 2, //分佣类型 1普通  2专属
                'distribution_type' => 1, //1,分享用户获取佣金，2完成当月目标获取佣金
                'create_time' => ['between', [$start_timestamp, $end_timestamp]]
            ];
            //获取所有的专属人员
            $distribution_num = Crud::getCount('zht_distribution', $distribution_where);
            if ($v['target_num'] >= $distribution_num) {
                $distribution_where = [
                    'share_id' => $v['user_id'],
                    'is_del' => 1,
                    'mem_id' => $v['mem_id'],
                    'commission_type' => 2, //分佣类型 1普通  2专属
                    'distribution_type' => 2, //1,分享用户获取佣金，2完成当月目标获取佣金
                    'create_time' => ['between', [$start_timestamp, $end_timestamp]]
                ];
                //查看用户在本机构是否加了目标记录
                $share_num = Crud::getCount('zht_distribution', $distribution_where);
                if ($share_num) {
                    return jsonResponse('3000', '本月已添加');
                } else {
                    $add_distribution = [
                        'share_id' => $v['user_id'],
                        'month_commission' => $v['month_commission'],
                        'mem_id' => $v['mem_id'],
                        'commission_type' => 2, //分佣类型 1普通  2专属
                        'distribution_type' => 2, //1,分享用户获取佣金，2完成当月目标获取佣金
                    ];
                    $share_num = Crud::setAdd('zht_distribution', $add_distribution);
                    if ($share_num) {
                        return jsonResponseSuccess($share_num);
                    }
                }
            }
        }
    }

    //缴费通知
    //arrange_course_id 排课ID
    //course_timetable_id 排课时间ID
    //course_id 课程ID
    public static function setPayNotice()
    {
        $data = input();  //yx_zht_student_class_list
        $where = [
            'arrange_course_id' => $data['arrange_course_id'],
            'is_del' => 1,
        ];
        //查询我要发送短信的短信记录信息及将第几节变为数组
        $short_message_data = Crud::getData('zht_short_message', 2, $where, 'course_id,arrange_course_id,course_number', '', 1, 10000000);
        if ($short_message_data) {
            foreach ($short_message_data as $k => $v) {
                $short_message_data[$k]['course_number'] = explode(',', $v['course_number']);
            }
        }

        $course_number_data = Crud::getData('zht_course_timetable', 1, ['id' => $data['course_timetable_id'], 'is_del' => 1], 'attend_class_num');
        if (!$course_number_data) {
            throw new NothingMissException();
        }

        foreach ($short_message_data as $k => $v) {
            //查看现在上完的课是否满足发短信条件
            if (in_array($course_number_data['attend_class_num'], $v['course_number'])) {
                $where_course = [
                    'id' => $data['course_id'],
                    'is_del' => 1
                ];
                //获取课程名
                $course_data = Crud::getData('zht_course', 1, $where_course, 'course_name');
//                dump($course_data);
                $where_student_class_list = [
                    'zscl.course_timetable_id' => $data['course_timetable_id'],
                    'zscl.arrange_course_id' => $data['arrange_course_id'],
//                    'zscl.is_del' =>1,
                ];

                $join = [
                    ['yx_lmport_student s', 'zscl.student_id =s.id ', 'left'],  //学生信息
                ];
                $alias = 'zscl';
                $table = 'zht_student_class_list';
                $user_data = Crud::getRelationData($table, $type = 1, $where_student_class_list, $join, $alias, $order = '', $field = 's.phone,s.student_name', 1, 10000000);
                dump($user_data);
            }

        }

    }

    //15分钟后未支付退回名额
    public static function returnQuota()
    {
        $where_order = [
            'status' => 1,
            'is_del' => 1,
        ];
        $order_count = Crud::getCount('zht_order', $where_order);
        $num = ceil(($order_count / 1000));
        for ($i = 0; $i <= $num; $i++) {
            $order_data = Crud::getData('zht_order', 2, $where_order, 'id,order_num,create_time,course_id,course_num_id,course_category', 'id desc', $i, '1000');
            if ($order_data) {
                foreach ($order_data as $k => $v) {
                    $time_data = time() - 900;
                    if ($v['create_time'] < $time_data) {
                        Db::startTrans();
                        //减报名人数
                        $enroll_data = Crud::setDecs('zht_course_num', ['id' => $v['course_num_id']], 'enroll_num');
                        if (!$enroll_data) {
                            Db::rollback();
                        }
                        //修改小订单状态
                        $order_update = Crud::setUpdate('zht_order', ['id' => $v['id']], ['status' => 7]);
                        if (!$order_update) {
                            Db::rollback();
                        }
                        //修改大订单状态
                        $order_num_update = Crud::setUpdate('zht_order_num', ['order_num' => $v['order_num']], ['status' => 7]);
                        if (!$order_num_update) {
                            Db::rollback();
                        }
                        Db::commit();
                    }
                }
            }
        }

    }


}