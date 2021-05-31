<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/21 0021
 * Time: 11:35
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class CourseTimetable
{
    //获取最近上课的列表
    //$user_id 用户ID
    //$latitude 经度
    //$longitude 纬度
    //$student_id 学生ID
    //$gettype 为0时获取返回下一次上课的所有信息（课程表），1为返回个人中心上课数据名称
    //$time_where 0为只获取下次的，1为如果明天没课了，则换回后面的课程
    public static function getCourseTimetable($user_id, $latitude = '', $longitude = '', $student_id, $gettype = 0, $time_where = 0)
    {
        //获取用户所有的报名课程
        $where = [
            'uid' => $user_id,
            'status' => ['in', [2, 5, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'is_del' => 1,
            'student_id' => $student_id,
        ];
        $table = 'order';
        $order_info = Crud::getData($table, $type = 2, $where, $field = 'id,cid', $order = '', $page = '1', $pageSize = '10000');
        if ($order_info) {
            //获取当天的时间

            $new_time = strtotime(date("Y-m-d"), time());

            $table1 = request()->controller();
            $course_timetable_data = [];
            foreach ($order_info as $k => $v) {
                //返回时间段
                if ($time_where == 0) {
                    $where1 = [
                        'day' => ['=', $new_time],
                        'course_id' => $v['cid'],
                        'is_del' => 1,
                    ];
                } else {
                    $where1 = [
                        'course_id' => $v['cid'],
                        'is_del' => 1,
                    ];
                }
                $timetable_data = Crud::getData($table1, $type = 2, $where1, $field = 'day,course_id,time_slot', $order = 'day', $page = '1', $pageSize = '1000000');
                if ($timetable_data) {
                    foreach ($timetable_data as $kk => $vv) {
                        $timetable_data[$kk]['order_id'] = $v['id'];
                    }
                }
                $course_timetable_data[] = assoc_unique($timetable_data, 'time_slot');
            }
            //三维变二维
            $course_timetable_data = Three_Two_array($course_timetable_data);
            //排序
            $course_timetable_data = array_sort($course_timetable_data, 'time_slot', SORT_ASC);
            //获取当前小时
            $current_time = time() - $new_time;

            //获取当前时间在哪个时间段
            if ($time_where == 0) {
                $where3 = [
                    'start_time' => ['>=', $current_time],
                    'is_del' => 1
                ];
            } else {
                $where3 = [
                    'is_del' => 1
                ];
            }
            $table2 = 'time_slot';
            $current_time = Crud::getData($table2, $type = 2, $where3, $field = 'time_id,name,start_time', $order = 'start_time asp', $page = '1', $pageSize = '10000');
            $time_table_array = [];
            foreach ($course_timetable_data as $k => $v) {
                if ($v['time_slot'] > $current_time[0]['time_id']) {
                    //求时间段名称
                    $hour_time = Crud::getData($table2, $type = 1, ['time_id' => $v['time_slot']], $field = 'name');
                    $time_table_array [] = [
                        'course_id' => $v['course_id'],
                        'day_time' => date('Y-m-d', $v['day']),
                        'hour_time' => $hour_time['name'],
                        'order_id' => $v['order_id'],
                        'time_slot' => $v['time_slot'],
                    ];
                }
            }
            if (empty($time_table_array)) {
                $new_data = self::getCourseTimetable($user_id, $latitude, $longitude, $student_id, $gettype, 1);
                if ($new_data) {
                    return $new_data;
                } else {
                    throw new NothingMissException();
                }

            }

            $time_table_array = assoc_unique($time_table_array, 'course_id');
            $time_table_array = array_sort($time_table_array, 'time_slot', SORT_ASC);


            if ($time_table_array) {
                foreach ($time_table_array as $k => $v) {
                    $where = [
                        'uid' => $user_id,
                        'id' => $v['order_id'],
                        'status' => ['in', [2, 5, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                        'is_del' => 1
                    ];
                    $table = 'order';
                    $order_info = Crud::getData($table, $type = 1, $where, $field = 'id,cid,cou_status,already_num', $order = '', $page = '1', $pageSize = '10000');
                    if ($order_info) {
                        $time_table_array[$k]['answer__num'] = $order_info['already_num'] + 1;
                        $time_table_array[$k]['already_num'] = $order_info['already_num'];
                        if ($order_info['cou_status'] == 1) {
                            $where1 = [
                                'c.is_del' => 1,
                                'c.type' => 1,
                                'c.id' => $order_info['cid'],
                            ];
                            $join = [
                                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                                ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
                            ];
                            $alias = 'c';
                            $table = 'course';
                            if ($gettype == 1) {
                                $field = ['cu.name,c.c_num,c.img,cl.province,cl.city,cl.area,cl.address'];
                            } else {
                                $field = ['cu.name,c.c_num,c.img,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                            }
                            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
                        } elseif ($order_info['cou_status'] == 2) {
                            $where1 = [
                                'ec.is_del' => 1,
                                'ec.type' => 1,
                                'ec.id' => $order_info['cid'],
                            ];
                            $join = [
                                ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
                                ['yx_classroom cl', 'ec.classroom_id = cl.id', 'left'],
                            ];
                            if ($gettype == 1) {
                                $field = ['cu.name,ec.c_num,ec.img,cl.province,cl.city,cl.area,cl.address'];
                            } else {
                                $field = ['cu.name,ec.c_num,ec.img,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                            }

                            $alias = 'ec';
                            $table = 'experience_course';
                            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                        } elseif ($order_info['cou_status'] == 3) {
                            $where1 = [
                                'cc.is_del' => 1,
                                'cc.type' => 1,
                                'cc.id' => $order_info['cid'],
                            ];
                            $join = [
                                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
                                ['yx_community_classroom cl', 'cc.classroom_id = cl.id', 'left'],
                            ];
                            if ($gettype == 1) {
                                $field = ['cu.name,cc.c_num,cc.img,cl.province,cl.city,cl.area,cl.address'];
                            } else {
                                $field = ['cu.name,cc.c_num,cc.img,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                            }

                            $alias = 'cc';
                            $table = 'community_course';
                            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                        } elseif ($order_info['cou_status'] == 4) {
                            $where1 = [
                                'sc.is_del' => 1,
                                'sc.type' => 1,
                                'sc.id' => $order_info['cid'],
                            ];
                            $join = [
                                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                                ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
                            ];
                            if ($gettype == 1) {
                                $field = ['cu.name,sc.c_num,sc.img,cl.province,cl.city,cl.area,cl.address'];
                            } else {
                                $field = ['cu.name,sc.c_num,sc.img,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                            }

                            $alias = 'sc';
                            $table = 'seckill_course';
                            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
                        } elseif ($order_info['cou_status'] == 5) {
                            $where1 = [
                                'sc.is_del' => 1,
                                'sc.type' => 1,
                                'sc.id' => $order_info['cid'],
                            ];
                            $join = [
                                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                                ['yx_synthetical_classroom cl', 'sc.classroom_id = cl.id', 'left'],
                            ];
                            if ($gettype == 1) {
                                $field = ['cu.name,sc.c_num,sc.img,cl.province,cl.city,cl.area,cl.address'];
                            } else {
                                $field = ['cu.name,sc.c_num,sc.img,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                            }

                            $alias = 'sc';
                            $table = 'synthetical_course';
                            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                        }
                        if (!empty($info_course['img'])) {
                            $info_course['img'] = get_take_img($info_course['img']);
                        }

                        $time_table_array[$k]['name'] = $info_course['name'];

                        if ($gettype == 0) {
                            $time_table_array[$k]['img'] = $info_course['img'];
                            $time_table_array[$k]['c_num'] = $info_course['c_num'];
                            $time_table_array[$k]['province'] = $info_course['province'];
                            $time_table_array[$k]['city'] = $info_course['city'];
                            $time_table_array[$k]['area'] = $info_course['area'];
                            $time_table_array[$k]['address'] = $info_course['address'];
                            $time_table_array[$k]['longitude'] = $info_course['longitude'];
                            $time_table_array[$k]['latitude'] = $info_course['latitude'];
                        }

                    }
                }
                return jsonResponseSuccess($time_table_array);
            }
            //获取当天时间
            //用当天时间和课程ID获取当前最近的时间段返回

        } else {
            throw new NothingMissException();
        }

    }

    //获取本月哪几天有课程
    public static function getMonthNum($user_id, $date, $student_id)
    {
        $where = [
            'uid' => $user_id,
            'status' => ['in', [2, 5, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'is_del' => 1,
            'student_id' => $student_id,
        ];
        $table = 'order';
        $order_info = Crud::getData($table, $type = 2, $where, $field = 'id,cid', $order = '', $page = '1', $pageSize = '10000');
        if (!$order_info) {
            throw new NothingMissException();
        }
        $firstday = date('Y-m-01', strtotime($date));//获取当月第一天
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));//获取当月最后一天
        //转成时间戳
        $firstday = strtotime($firstday);
        //转成时间戳
        $lastday = strtotime($lastday);
        $table1 = request()->controller();
        $course_timetable_data = [];
        foreach ($order_info as $k => $v) {
            //返回时间段
            $where1 = [
                'day' => ['between', [$firstday, $lastday]],
                'course_id' => $v['cid'],
                'is_del' => 1
            ];
            $timetable_data = Crud::getData($table1, $type = 2, $where1, $field = 'day,course_id,time_slot', $order = 'day', $page = '1', $pageSize = '1000000');
            if ($timetable_data) {
                foreach ($timetable_data as $kk => $vv) {
                    $timetable_data[$kk]['order_id'] = $v['id'];
                }
            }
            $course_timetable_data[] = assoc_unique($timetable_data, 'day');
        }
        if (!$course_timetable_data) {
            throw  new NothingMissException();
        }

        //三维变二维
        $course_timetable_data = Three_Two_array($course_timetable_data);
        //将时间戳转变时间
        foreach ($course_timetable_data as $k => $v) {
//            $course_timetable_data[$k]['day'] = date('Y-m-d',$v['day']);
            $course_timetable_data[$k]['day'] = date('d', $v['day']);
        }
//        dump($course_timetable_data);exit;

        $j = date("t", strtotime($date)); //获取当前月份天数
        $firstday = date('Y-m-01', strtotime($date));//获取当月第一天 时间
        $start_time = strtotime(date($firstday));//获取本月第一天时间戳
        $array = array();
        for ($i = 0; $i < $j; $i++) {
//            $array[] = date('d', $start_time + $i * 86400);
            $array[] = [
                'time' => date('Y-m-d', $start_time + $i * 86400), //每隔一天赋值给数组
                'day' => date('d', $start_time + $i * 86400),
                'selection' => 2,
            ];
        }
        //求某月的时间天数
//        dump($course_timetable_data);
        $day = [];
        foreach ($array as $k => $v) {
            foreach ($course_timetable_data as $kk => $vv) {
                if ($v['day'] == $vv['day']) {
                    $day[] = $vv['day'];
                    $array[$k]['selection'] = 1;
//                    $array[$k]['order_id'] = $vv['order_id'];
                }


            }
        }
//        dump($day);
//        dump($array);exit;
        if ($day) {
            $conlist = [
                'nyday' => $date,
                'day' => $day,
            ];
            return jsonResponseSuccess($conlist);
        }
    }

    //获取本周哪天有课程
    public static function getweekCourse($user_id, $new_time, $student_id)
    {
        if ($new_time) {
            $new_time = strtotime($new_time);
        }
        //获取用户所有的报名课程
        $where = [
            'uid' => $user_id,
            'status' => ['in', [2, 5, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            'is_del' => 1,
            'student_id' => $student_id,
//            'cid' => 1388, //测试用
        ];
        $table = 'order';
        $order_info = Crud::getData($table, $type = 2, $where, $field = 'id,cid,cou_status', $order = '', $page = '1', $pageSize = '10000');
        if ($order_info) {
            $order_info = self::getCourse($order_info);
            //本周开始结束时间戳
            $week_time = self::getWeekMyActionAndEnd($new_time);

            $table1 = request()->controller();
//            dump(date('Y-m-d H:i:s',$week_time['week_start']));
//            dump(date('Y-m-d H:i:s',$week_time['week_end']));
            $course_timetable_data = [];
            foreach ($order_info as $k => $v) {
                //返回天数
                $where1 = [
                    'day' => ['between', [$week_time['week_start'], $week_time['week_end']]],
                    'course_id' => $v['cid'],
                    'is_del' => 1,
                ];
                $timetable_data = Crud::getData($table1, $type = 2, $where1, $field = 'day,course_id,time_slot,type', $order = 'day', $page = '1', $pageSize = '1000000');
                if ($timetable_data) {
                    foreach ($timetable_data as $kk => $vv) {
                        $timetable_data[$kk]['order_id'] = $v['id'];
                        $timetable_data[$kk]['name'] = $v['name'];
                        $timetable_data[$kk]['cou_status'] = $v['cou_status'];
                    }
                }
//
                $day = array_column($timetable_data, 'day');
                $time_slot = array_column($timetable_data, 'time_slot');
                $res = [];
                foreach ($day as $key => $val) {
                    $res[] = ['day' => $day[$key], 'time_slot' => $time_slot[$key]];
                }
                $res = array_unique($res, SORT_REGULAR);
                $timetable_data = remove_duplicate($timetable_data, $res, ['day', 'time_slot']);
                $course_timetable_data[] = $timetable_data;
//                $course_timetable_data[] = assoc_unique($timetable_data,'time_slot');
            }
            //三维变二维
            $course_timetable_data = Three_Two_array($course_timetable_data);


            //排序
            $course_timetable_data = array_sort($course_timetable_data, 'time_slot', SORT_ASC);
        }

        for ($i = $week_time['week_start']; $i < $week_time['week_end']; $i += 86400) {
            $year = $i;
//            $Timedata[] = date('j', $i);
            $Timedata[] = [
                'time' => $i,
                'day' => date('j', $i),
            ];
        }
        foreach ($Timedata as $k => $v) {
            foreach ($course_timetable_data as $kk => $vv) {
                if ($v['time'] == $vv['day']) {
//                    $Timedata[$k]['name']=$vv['name'];
//                    $Timedata[$k]['time_slot']=$vv['time_slot'];
                    $Timedata[$k]['info'][] = $vv;
                }
            }
        }
//        dump($Timedata);exit;
        $week_name = array('一', '二', '三', '四', '五', '六', '日');

        $week_day = [];//组合星期数组
        foreach ($Timedata as $k => $v) {
            //组合星期数组
//            $week_day = [];
            foreach ($week_name as $kk => $vv) {
                if ($k == $kk) {
                    $week_day[] = [
                        'week' => $vv,
                        'day' => $v['day'],
                        'time' => $v['time'] * 1000,
                    ];
                }
            }
        }
        $sw = [];
        $xw = [];
        $ws = [];
//        dump($Timedata);
        foreach ($Timedata as $k => $v) {
            //组合上午下午晚上数组
            if (isset($v['info'])) {
                foreach ($v['info'] as $ik => $iv) {
                    if ($iv['type'] == 1) {
                        $sw[] = $iv;
                    }
                    if ($iv['type'] == 2) {
                        $xw[] = $iv;
                    }
                    if ($iv['type'] == 3) {
                        $ws[] = $iv;
                    }
                }
            }
        }
//        dump($sw);

        $sw_data = self::getTimeCourse($sw);
        $xw_data = self::getTimeCourse($xw);
        $ws_data = self::getTimeCourse($ws);

        $day_data = [
            'week_day' => $week_day,
            'sw_kc' => $sw_data,
            'xw_kc' => $xw_data,
            'ws_kc' => $ws_data,
        ];

        if ($day_data) {
            return jsonResponseSuccess($day_data);
        }
    }

    //获取课程信息
    public static function getCourse($time_table_array)
    {

        foreach ($time_table_array as $k => $v) {
            $where = [
                'id' => $v['id'],
                'status' => ['in', [2, 5, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
                'is_del' => 1
            ];
            $table = 'order';
            $order_info = Crud::getData($table, $type = 1, $where, $field = 'id,cid,cou_status,already_num', $order = '', $page = '1', $pageSize = '10000');
            if ($order_info) {
                $time_table_array[$k]['already_num'] = $order_info['already_num'] + 1;
                if ($order_info['cou_status'] == 1) {
                    $where1 = [
                        'c.is_del' => 1,
                        'c.type' => 1,
                        'c.id' => $order_info['cid'],
                    ];
                    $join = [
                        ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                        ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
                    ];
//                    $field = ['cu.name,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                    $field = ['cu.name'];
                    $alias = 'c';
                    $table = 'course';
                    $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
                } elseif ($order_info['cou_status'] == 2) {
                    $where1 = [
                        'ec.is_del' => 1,
                        'ec.type' => 1,
                        'ec.id' => $order_info['cid'],
                    ];
                    $join = [
                        ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
                        ['yx_classroom cl', 'ec.classroom_id = cl.id', 'left'],
                    ];
//                    $field = ['cu.name,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                    $field = ['cu.name'];
                    $alias = 'ec';
                    $table = 'experience_course';
                    $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                } elseif ($order_info['cou_status'] == 3) {
                    $where1 = [
                        'cc.is_del' => 1,
                        'cc.type' => 1,
                        'cc.id' => $order_info['cid'],
                    ];
                    $join = [
                        ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
                        ['yx_community_classroom cl', 'cc.classroom_id = cl.id', 'left'],
                    ];
//                    $field = ['cu.name,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                    $field = ['cu.name'];
                    $alias = 'cc';
                    $table = 'community_course';
                    $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                } elseif ($order_info['cou_status'] == 4) {
                    $where1 = [
                        'sc.is_del' => 1,
                        'sc.type' => 1,
                        'sc.id' => $order_info['cid'],
                    ];
                    $join = [
                        ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                        ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
                    ];
//                    $field = ['cu.name,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                    $field = ['cu.name'];
                    $alias = 'sc';
                    $table = 'seckill_course';
                    $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
                } elseif ($order_info['cou_status'] == 5) {
                    $where1 = [
                        'sc.is_del' => 1,
                        'sc.type' => 1,
                        'sc.id' => $order_info['cid'],
                    ];
                    $join = [
                        ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                        ['yx_synthetical_classroom cl', 'sc.classroom_id = cl.id', 'left'],
                    ];
//                    $field = ['cu.name,cl.province,cl.city,cl.area,cl.address,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
                    $field = ['cu.name'];
                    $alias = 'sc';
                    $table = 'synthetical_course';
                    $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

                }
                $time_table_array[$k]['name'] = $info_course['name'];
//                $time_table_array[$k]['province'] = $info_course['province'];
//                $time_table_array[$k]['city'] = $info_course['city'];
//                $time_table_array[$k]['area'] = $info_course['area'];
//                $time_table_array[$k]['address'] = $info_course['address'];
//                $time_table_array[$k]['longitude'] = $info_course['longitude'];
//                $time_table_array[$k]['latitude'] = $info_course['latitude'];
            }
        }
        return $time_table_array;
    }

    public static function getaaa()
    {
        $week_day = [
            'week' => '一',
            'day' => '',
        ];
        $sw_kc = [
            'xqj' => '',   //表示星期几
            'skjc' => '', //时间纵向排列距离
            'skcd' => '', //课时长度
            'kcmc' => '', //名称
            'bg' => '',  //背景颜色
        ];
        $xw_kc = [
            'xqj' => '',   //表示星期几
            'skjc' => '', //时间纵向排列距离
            'skcd' => '', //课时长度
            'kcmc' => '', //名称
            'bg' => '',  //背景颜色
        ];
        $ws_kc = [
            'xqj' => '',   //表示星期几
            'skjc' => '', //时间纵向排列距离
            'skcd' => '', //课时长度
            'kcmc' => '', //名称
            'bg' => '',  //背景颜色
        ];

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

    //获取某周数据处理（本周上课信息）
    public static function getTimeCourse($array)
    {
        $new_array = [];
        $array_length = count($array);
        if ($array_length != 0) {
            $num = 0;
            foreach ($array as $k => $v) {
                $colour = self::getRandomColour();
                $num++;
                if ($k + 1 <= $array_length - 1) {
                    if ($v['time_slot'] != ($array[$k + 1]['time_slot'] - 1)) {

                        $new_array[] = [
                            'xqj' => date('d', $v['day']),
                            'time' => $v['day'],
                            'course_id' => $v['course_id'],
                            'skjc' => $v['time_slot'] - $num + 1,
                            'type' => $v['type'],
                            'order_id' => $v['order_id'],
                            'kcmc' => $v['name'],
                            'status' => $v['cou_status'],
                            'skcd' => $num,
                            'bg' => $colour
                        ];
                        $num = 0;
                    }
                } else {
                    $new_array[] = [
                        'xqj' => date('d', $v['day']),
                        'time' => $v['day'],
                        'course_id' => $v['course_id'],
                        'skjc' => $v['time_slot'] - $num + 1,
                        'type' => $v['type'],
                        'order_id' => $v['order_id'],
                        'kcmc' => $v['name'],
                        'skcd' => $num,
                        'bg' => $colour
                    ];
                    $num = 0;
                }
            }
            return $new_array;
        } else {
            return [];
        }
    }

    public static function getRandomColour()
    {
        $array = ['#7fff8e', '#2ad39b', '#ffd030', '#fec422', '#ff8181', '#ff6666', '#5ac4ff', '#7076ff', '#5ac4ff', '#7076ff'];
        shuffle($array);
        return $array[0];
    }

}