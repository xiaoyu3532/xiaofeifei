<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 15:10
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v2\LmportStudent;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\CourseMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;
use app\lib\exception\UpdateMissException;
use think\Db;

class Order extends BaseController
{
    //获取订单列表
    public static function getjgOrderList($page = 1, $pageSize = 4, $order_num = '', $student_name = '', $course_name = '', $time_data = '', $mem_id = '', $course_id = '', $status = '', $course_type = '', $phone = '', $order_source = '', $leave_type = '', $is_del = 1)
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $mem_id = ['in', $mem_ids];
            }
            $where = [
                'o.is_del' => 1,
                's.is_del' => 1,
//                'u.is_del' => 1,
//                'u.type' => 1,
                'c.is_del' => 1,
                'o.mem_id' => $mem_id,
            ];


            if ((isset($time_data) && !empty($time_data))) {
                $start_time = $time_data[0] / 1000;
                $end_time = $time_data[1] / 1000;
                $where['o.create_time'] = ['between', [$start_time, $end_time]];
            }

            (isset($course_name) && !empty($course_name)) && $where['o.course_name'] = ['like', '%' . $course_name . '%']; //课程名
            (isset($order_num) && !empty($order_num)) && $where['o.order_num'] = ['like', '%' . trim($order_num) . '%']; //课程名查询和订单号
            (isset($student_name) && !empty($student_name)) && $where['s.student_name'] = ['like', '%' . $student_name . '%']; //学生名查询
            (isset($course_id) && !empty($course_id[0])) && $where['c.category_id'] = $course_id[0]; //分类
            (isset($phone) && !empty($phone)) && $where['s.phone'] = $phone; //手机号与学生名
            (isset($status) && !empty($status)) && $where['o.status'] = $status; //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
            (isset($course_type) && !empty($course_type)) && $where['o.course_type'] = $course_type; //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
            (isset($order_source) && !empty($order_source)) && $where['o.order_source'] = $order_source; //订单来源
            (isset($leave_type) && !empty($leave_type)) && $where['o.leave_type'] = $leave_type; //是否请假
            $join = [
                ['yx_zht_course c', 'o.course_id = c.id', 'left'],  //课程
                ['yx_teacher te', 'c.teacher_id = te.id', 'left'],  //老师 要修改
                ['yx_lmport_student_member s', 'o.student_member_id =s.id ', 'left'],  //学生信息
                ['yx_member m', 'o.mem_id =m.uid ', 'left'],  //机构信息
                ['yx_zht_arrange_course zac', 'o.arrange_course_id =zac.id ', 'left'],  //机构信息
                ['yx_classroom cl', 'zac.day_classroom_id =cl.id ', 'left'],  //机构信息
            ];
            $alias = 'o';
            $table = 'zht_order';
            $order_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.*,c.course_img,te.teacher_nickname,cl.classroom_name,c.course_start_time,c.course_end_time,m.cname,s.student_name,s.student_identifier,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone', $page, $pageSize);

            if ($order_data) {

                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'o.id');
                foreach ($order_data as $k => $v) {
                    //求家长信息 yx_user_student
                    $where_user = [
                        'us.user_id' => $v['user_id'],
                        'us.student_id' => $v['student_id'],
                        'us.is_del' => 1,
                        'u.is_del' => 1
                    ];
                    $join = [
                        ['yx_user u', 'us.user_id = u.id', 'left'],  //用户
                    ];
                    $alias = 'us';
                    $user_data = Crud::getRelationData('user_student', $type = 1, $where_user, $join, $alias, '', $field = 'us.id,us.relation,u.name,u.phone,u.user_identifier', $page, $pageSize);
                    $lmport_student_memberr_data = Crud::getData('lmport_student_member', $type = 1, ['id' => $v['student_member_id'], 'is_del' => 1], 'phone');

                    if ($user_data) {
                        $order_data[$k]['user_name'] = $user_data['name'];
                        $order_data[$k]['relation'] = $user_data['relation'];
                        $order_data[$k]['phone'] = $lmport_student_memberr_data['phone'];
                        $order_data[$k]['user_identifier'] = $user_data['user_identifier'];
                    } else {
                        $order_data[$k]['user_name'] = '-';
                        $order_data[$k]['relation'] = '-';
                        $order_data[$k]['phone'] = '-';
                        $order_data[$k]['user_identifier'] = '-';
                    }
                    //求已消耗课时
                    $consume_class_hour = Crud::getData('zht_course_hour_record', 1, ['id' => $v['course_hour_record_id'], 'is_del' => 1], '*');
                    if ($v['course_type'] == 1) {
                        if ($v['course_num'] > $consume_class_hour['cancelled_num']) {
                            $order_data[$k]['consume_class_hour'] = 0;
                        } else {
                            $last_consume_class_hour = $consume_class_hour['cancelled_num'] - $v['course_num'];
                            $order_data[$k]['consume_class_hour'] = $last_consume_class_hour;
                        }

                    } else {
                        if ($v['course_num'] > $consume_class_hour['cancelled_num']) {
                            $order_data[$k]['consume_class_hour'] = $consume_class_hour['cancelled_num'];
                        } else {
                            $order_data[$k]['consume_class_hour'] = $v['course_num'];
                        }

                    }

                    $order_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                    if (empty($v['classroom_name'])) {
                        $order_data[$k]['classroom_name'] = '-';
                    }
                    if (empty($v['teacher_nickname'])) {
                        $order_data[$k]['teacher_nickname'] = '-';
                    }
                    $order_data[$k]['course_time'] = date('Y-m-d', $v['course_start_time']) . '-' . date('Y-m-d', $v['course_end_time']);
                    $order_data[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
                    $order_data[$k]['course_ids'] = unserialize($v['course_ids']);
                    $order_data[$k]['student_id'] = [$v['student_id']];
                    $order_data[$k]['student_member_id'] = [$v['student_member_id']];
                    $order_data[$k]['order_source'] = (string)$v['order_source'];
                    $order_data[$k]['course_type'] = (string)$v['course_type'];

                    if ($v['course_type'] == 1) { //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                        $order_data[$k]['course_type_name'] = '体验课程';
                    } elseif ($v['course_type'] == 2) {
                        $order_data[$k]['course_type_name'] = '普通课程';
                    } elseif ($v['course_type'] == 3) {
                        $order_data[$k]['course_type_name'] = '活动课程';
                    } elseif ($v['course_type'] == 4) {
                        $order_data[$k]['course_type_name'] = '试听课';
                    } elseif ($v['course_type'] == 5) {
                        $order_data[$k]['course_type_name'] = '赠送课';
                    }
                    if ($v['order_source'] == 1) { //1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                        $order_data[$k]['order_source_name'] = '线下活动';
                    } elseif ($v['order_source'] == 2) {
                        $order_data[$k]['order_source_name'] = '转介绍';
                    } elseif ($v['order_source'] == 3) {
                        $order_data[$k]['order_source_name'] = '自主上门';
                    } elseif ($v['order_source'] == 4) {
                        $order_data[$k]['order_source_name'] = '网络平台';
                    } elseif ($v['order_source'] == 5) {
                        $order_data[$k]['order_source_name'] = '其他渠道';
                    }


                    if ($v['status'] == 1) { // 1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                        $order_data[$k]['status_name'] = '待付款';
                    } elseif ($v['status'] == 2) {
                        $order_data[$k]['status_name'] = '待排课';
                    } elseif ($v['status'] == 3) {
                        $order_data[$k]['status_name'] = '上课中';
                    } elseif ($v['status'] == 4) {
                        $order_data[$k]['status_name'] = '已毕业';
                    } elseif ($v['status'] == 5) {
                        $order_data[$k]['status_name'] = '已休学';
                    } elseif ($v['status'] == 6) {
                        $order_data[$k]['status_name'] = '已退款';
                    } elseif ($v['status'] == 7) {
                        $order_data[$k]['status_name'] = '已取消';
                    }
                }

                $info_data = [
                    'info' => $order_data,
                    'num' => $num,
                    'pageSzie' => (int)$pageSize,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取学生个人订单
    public static function getjgOrderLmport($student_id, $mem_id = '', $page = 1)
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }

        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            //查年学生订单
            $order_where = [
                'c.is_del' => 1,
//                'c.type' => 1,
                'o.is_del' => 1,
                'o.student_id' => $student_id,
                'o.mem_id' => $mem_id,
                'o.status' => ['in', [2, 2, 3, 4, 5]],  //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
            ];
            $join = [
                ['yx_lmport_student_member s', 'o.student_id =s.id ', 'left'],  //学生
                ['yx_zht_course c', 'o.course_id =c.id ', 'left'],  //课程详情
            ];
            $alias = 'o';
            $table = 'zht_order';
            $order_data = Crud::getRelationData($table, $type = 2, $order_where, $join, $alias, $order = 'o.create_time desc', $field = 'o.*,c.course_img,s.customer_type', $page, 3);
            if ($order_data) {
                foreach ($order_data as $k => $v) {
                    if ($v['course_type'] == 1) {
                        $order_data[$k]['course_type_name'] = '试听课';
                    } elseif ($v['course_type'] == 2) {
                        $order_data[$k]['course_type_name'] = '普通课';
                    }
                    //1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
                    if ($v['order_source'] == 1) {
                        $order_data[$k]['customer_type_name'] = '线下活动';
                    } elseif ($v['order_source'] == 2) {
                        $order_data[$k]['customer_type_name'] = '转介绍';
                    } elseif ($v['order_source'] == 3) {
                        $order_data[$k]['customer_type_name'] = '自主上门';
                    } elseif ($v['order_source'] == 4) {
                        $order_data[$k]['customer_type_name'] = '网络平台';
                    } elseif ($v['order_source'] == 5) {
                        $order_data[$k]['customer_type_name'] = '其他渠道';
                    }

                    //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
                    if ($v['status'] == 1) {
                        $order_data[$k]['order_status'] = '待付款';
                    } elseif ($v['status'] == 2) {
                        $order_data[$k]['order_status'] = '待排课';
                    } elseif ($v['status'] == 3) {
                        $order_data[$k]['order_status'] = '上课中';
                    } elseif ($v['status'] == 4) {
                        $order_data[$k]['order_status'] = '已毕业';
                    } elseif ($v['status'] == 5) {
                        $order_data[$k]['order_status'] = '已休学';
                    } elseif ($v['status'] == 6) {
                        $order_data[$k]['order_status'] = '已退款';
                    } elseif ($v['status'] == 7) {
                        $order_data[$k]['order_status'] = '已取消';
                    }
                }
                $num = Crud::getCountSel($table, $order_where, $join, $alias, $field = 'o.id');
                $info_data = [
                    'info' => $order_data,
                    'pageSize' => 3,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //修改小订单状态
    public static function setjgOrderStatus($order_id, $status)
    {
        $where = [
            'id' => $order_id,
        ];
        $upData = [
            'status' => $status
        ];
        $table = 'zht_order';
        $cname_data = Crud::setUpdate($table, $where, $upData);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除小订单状态
    public static function deljgOrder($order_id, $type)
    {
        $where = [
            'id' => ['in', $order_id],
        ];
        $upData = [
            'is_del' => $type
        ];
        $table = 'zht_order';
        $cname_data = Crud::setUpdate($table, $where, $upData);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new UpdateMissException();
        }

    }

    //添加订单 学生ID 订单来源 课程来源 课程ID 课程数ID
    public static function addjgOrder()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            $where = [
                'id' => $data['course_ids'][2],
                'is_del' => 1
            ];
            $course_data = Crud::getData('zht_course', 1, $where, '*');
            if (!$course_data) {
                throw new AddMissException();
            }
//            yx_zht_course_num
            $course_num = Crud::getData('zht_course_num', 1, ['id' => $data['course_ids'][3], 'is_del' => 1], 'course_section_price,course_section_num,surplus_num,enroll_num');
            if (!$course_num) {
                throw new AddMissException();
            }
            $stock = $course_num['surplus_num'] - $course_num['enroll_num'];
            if ($stock <= 0) {
                return jsonResponse('3000', '库存不足请加库存');
            }

            //计算折扣价
            if ($course_data['discount_start_time'] <= time() && $course_data['discount_end_time'] >= time()) {
                $price = $course_num['course_section_price'] * ($course_data['discount'] / 10);
                $original_price = $course_num['course_section_price'];
            } else {
                $price = $course_num['course_section_price'];
                $original_price = $course_num['course_section_price'];
            }
            if ($price <= 0) {
                $price = 0;
                $order_num_status = 2;
                $order_status = 2;
            } else {
                $order_num_status = 1;
                $order_status = 1;
            }

            //获取学生信息
            $student_data = Crud::getData('lmport_student_member', 1, ['id' => $data['student_member_id'][0], 'is_del' => 1], 'id,student_id');

            //添加大订单
            $order_num = time() . mt_rand(999, 9999);
            $order_num_data = [
                'order_num' => $order_num,
                'add_order_type' => 2,//1小程序创建，2后台创建
//                'user_id' => $student_data['user_id'],
//                'course_type' => $data['course_type'], //课程类型1普通课，2社区课
                'status' => $order_num_status,
                'order_source' => $data['order_source'], //订单来源，1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                'price' => $course_num['course_section_price'],
                'student_id' => $student_data['student_id'],
                'course_id' => $data['course_ids'][2],
//                'course_num' => $course_num['course_section_num'],
                'course_num_id' => $data['course_ids'][3], //课时ID
            ];
            $order_id = time() . mt_rand(999, 9999);
            $order_data = [
//                'user_id' => $order_id,
                'add_order_type' => 2,//1小程序创建，2后台创建
                'order_id' => $order_id,
                'order_num' => $order_num,
                'mem_id' => $data['mem_id'],
                'course_id' => $data['course_ids'][2], //课程ID
                'course_num_id' => $data['course_ids'][3], //课时ID
                'course_name' => $course_data['course_name'],
//                'course_num_id' => $data['course_num_id'], //学生报名信息ID
                'course_num' => $course_num['course_section_num'],
                'surplus_course_num' => $course_num['course_section_num'],//剩余课时
                'course_start_time' => $course_data['course_start_time'],
                'course_end_time' => $course_data['course_end_time'],
                'course_type' => $course_data['course_type'], //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                'discount' => $course_data['discount'], //折扣
//                'attend_class_type' => $course_data['attend_class_type'],  //1试听课程,2赠送课
                'order_source' => $data['order_source'], //订单来源，1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                'student_id' => $student_data['student_id'], //学生信息
                'student_member_id' => $student_data['id'], //学生信息
                'price' => $price,
                'status' => $order_status,
                'original_price' => $original_price,
                'course_ids' => serialize($data['course_ids']),
            ];
            Db::startTrans();
            $order_num_info = Crud::setAdd('zht_order_num', $order_num_data);
            if (!$order_num_info) {
                Db::rollback();
                throw new AddMissException();
            }

            //yx_zht_course_hour_record
            $hour_record = [
                'course_id' => $data['course_ids'][2],
                'student_id' => $student_data['student_id'],
                'student_member_id' => $student_data['id'], //学生信息
                'mem_id' => $data['mem_id'],
                'stay_row_num' => $course_num['course_section_num'],  //待排课时
            ];
            $course_hour_record_id = Crud::setAdd('zht_course_hour_record', $hour_record, 2);
            if (!$course_hour_record_id) {
                Db::rollback();
                throw new AddMissException();
            }
            $order_data['course_hour_record_id'] = $course_hour_record_id;
            $order_info = Crud::setAdd('zht_order', $order_data);
            if (!$order_info) {
                Db::rollback();
                throw new AddMissException();
            }
            //不需要付款
            if ($order_status == 2) {
                //添加报名人数
                $course_where = [
                    'id' => $data['course_ids'][2]
                ];
                $course_inc = Crud::setIncs('zht_course', $course_where, 'enroll_num');
                if (!$course_inc) {
                    Db::rollback();
                    throw new AddMissException();
                }

                //加课包销量
                $course_num_where = [
                    'id' => $data['course_ids'][3]
                ];
                $course_num = Crud::setIncs('zht_course_num', $course_num_where, 'enroll_num');
                if (!$course_num) {
                    Db::rollback();
                    throw new AddMissException();
                }
            }


            //验证此用户是否是本机构的
            $student_status = LmportStudent::isStudentStatus($data['student_member_id'][0]);
//            dump($course_data['course_type']);
//            dump($student_status);
//            dump($order_status);
            if ($student_status['student_status'] != 3 && $order_status == 2 && ($course_data['course_type'] = ['in', [2, 3]])) {  //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                $updateStudentStatus = LmportStudent::updateStudentStatus($data['student_member_id'][0]);
                if (!$updateStudentStatus) {
                    Db::rollback();
                    throw new UpdateMissException();
                }
            }


            Db::commit();
            if (isset($data['type']) && $data['type'] == 2) {
                return $order_info;
            }
            return jsonResponseSuccess($order_info);


        } else {
            throw new ISUserMissException();
        }

    }

    //修改订单信息
    public static function editjgOrder()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $data = input();
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            $where = [
                'id' => $data['course_ids'][2],
                'is_del' => 1
            ];
            $course_data = Crud::getData('zht_course', 1, $where, '*');
            if (!$course_data) {
                throw new AddMissException();
            }
//            yx_zht_course_num
            $course_num = Crud::getData('zht_course_num', 1, ['id' => $data['course_ids'][3], 'is_del' => 1], 'course_section_price,course_section_num');
            if (!$course_num) {
                throw new AddMissException();
            }

            //计算折扣价
            if ($course_data['discount_start_time'] <= time() && $course_data['discount_end_time'] >= time()) {
                $price = $course_num['course_section_price'] * ($course_data['discount'] / 10);
                $original_price = $course_num['course_section_price'];
            } else {
                $price = $course_num['course_section_price'];
                $original_price = $course_num['course_section_price'];
            }
//            yx_lmport_student
            $student_data = Crud::getData('lmport_student_member', 1, ['id' => $data['student_member_id'][0], 'is_del' => 1], '*');

            //yx_user_student
//            $user_student = Crud::getData('lmport_student', 1, ['id' => $data['student_id'][0], 'is_del' => 1], '*');
//            dump($student_data);exit;

            $order_data = [
                'mem_id' => $data['mem_id'],
                'course_id' => $data['course_ids'][2], //课程ID
                'course_name' => $course_data['course_name'],
//                'course_num_id' => $data['course_num_id'], //学生报名信息ID
                'course_num' => $course_num['course_section_num'],
                'course_start_time' => $course_data['course_start_time'],
                'course_end_time' => $course_data['course_end_time'],
//                'course_type' => $data['course_type'], //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
//                'attend_class_type' => $course_data['attend_class_type'],  //1试听课程,2赠送课
                'order_source' => $data['order_source'], //订单来源，1线下活动，2转介绍（介绍人），3自主上门，4网络平台，5其他渠道
                'student_id' => $student_data['student_id'], //学生信息
                'student_member_id' => $student_data['id'], //学生信息
                'price' => $price,
                'original_price' => $original_price,
                'course_ids' => serialize($data['course_ids']),

            ];
            $order_info = Crud::setUpdate('zht_order', ['order_id' => $data['order_id']], $order_data);
            if (!$order_info) {
                throw new UpdateMissException();
            }
            return jsonResponseSuccess($order_info);
        } else {
            throw new ISUserMissException();
        }

    }


    //获取课程详情
    public static function getCourseDetails($data)
    {  //yx_seckill_course 秒杀课程  yx_experience_course 体验课程 yx_community_course 活动课程

        //判断是哪种类型的课进详情页
        if ($data['cou_status'] == 4) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'],
                ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
            ];
            $field = ['sc.id cou_id,cl.name clname, sc.start_age,sc.end_age,cu.wheel_img,sc.img,sc.mid,cu.name,cu.details,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,m.address,m.logo,cl.longitude,cl.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
            $alias = 'sc';
            $table = 'seckill_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('秒杀课程进入详情');
            //这是秒杀课程进入详情
        } elseif ($data['cou_status'] == 2) {
            $where1 = [
                'ec.is_del' => 1,
                'ec.type' => 1,
                'ec.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'ec.mid = m.uid', 'left'],
                ['yx_classroom cl', 'ec.classroom_id = cl.id', 'left'],
            ];
            $field = ['ec.id cou_id,cl.name clname,ec.start_age,ec.end_age,cu.wheel_img,ec.img,ec.mid,cu.name,cu.details,ec.title,ec.present_price,ec.original_price,ec.enroll_num,ec.surplus_num,ec.start_time,ec.end_time,ec.c_num,m.cname,m.address,m.logo,cl.longitude,cl.latitude,m.course_num,m.logo,m.give_type,m.uid,m.ismember,m.balance,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
            $alias = 'ec';
            $table = 'experience_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('体验课程进入详情');
            //这是体验课程进入详情
        } elseif ($data['cou_status'] == 3) {
            $where1 = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $data['cid'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],//社区课目
                ['yx_community_classroom cl', 'cc.classroom_id = cl.id', 'left'], //社区教室
            ];
            $field = 'cc.id cou_id,cl.name clname,cc.img,cu.wheel_img,cu.name,cu.details,cc.community_id,cc.title,cc.start_age,cc.end_age,cc.original_price,cc.enroll_num,cc.start_time,cc.end_time,cc.c_num,cc.present_price,cc.surplus_num,cc.by_time,cl.latitude,cl.longitude,cl.province,cl.city,cl.area,cl.address';
            $alias = 'cc';
            $table = 'community_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('活动课程进入详情');
            //这是活动课程进入详情
        } elseif ($data['cou_status'] == 1) {

            $where1 = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $data['cid'],
            ];
            $join = [
                ['yx_member m', 'c.mid = m.uid', 'left'],
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
            ];
            $field = ['c.id cou_id,cl.name clname,cu.wheel_img,c.img,c.mid,cu.name,cu.details,c.title,c.aid,c.present_price,c.start_age,c.end_age,c.original_price,c.enroll_num,c.surplus_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,c.longitude,c.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
//            dump('普通课程');
            $alias = 'c';
            $table = 'course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        } elseif ($data['cou_status'] == 5) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'], //机构
                ['yx_synthetical_classroom scm', 'sc.classroom_id = scm.id', 'left'], //机构
            ];
            $field = ['sc.id cou_id,scm.name clname,sc.start_age,sc.end_age,sc.syntheticalcn_id,cu.wheel_img,cu.details,sc.img,sc.mid,cu.name,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,scm.address,m.logo,scm.longitude,scm.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,scm.province,scm.city,scm.area'];
            $alias = 'sc';
            $table = 'synthetical_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

