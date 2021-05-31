<?php

namespace app\xcx\controller\v3;

use AlibabaCloud\Cr\Cr;
use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use EasyWeChat\Factory;
use app\xcx\controller\v2\Base;


/**
 * 异步回调
 */
class Gateway extends Base
{
    protected $exceptTicket = ["orderCallback", "collageCallback", 'collagerefund', 'fictitious', "updateOnlineCoursePrice", "updateOfflineCoursePrice", 'updateOfflineCourseStatus','vipback'];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 订单异步回调
     * @Author: asus
     * @Date: 2020/5/25
     * @Time: 18:28
     * @Interface orderCallback
     * @throws Exception
     */
    public function orderCallback()
    {

        //微信小程序异步校验\
        // $result = file_get_contents('php://input', 'r');
        // file_put_contents('wxpay.txt', $result . PHP_EOL);
        // exit();
        // 获取微信配置
        $config = config('wxpayConfig');
        $app = Factory::payment($config);

        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 判断订单是否存在 $message['out_trade_no']

            $orderNum = Crud::getData("zht_order_num", 1, ['order_num' => $message['out_trade_no'], 'status' => 1], 'id,student_id,status');
            if (empty($orderNum)) {
                $fail('订单不存在');
            }
            if ($orderNum['status'] == 2) {
                return true;
            }
            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                if ($message['result_code'] === 'SUCCESS') { // 成功
                    //$orderNum = Crud::getData("zht_order_num", 1, ['order_num' => $message['out_trade_no'], 'status' => 1], 'id,student_id');
                    $order = Crud::getDataunpage("zht_order", 2, ['order_num' => $message['out_trade_no'], 'status' => 1], 'id,course_id,mem_id,course_num,course_num_id,course_category,user_id,student_id');
                    if (count($order) == 0) {
                        return $fail('订单异常');
                    }
                    $student = Crud::getData('lmport_student', 1, ['id' => $orderNum['student_id']], 'student_name,phone,birthday,sex,id_card,province,city,area,address,community,school,class,province_num,city_num,area_num');

                    Db::startTrans();
                    try {
                        //添加 学生-机构绑定表
                        foreach ($order as $item) {
                            //判断学生是否绑定该机构
                            $studentMember = Crud::getData('lmport_student_member', 1, ['mem_id' => $item['mem_id'], 'student_id' => $orderNum['student_id']], "id,student_status");
                            if (empty($studentMember)) {
                                $data = [
                                    'mem_id' => $item['mem_id'],
                                    'student_id' => $orderNum['student_id'],
                                    'student_name' => $student['student_name'],
                                    'student_type' => 3,
                                    'customer_type' => 4,
                                    'student_status' => 3,
                                    'sex' => $student['sex'],
                                    'birthday' => $student['birthday'],
                                    'id_card' => $student['id_card'],
                                    'return_visit_id' => time() . rand(999, 9999),
                                    'student_identifier' => time() . rand(999, 9999),
                                    'phone' => $student['phone'],
                                    'province' => $student['province'],
                                    'city' => $student['city'],
                                    'area' => $student['area'],
                                    'address' => $student['address'],
                                    'community' => $student['community'],
                                    'school' => $student['school'],
                                    'class' => $student['class'],
                                    'province_num' => $student['province_num'],
                                    'city_num' => $student['city_num'],
                                    'area_num' => $student['area_num']
                                ];
                                $import = Crud::setAdd('lmport_student_member', $data, 2);
                                if (empty($import)) {
                                    throw new Exception('学员绑定机构失败');
                                }
                            } else {
                                if ($studentMember['student_status'] != 3) {
                                    //修改学员状态
                                    $updateStudentStatus = Crud::setUpdate('lmport_student_member', ['id' => $studentMember['id']], ['update_time' => time(), 'student_status' => 3]);
                                    if (empty($updateStudentStatus)) {
                                        throw new Exception('修改学员状态失败');
                                    }
                                }
                                $import = $studentMember['id'];
                            }

                            if ($item['course_category'] == 2) {
                                // 添加学生上课记录表
                                $courseHourRecord = [
                                    'course_id' => $item['course_id'],
                                    'student_id' => $orderNum['student_id'],
                                    'student_member_id' => $import,
                                    'mem_id' => $item['mem_id'],
                                    'sum_class_hour' => $item['course_num'],
                                    'stay_row_num' => $item['course_num'],
                                ];
                                $courseHour = Crud::setAdd('zht_course_hour_record', $courseHourRecord, 2);
                                if (empty($courseHour)) {
                                    throw new Exception('添加学生上课记录表失败');
                                }
                            }
                            $courseHour = empty($courseHour) ? '' : $courseHour;
                            // //更改课时报名人数
                            // $courseNum = Crud::getData('zht_course_num', 1, ['id' => $item['course_num_id']], 'enroll_num,surplus_num');
                            // if (empty($courseNum)) {
                            //     throw new Exception('课时不存在');
                            // }
                            // $updateCourseNum = Crud::setUpdate('zht_course_num', ['id' => $item['course_num_id']], ['enroll_num' => $courseNum['enroll_num'] + 1]);
                            // if (empty($updateCourseNum)) {
                            //     throw new Exception('修改报名人数失败');
                            // }
                            //更改子订单状态及course_hour_record_id,student_member_id
                            $update = Crud::setUpdate('zht_order', ['id' => $item['id'], 'status' => 1], ['update_time' => time(), 'course_hour_record_id' => $courseHour, 'status' => 2, 'student_member_id' => $import]);
                            if (empty($update)) {
                                throw new Exception('更新子订单失败');
                            }
                            $user = Crud::getData('user', 1, ['id' => $item['user_id'], 'is_del' => 1], "name as parent_name,phone,qq,email");
                            if (empty($user)) {
                                throw new Exception('用户信息异常');
                            }
                            $user['student_id'] = $item['student_id'];
                            $user['mem_id'] = $item['mem_id'];
                            $user['student_member_id'] = $import;
                            //判断机构与家长是否绑定
                            $parent = Crud::getData('parent', 1, ['student_member_id' => $import, 'mem_id' => $item['mem_id'], 'is_del' => 1], 'id');
                            if (empty($parent)) {
                                //添加家长表
                                $addParent = Crud::setAdd('parent', $user);
                                if (empty($addParent)) {
                                    throw new Exception('添加家长表失败');
                                }
                            }

                            //判断家长信息与学生关联
                            $studentParentRelation = Crud::getData('student_parent_relation', 1, ['student_id' => $item['student_id'], 'mem_id' => $item['mem_id'], 'parent_id' => $item['user_id'], 'is_del' => 1], 'id');
                            if (empty($studentParentRelation)) {
                                //添加关系表
                                $addRelation = Crud::setAdd('student_parent_relation', ['student_id' => $item['student_id'], 'mem_id' => $item['mem_id'], 'student_member_id' => $import, 'parent_id' => $item['user_id']]);
                                if (empty($addRelation)) {
                                    throw new Exception('添加家长信息与学生关联失败');
                                }
                            }
                        }
                        //更改主订单状态
                        $updateOrder = Crud::setUpdate('zht_order_num', ['id' => $orderNum['id'], 'status' => 1], ['update_time' => time(), 'status' => 2, 'student_member_id' => $import]);
                        if (empty($updateOrder)) {
                            throw new Exception('更新主订单失败');
                        }
                        Db::commit();
                        return true;
                        //回复微信数据
                    } catch (\Exception  $e) {
                        Db::rollback();
                        //回复微信数据
                        $path = "order/" . date('Y-m-d');
                        if (!file_exists($path)) {
                            mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                        }
                        $msg['order_num'] = $message['out_trade_no'];
                        $msg['msg'] = $e->getMessage();
                        $msg['time'] = time();
                        file_put_contents($path . '/' . date('a') . '.txt', var_export($msg, true) . PHP_EOL, FILE_APPEND);

                        return $fail('通信失败，请稍后再通知我' . $e->getMessage());
                    }

                } else if (array_get($message, 'result_code') === 'FAIL') { // 失败记录日志
                    $path = "order/" . date('Y-m-d');
                    if (!file_exists($path)) {
                        mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                    }
                    $message['time'] = time();
                    file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                }
            } else {
                $path = "order/" . date('Y-m-d');
                if (!file_exists($path)) {
                    mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                }
                $message['time'] = time();
                file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                return $fail('通信失败，请稍后再通知我1');
            }