//            dump('活动课程进入详情');
            //这是活动课程进入详情
        }
        if (!$info_course) {
            throw new CourseMissException();
        } else {

            //将年龄ID字符串变为数组
            $info_course['age_name'] = $info_course['start_age'] . '~' . $info_course['end_age'];
            $info_course['status'] = $data['status'];

            //判读是否是序列化字符串
            if (!empty($info_course['wheel_img'])) {
                $info_course['wheel_img'] = get_take_img($info_course['wheel_img']);
            }
            if (!empty($info_course['mwheel_img'])) {
                $mwheel_img = get_take_img($info_course['mwheel_img']);
                $info_course['mwheel_img'] = $mwheel_img[0];
            }
            if (!empty($info_course['logo'])) {
                $info_course['logo'] = get_take_img($info_course['logo']);
            }
            if (!empty($info_course['img'])) {
                $info_course['img'] = get_take_img($info_course['img']);
            }
            return $info_course;
        }


    }


    //订单上传合同
    public static function uploadjgOrderContract()
    {
        $data = input();
        $contract = Crud::setUpdate('zht_order', ['order_id' => $data['order_id']], ['contract' => $data['contract']]);
        if ($contract) {
            return jsonResponseSuccess($contract);
        } else {
            throw new AddMissException();
        }
    }

}