            return true;
        });

        $response->send();
        exit;
    }

    /**
     * @Notes: 拼团异步回调
     * @Author: asus
     * @Date: 2020/5/29
     * @Time: 15:20
     * @Interface collageCallback
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function collageCallback()
    {
        //微信小程序异步校验\
        $result = file_get_contents('php://input', 'r');
        file_put_contents('wxpay1.txt', $result . PHP_EOL);
        //exit();
        // 获取微信配置
        $config = config('wxpayConfig');
        $app = Factory::payment($config);

        $response = $app->handlePaidNotify(function ($message, $fail) {
            //halt($message);
            // 判断订单是否存在 $message['out_trade_no']
            $activityOrder = Crud::getData("zht_activity_order", 1, ['activity_order_num' => $message['out_trade_no'], 'status' => 2], 'iscourse_type,id,mem_id,share_id,student_id,price,user_id,activity_id,activity_type,status,course_id,course_num_id,course_category');
            if (empty($activityOrder)) {
                $fail('订单不存在');
            }
            if ($activityOrder['status'] != 2) {
                return true;
            }
            $user = Crud::getData('user', 1, ['id' => $activityOrder['user_id'], 'is_del' => 1], 'name,img,phone,qq,email');
            if (empty($user)) {
                $fail('用户异常');
            }
            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                if ($message['result_code'] === 'SUCCESS') { // 成功
                    $student = Crud::getData('lmport_student', 1, ['id' => $activityOrder['student_id']], 'student_name,phone,birthday,sex,id_card,province,city,area,address,community,school,class,province_num,city_num,area_num');
                    $activity = Crud::getData('zht_activity', 1, ['id' => $activityOrder['activity_id']], 'activity_type,activity_num,activity_enroll_num,activity_price,deal_sum_user_num,deal_sum_price,basics_sum_commission,exclusive_sum_commission,activity_isdistribution,activity_distribution,income_price');
                    if (empty($student) || empty($activity)) {
                        $fail('异常');
                    }
                    Db::startTrans();
                    try {
                        //添加 学生-机构绑定表
                        $studentMember = Crud::getData('lmport_student_member', 1, ['mem_id' => $activityOrder['mem_id'], 'student_id' => $activityOrder['student_id']], "id,student_status");
                        if (empty($studentMember)) {
                            $data = [
                                'mem_id' => $activityOrder['mem_id'],
                                'student_id' => $activityOrder['student_id'],
                                'student_name' => $student['student_name'],
                                'student_type' => 3,
                                'customer_type' => 4,
                                'student_status' => 3,
                                'sex' => $student['sex'],
                                'birthday' => $student['birthday'],
                                'id_card' => $student['id_card'],
                                'return_visit_id' => time() . rand(999, 9999),
                                'student_identifier' => time() . rand(999, 9999),
                                'phone' => $student['phone'],
                                'province' => $student['province'],
                                'city' => $student['city'],
                                'area' => $student['area'],
                                'address' => $student['address'],
                                'community' => $student['community'],
                                'school' => $student['school'],
                                'class' => $student['class'],
                                'province_num' => $student['province_num'],
                                'city_num' => $student['city_num'],
                                'area_num' => $student['area_num']
                            ];
                            $import = Crud::setAdd('lmport_student_member', $data, 2);
                            if (empty($import)) {
                                throw new Exception('学员绑定机构失败');
                            }
                        } else {
                            if ($studentMember['student_status'] != 3) {
                                //修改学员状态
                                $updateStudentStatus = Crud::setUpdate('lmport_student_member', ['id' => $studentMember['id']], ['update_time' => time(), 'student_status' => 3]);
                                if (empty($updateStudentStatus)) {
                                    throw new Exception('修改学员状态失败');
                                }
                            }
                            $import = $studentMember['id'];
                        }

                        //修改活动订单状态
                        $status = 1;
                        $updateActivityOrder = Crud::setUpdate('zht_activity_order', ['activity_order_num' => $message['out_trade_no'], 'status' => 2], ['time' => time(), 'student_member_id' => $import, 'status' => $status]);
                        if (empty($updateActivityOrder)) {
                            throw new Exception('修改活动订单状态失败');
                        }
                        //添加活动参与记录
                        $add = Crud::setAdd('zht_activity_avatar', ['activity_id' => $activityOrder['activity_id'], 'avatar' => $user['img'], 'price' => $activityOrder['price'], 'type' => $activityOrder['activity_type'], 'nickname' => $user['name']]);
                        if (empty($add)) {
                            throw new Exception('添加活动参与记录失败');
                        }
                        $table = $activityOrder['course_category'] == 1 ? "zht_online_course" : "zht_course";
                        //活动关联线下课程时
                        if ($activityOrder['course_category'] != 3) {
                            if ($activityOrder['course_category'] == 2) {
                                //添加课时记录表
                                $courseNum = Crud::getData("zht_course_num", 1, ['id' => $activityOrder['course_num_id']], 'course_section_num');
                                if (empty($courseNum)) {
                                    throw new Exception('课时异常');
                                }
                                $courseHourRecord = [
                                    'course_id' => $activityOrder['course_id'],
                                    'student_id' => $activityOrder['student_id'],
                                    'student_member_id' => $import,
                                    'mem_id' => $activityOrder['mem_id'],
                                    'sum_class_hour' => $courseNum['course_section_num'],
                                    'stay_row_num' => $courseNum['course_section_num'],
                                ];
                                $courseHour = Crud::setAdd('zht_course_hour_record', $courseHourRecord, 2);
                                if (empty($courseHour)) {
                                    throw new Exception('添加学生上课记录表失败');
                                }
                            }


                            //创建主订单
                            $orderNo = time() . mt_rand(999, 9999);
                            $data = [
                                'order_num' => $orderNo,
                                'user_id' => $activityOrder['user_id'],
                                'status' => 2,
                                'order_source' => 4,
                                'price' => $activityOrder['price'],
                                'paytype' => 2,
                                'student_id' => $activityOrder['student_id'],
                                'student_member_id' => $import
                            ];
                            $orderNumId = Crud::setAdd('zht_order_num', $data, 2);
                            if (empty($orderNumId)) {
                                throw new Exception('创建主订单失败');
                            }
                            //绑定课程 获取课程详情
                            $course = Crud::getData($table, 1, ['id' => $activityOrder['course_id']], 'course_name,course_start_time,course_end_time,course_type');

                            //创建子订单
                            $orderData = [
                                'order_id' => time() . mt_rand(999, 9999),
                                'order_num' => $orderNo,
                                'mem_id' => $activityOrder['mem_id'],
                                'course_id' => $activityOrder['course_id'],
                                'course_name' => empty($course) ? '' : $course['course_name'],
                                'activity_order_id' => $activityOrder['id'],
                                'activity_id' => $activityOrder['activity_id'],
                                'course_hour_record_id' => empty($courseHour) ? '' : $courseHour,
                                'course_num' => empty($courseNum['course_section_num']) ? 0 : $courseNum['course_section_num'],
                                'surplus_course_num' => empty($courseNum['course_section_num']) ? 0 : $courseNum['course_section_num'],
                                'course_num_id' => $activityOrder['course_num_id'],
                                'course_start_time' => empty($course['course_start_time']) ? 0 : $course['course_start_time'],
                                'course_end_time' => empty($course['course_end_time']) ? 0 : $course['course_end_time'],
                                'course_category' => $activityOrder['course_category'],
                                'course_type' => $course['course_type'],
                                'order_source' => 4,
                                'status' => 2,
                                'discount_price' => $activity['activity_price'] - $activityOrder['price'],
                                'price' => $activityOrder['price'],
                                'original_price' => $activity['activity_price'],
                                'user_id' => $activityOrder['user_id'],
                                'student_id' => $activityOrder['student_id'],
                                'student_member_id' => $import
                            ];
                            $order = Crud::setAdd('zht_order', $orderData, 2);
                            if (empty($order)) {
                                throw new Exception('创建子订单失败');
                            }
                            //判断机构与家长是否绑定
                            $parent = Crud::getData('parent', 1, ['student_member_id' => $import, 'mem_id' => $activityOrder['mem_id'], 'is_del' => 1], 'id');
                            if (empty($parent)) {
                                //添加家长表
                                $users['parent_name'] = $user['name'];
                                $users['qq'] = $user['qq'];
                                $users['email'] = $user['email'];
                                $users['phone'] = $user['phone'];
                                $users['student_id'] = $activityOrder['student_id'];
                                $users['mem_id'] = $activityOrder['mem_id'];
                                $users['student_member_id'] = $import;
                                $addParent = Crud::setAdd('parent', $users);
                                if (empty($addParent)) {
                                    throw new Exception('添加家长表失败');
                                }
                            }

                            //判断家长信息与学生关联
                            $studentParentRelation = Crud::getData('student_parent_relation', 1, ['student_id' => $activityOrder['student_id'], 'mem_id' => $activityOrder['mem_id'], 'parent_id' => $activityOrder['user_id'], 'is_del' => 1], 'id');
                            if (empty($studentParentRelation)) {
                                //添加关系表
                                $addRelation = Crud::setAdd('student_parent_relation', ['student_id' => $activityOrder['student_id'], 'mem_id' => $activityOrder['mem_id'], 'student_member_id' => $import, 'parent_id' => $activityOrder['user_id']]);
                                if (empty($addRelation)) {
                                    throw new Exception('添加家长信息与学生关联失败');
                                }
                            }

                        }

                        $activityData = [
                            "deal_sum_user_num" => $activity['deal_sum_user_num'] + 1,
                            'deal_sum_price' => $activity['deal_sum_price'] + $activityOrder['price'],
                            'basics_sum_commission' => $activity['basics_sum_commission'],
                            'exclusive_sum_commission' => $activity['exclusive_sum_commission']
                        ];

                        //分销 分享用户id必须大于0
                        if ($activityOrder['share_id'] > 0) {
                            $userData = [];
                            //获取用户与机构分销关系
                            $distributionRelation = Crud::getData("zht_distribution_relation", 1, ['user_id' => $activityOrder['share_id'], 'mem_id' => $activityOrder["mem_id"], 'is_del' => 1], 'id,exclusive_single_commission,visit_num,exclusive_type,deal_sum_user_num,deal_sum_price,basics_sum_commission,exclusive_sum_commission,sum_commission');
                            if (empty($distributionRelation)) {
                                throw new Exception('用户机构数据异常');
                            }
                            $activityData["basics_sum_commission"] = $activity['activity_isdistribution'] == 1 ? $activityData["basics_sum_commission"] + $activity['activity_distribution'] : $activityData["basics_sum_commission"];
                            $activityData['exclusive_sum_commission'] = $distributionRelation['exclusive_type'] == 2 ? $activityData['exclusive_sum_commission'] + $distributionRelation['exclusive_single_commission'] : $activityData['exclusive_sum_commission'];

                            //添加分享记录
                            $disData = [
                                'share_id' => $activityOrder['share_id'],
                                'shared_id' => $activityOrder['user_id'],
                                'course_id' => $activityOrder['course_id'],
                                'activity_order_id' => $activityOrder['id'],
                                "activity_id" => $activityOrder["activity_id"],
                                'activity_distribution' => $activity['activity_distribution'],
                                "mem_id" => $activityOrder['mem_id'],
                                "deal_price" => $activityOrder['price'],
                                'basics_commission' => $activity['activity_isdistribution'] == 1 ? $activity['activity_distribution'] : 0,
                                "exclusive_commission" => $distributionRelation['exclusive_single_commission'],
                                "commission_type" => $distributionRelation['exclusive_type']

                            ];
                            $addDistribution = Crud::setAdd("zht_distribution", $disData);
                            if (empty($addDistribution)) {
                                throw new Exception('添加分销记录失败');
                            }

                            //修改活动人员佣金表
                            $userDis = Crud::getData("zht_activity_user_distribution", 1, ['user_id' => $activityOrder['share_id'], 'mem_id' => $activityOrder['mem_id'], 'activity_id' => $activityOrder['activity_id'], 'is_del' => 1], 'id,deal_sum_user_num,deal_sum_price,basics_sum_commission,exclusive_sum_commission,sum_commission');
                            if (empty($userDis)) {
                                throw new Exception('用户活动数据异常');
                            }
                            $userDisData = [
                                'deal_sum_user_num' => $userDis['deal_sum_user_num'] + 1,
                                'deal_sum_price' => $userDis['deal_sum_price'] + $activityOrder['price'],
                                'basics_sum_commission' => $activity['activity_isdistribution'] == 1 ? $activity['activity_distribution'] + $userDis['basics_sum_commission'] : $userDis['basics_sum_commission'],
                                'exclusive_sum_commission' => $distributionRelation['exclusive_type'] == 2 ? $userDis['exclusive_sum_commission'] + $distributionRelation['exclusive_single_commission'] : $userDis['exclusive_sum_commission'],
                            ];
                            $userDisData['sum_commission'] = $userDisData['basics_sum_commission'] + $userDisData['exclusive_sum_commission'];
                            $userDisData['update_time'] = time();
                            $updateUserDis = Crud::setUpdate("zht_activity_user_distribution", ['id' => $userDis['id']], $userDisData);
                            if (empty($updateUserDis)) {
                                throw new Exception('修改用户活动数据失败');
                            }
                            //修改分销员与机构表
                            $distributionRelationData = [
                                'deal_sum_user_num' => $distributionRelation['deal_sum_user_num'] + 1,
                                'deal_sum_price' => $distributionRelation['deal_sum_price'] + $activityOrder['price'],
                                'basics_sum_commission' => $activity['activity_isdistribution'] == 1 ? $activity['activity_distribution'] + $distributionRelation['basics_sum_commission'] : $distributionRelation['basics_sum_commission'],
                                'exclusive_sum_commission' => $distributionRelation['exclusive_type'] == 2 ? $distributionRelation['exclusive_sum_commission'] + $distributionRelation['exclusive_single_commission'] : $distributionRelation['exclusive_sum_commission'],
                            ];
                            $distributionRelationData['sum_commission'] = $distributionRelationData['basics_sum_commission'] + $distributionRelationData['exclusive_sum_commission'];
                            $distributionRelationData['update_time'] = time();
                            $updateDisRelation = Crud::setUpdate("zht_distribution_relation", ['id' => $distributionRelation['id']], $distributionRelationData);
                            if (empty($updateDisRelation)) {
                                throw new Exception('修改用户机构分销记录失败');
                            }

                            //修改用户总佣金 user表
                            $user = Crud::getData('user', 1, ['id' => $activityOrder['share_id'], 'is_del' => 1], "commission");
                            if (empty($user)) {
                                throw new Exception('分享用户数据异常');
                            }
                            $userData['commission'] = $distributionRelation['exclusive_type'] == 2 ? $distributionRelation['exclusive_single_commission'] + $user['commission'] : $user['commission'];
                            $userData['commission'] = $activity['activity_isdistribution'] == 1 ? $userData['commission'] + $activity['activity_distribution'] : $userData['commission'];
                            $userData['update_time'] = time();
                            $updateUser = Crud::setUpdate('user', ['id' => $activityOrder['share_id']], $userData);
                            if (empty($updateUser)) {
                                throw new Exception('修改分享用户佣金失败');
                            }
                        }

                        //修改活动数据(参与人数，成交量，累计佣金等)
                        $num = $activity['activity_enroll_num'] + 1;
                        $activityData['activity_enroll_num'] = $num;
                        if ($num == $activity['activity_num']) {
                            $activityData['status'] = 3;
                        }
                        $activityData['settlement_price'] = $activityOrder['price'];
                        $activityData['update_time'] = time();
                        $updateActivity = Crud::setUpdate('zht_activity', ['id' => $activityOrder['activity_id']], $activityData);
                        if (empty($updateActivity)) {
                            throw new Exception('修改活动参与数失败');
                        }

                        Db::commit();
                        return true;
                        //回复微信数据
                    } catch (\Exception  $e) {
                        Db::rollback();
                        //回复微信数据
                        $path = "collage/" . date('Y-m-d');
                        if (!file_exists($path)) {
                            mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                        }
                        $msg['activity_order_num'] = $message['out_trade_no'];
                        $msg['msg'] = $e->getMessage();
                        $msg['time'] = time();
                        file_put_contents($path . '/' . date('a') . '.txt', var_export($msg, true) . PHP_EOL, FILE_APPEND);

                        return $fail('通信失败，请稍后再通知我' . $e->getMessage());
                    }

                } else if (array_get($message, 'result_code') === 'FAIL') { // 失败记录日志
                    $path = "collage/" . date('Y-m-d');
                    if (!file_exists($path)) {
                        mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                    }
                    $message['time'] = time();
                    file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                }
            } else {

                $path = "collage/" . date('Y-m-d');
                if (!file_exists($path)) {
                    mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                }
                $message['time'] = time();
                file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                return $fail('通信失败，请稍后再通知我1');
            }

            return true;
        });

        $response->send();
        exit;
    }

    /**
     * @Notes: 活动退款
     * @Author: asus
     * @Date: 2020/6/9
     * @Time: 14:09
     * @Interface collagerefund
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function collagerefund()
    {
        set_time_limit(0);
        $where['status'] = ['=', 1];
        $where['activity_type'] = ['=', 1];
        $where['price'] = ['>', 0];
        $activityOrders = Crud::getData('zht_activity_order', 2, $where, 'id,activity_id,user_id,price', '', 1, 100);
        if (count($activityOrders) > 0) {
            foreach ($activityOrders as $item) {
                $activity = Crud::getData("zht_activity", 1, ['id' => $item['activity_id'], 'status' => 3], 'id,settlement_price,refund_price,income_price');
                if (!empty($activity)) {
                    Db::startTrans();
                    try {
                        $user = Crud::getData('user', 1, ['id' => $item['user_id']], "x_openid");
                        if (empty($user)) {
                            throw new Exception('用户数据异常');
                        }
                        //修改活动退款金额 实际收益
                        $money = bcsub($item['price'] - $activity['settlement_price'], 2);
                        $refund = bcadd($money, $activity['refund_price'], 2);
                        $incomePrice = bcadd($activity['settlement_price'], $activity['income_price'], 2);

                        $updateActivity = Crud::setUpdate("zht_activity", ['id' => $activity['id']], ['update_time' => time(), 'refund_price' => $refund, 'income_price' => $incomePrice]);
                        if (empty($updateActivity)) {
                            throw new Exception('修改活动数据失败');
                        }
                        //修改活动订单数据
                        $updateActivityOrder = Crud::setUpdate('zht_activity_order', ['id' => $item['id']], ['update_time' => time(), 'status' => 3, 'settlement_price' => $activity['settlement_price'], 'return_price' => $money]);
                        if (empty($updateActivityOrder)) {
                            throw new Exception('修改订单数据失败');
                        }

                        //提现
                        // if ($money < 0.3) {
                        //     $config = config('wxpayConfig');
                        //     $app = Factory::payment($config);
                        //     $toBalance = $app->transfer->toBalance([
                        //         'partner_trade_no' => time() . rand(999, 9999), // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
                        //         'openid' => $user['x_openid'],
                        //         'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
                        //         're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
                        //         'amount' => 30,//$money * 100, // 企业付款金额，单位为分
                        //         'desc' => '退款', // 企业付款操作说明信息。必填
                        //     ]);
                        //     if ($toBalance['return_code'] != "SUCCESS" || $toBalance['result_code'] != "SUCCESS") {
                        //         throw new Exception($toBalance['err_code_des']);
                        //     }
                        // }

                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        //记录日志
                        $path = "refund/" . date('Y-m-d');
                        if (!file_exists($path)) {
                            mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                        }
                        $data['msg'] = $e->getMessage();
                        $data['activity_id'] = $item['activity_id'];
                        $data['id'] = $item['id'];
                        $data['time'] = time();
                        file_put_contents($path . '/' . date('a') . '.txt', var_export($data, true) . PHP_EOL, FILE_APPEND);
                    }
                }
                continue;
            }
            return returnResponse('1000', '成功');
        } else {
            return returnResponse('1000', '暂无');
        }
    }

    /**
     * @Notes: 活动虚拟销量
     * @Author: asus
     * @Date: 2020/6/10
     * @Time: 13:38
     * @Interface fictitious
     */
    public function fictitious()
    {
        set_time_limit(0);
        $activityLists = Crud::getDataunpage('zht_activity', 2, ['status' => 2, 'activity_isfictitious_user' => 1, 'is_del' => 1], 'id,activity_fictitious_user_time,activity_fictitious_time,activity_fictitious_user_time_num,activity_start_time,activity_num');

        if (count($activityLists) > 0) {
            foreach ($activityLists as $item) {
                $time = empty($item['activity_fictitious_time']) ? $item['activity_start_time'] : $item['activity_fictitious_time'];
                $sub = bcsub(time(), $time);
                $hours = bcdiv($sub, 3600, 2);
                $count = Crud::getData('zht_fictitious', 1, ['is_del' => 1], 'count(*) as count');
                if ($hours >= $item['activity_fictitious_user_time']) {
                    for ($i = 0; $i < $item['activity_fictitious_user_time_num']; $i++) {
                        $activity = Crud::getData('zht_activity', 1, ['id' => $item['id'], 'status' => 2], 'id,activity_type,activity_fictitious_user_num,activity_enroll_num,activity_num,activity_price');
                        if (!empty($activity) && $activity['activity_enroll_num'] < $activity['activity_num']) {
                            Db::startTrans();
                            try {
                                //修改活动数据 添加头像昵称价格
                                $status = ($activity['activity_enroll_num'] + 1) == $activity['activity_num'] ? 3 : 2;
                                $updateActivity = Crud::setUpdate('zht_activity', ['id' => $item['id'], 'status' => 2], ['update_time' => time(), 'activity_fictitious_time' => time(), 'activity_fictitious_user_num' => $activity['activity_fictitious_user_num'] + 1, 'status' => $status, 'activity_enroll_num' => $activity['activity_enroll_num'] + 1]);
                                if (empty($updateActivity)) {
                                    throw new Exception('更改活动失败');
                                }

                                $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $activity['id'], 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
                                array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $activity['activity_price']]);
                                foreach ($ladderPrice as $k => $v) {
                                    if ($activity['activity_enroll_num'] >= $v['ladder_num']) {
                                        $price = $v['ladder_price'];
                                        break;
                                    }
                                    continue;
                                }

                                $id = mt_rand(1, $count['count'] - 1);
                                $ava = Crud::getData("zht_fictitious", 1, ['id' => $id], "avatar,nickname");
                                $ava['price'] = $price;
                                $ava['activity_id'] = $activity['id'];
                                $ava['type'] = $activity['activity_type'];
                                $add = Crud::setAdd("zht_activity_avatar", $ava);
                                if (empty($add)) {
                                    throw new Exception('添加头像记录失败');
                                }
                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                                //记录日志
                                $path = "fictitious/" . date('Y-m-d');
                                if (!file_exists($path)) {
                                    mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                                }
                                $data['msg'] = $e->getMessage();
                                $data['activity_id'] = $item['id'];
                                $data['data'] = $activity;
                                $data['time'] = time();
                                file_put_contents($path . '/' . date('a') . '.txt', var_export($data, true) . PHP_EOL, FILE_APPEND);

                            }
                        }

                    }

                }

            }
            return returnResponse('1000', '成功');

        }
        return returnResponse('1000', '暂无');
    }

    /**
     * @Notes: 修改线上价格问题
     * @Author: asus
     * @Date: 2020/7/1
     * @Time: 11:10
     * @Interface updateOnlineCoursePrice
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function updateOnlineCoursePrice()
    {
        set_time_limit(0);
        $count = Crud::getData("zht_online_course", 1, ['is_del' => 1, 'type' => 1, 'activity_type' => 2], "count(id) as count");
        if ($count['count'] > 0) {
            $counts = ceil(bcdiv($count['count'], 100, 2));
            for ($i = 0; $i < $counts; $i++) {
                $result = Crud::getData("zht_online_course", 2, ['is_del' => 1, 'type' => 1, 'activity_type' => 2], 'id,original_price,present_price,discount,discount_start_time,discount_end_time', 'id ASC', $i + 1, 100);
                if (count($result) > 0) {
                    foreach ($result as $item) {
                        if ($item['discount_start_time'] > time() || $item['discount_end_time'] < time()) {
                            $price = $item['original_price'];
                        } else {
                            $dis = bcdiv($item['discount'], 10, 2);
                            $price = bcmul($dis, $item["original_price"], 2);
                        }
                        if ($price != $item['present_price']) {
                            Crud::setUpdate("zht_online_course", ['id' => $item['id']], ['update_time' => time(), 'present_price' => $price]);
                        }
                    }
                }
            }
        }
    }


    /** 修改线下价格问题
     * @Notes:
     * @Author: asus
     * @Date: 2020/7/1
     * @Time: 11:10
     * @Interface updateOfflineCoursePrice
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function updateOfflineCoursePrice()
    {
        set_time_limit(0);
        $count = Crud::getData("zht_course", 1, ['is_del' => 1, 'type' => 1, 'activity_type' => 2], "count(id) as count");
        if ($count['count'] > 0) {
            $counts = ceil(bcdiv($count['count'], 100, 2));
            for ($i = 0; $i < $counts; $i++) {
                $result = Crud::getData("zht_course", 2, ['is_del' => 1, 'type' => 1, 'activity_type' => 2], 'id,present_price,discount,discount_start_time,discount_end_time', 'id ASC', $i + 1, 100);
                if (count($result) > 0) {
                    foreach ($result as $item) {
                        $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['id'], 'is_del' => 1], "min(course_section_price) as present_price");
                        if ($item['discount_start_time'] > time() || $item['discount_end_time'] < time()) {
                            $price = $enrollNum['present_price'];
                        } else {
                            $dis = bcdiv($item['discount'], 10, 2);
                            $price = bcmul($dis, $enrollNum['present_price'], 2);
                        }
                        if ($price != $item['present_price']) {
                            Crud::setUpdate("zht_course", ['id' => $item['id']], ['update_time' => time(), 'present_price' => $price]);
                        }
                    }
                }
            }
        }
    }

    public function updateOfflineCourseStatus()
    {
        set_time_limit(0);
        $sql = "select count(id) as count from yx_zht_online_course where ((type = 1 and course_end_time < " . time() . " ) OR (type = 2 and course_start_time < " . time() . " and course_end_time > " . time() . ")) and is_del = 1 limit 1";
        $count = Db::query($sql);
        // halt(Db::name("zht_online_course")->getLastSql());
        if ($count[0]['count'] > 0) {
            $counts = ceil(bcdiv($count[0]['count'], 100, 2));
            for ($i = 0; $i < $counts; $i++) {
                $page = $i * 100;
                $sqls = "select id,type from yx_zht_online_course where ((type = 1 and course_end_time < " . time() . " ) OR (type = 2 and course_start_time < " . time() . " and course_end_time > " . time() . ")) and is_del = 1 limit $page,100";
                $res = Db::query($sqls);
                if (count($res)) {
                    foreach ($res as $item) {
                        $type = $item['type'] == 1 ? 2 : 1;
                        Crud::setUpdate('zht_online_course', ['id' => $item['id']], ['update_time' => time(), 'type' => $type]);
                    }
                }
            }
        }
    }

    public function vipBack()
    {
        //微信小程序异步校验\
        $result = file_get_contents('php://input', 'r');
        file_put_contents('vipBack.txt', $result . PHP_EOL);
        exit();
        // 获取微信配置
        $config = config('wxpayConfig');
        $app = Factory::payment($config);

        $response = $app->handlePaidNotify(function ($message, $fail) {
            $order = Crud::getData("zht_vip_order", 1, ['is_del' => 1, 'status' => 1, 'order_no' => $message['out_trade_no']], 'id,user_id,vip_type,days');
            if (empty($order)) {
                $fail('订单不存在');
            }
            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                if ($message['result_code'] === 'SUCCESS') { // 成功

                    Db::startTrans();
                    try {
                        //1修改订单状态
                        $updateStatus = Crud::setUpdate("zht_vip_order", ['id' => $order['id']], ['update_time' => time(), 'status' => 2]);
                        if (empty($updateStatus)) {
                            throw new Exception('修改订单状态失败');
                        }

                        $vip = Crud::getData("zht_vip", 1, ['is_del' => 1, 'user_id' => $order['user_id'], 'vip_type' => $order['vip_type']], 'id,expiration_time');
                        if (empty($vip)) {
                            //新增
                            $time = strtotime(date('Y-m-d H:i:s', strtotime("+" . $order['days'] . "day")));
                            $add = Crud::setAdd('zht_vip', ['user_id' => $order['user_id'], 'vip_type' => $order['vip_type'], 'expiration_time' => $time]);
                            if (empty($add)) {
                                throw new Exception('新增会员时间失败');
                            }
                        } else {
                            //修改
                            if ($vip["expiration_time"] < time()) {
                                $time = strtotime(date('Y-m-d H:i:s', strtotime("+" . $order['days'] . "day")));
                            } else {
                                $time = $vip['expiration_time'] + $order['days'] * 24 * 60 * 60;
                            }
                            $updateVip = Crud::setUpdate("zht_vip", ['id' => $vip['id']], ['update_time' => time(), 'expiration_time' => $time]);
                            if (empty($updateVip)) {
                                throw new Exception('修改会员时间失败');
                            }

                        }
                        //购买智能测评时 添加点读会员时间
                        if ($order['vip_type'] == 2) {
                            $vips = Crud::getData("zht_vip", 1, ['is_del' => 1, 'user_id' => $order['user_id'], 'vip_type' => 1], 'id,expiration_time');
                            if (empty($vips)) {
                                //新增
                                $times = strtotime(date('Y-m-d H:i:s', strtotime("+" . $order['days'] . "day")));
                                $add = Crud::setAdd('zht_vip', ['user_id' => $order['user_id'], 'vip_type' => 1, 'expiration_time' => $times]);
                                if (empty($add)) {
                                    throw new Exception('新增会员时间失败');
                                }
                            } else {
                                //修改
                                if ($vips["expiration_time"] < time()) {
                                    $times = strtotime(date('Y-m-d H:i:s', strtotime("+" . $order['days'] . "day")));
                                } else {
                                    $times = $vips['expiration_time'] + $order['days'] * 24 * 60 * 60;
                                }
                                $updateVip = Crud::setUpdate("zht_vip", ['id' => $vip['id']], ['update_time' => time(), 'expiration_time' => $times]);
                                if (empty($updateVip)) {
                                    throw new Exception('修改会员时间失败');
                                }

                            }

                        }
                        Db::commit();
                        return true;
                    } catch (\Exception $e) {
                        Db::rollback();
                        $path = "vip/" . date('Y-m-d');
                        if (!file_exists($path)) {
                            mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                        }
                        $msg['order_num'] = $message['out_trade_no'];
                        $msg['msg'] = $e->getMessage();
                        $msg['time'] = time();
                        file_put_contents($path . '/' . date('a') . '.txt', var_export($msg, true) . PHP_EOL, FILE_APPEND);

                        return $fail('通信失败，请稍后再通知我' . $e->getMessage());
                    }

                } else if (array_get($message, 'result_code') === 'FAIL') { // 失败记录日志
                    $path = "vip/" . date('Y-m-d');
                    if (!file_exists($path)) {
                        mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                    }
                    $message['time'] = time();
                    file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                }
            } else {

                $path = "vip/" . date('Y-m-d');
                if (!file_exists($path)) {
                    mkdir(iconv("utf-8", "gbk", $path), 0777, true);
                }
                $message['time'] = time();
                file_put_contents($path . '/' . date('a') . '.txt', var_export($message, true) . PHP_EOL, FILE_APPEND);

                return $fail('通信失败，请稍后再通知我');
            }

            return true;
        });

        $response->send();
        exit;
    }
}