<?php

namespace app\xcx\controller\v3;

use app\lib\exception\NothingMissException;
use EasyWeChat\Factory;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use app\xcx\controller\v2\Base;

/**
 * 活动
 */
class Activity extends Base
{
    protected $exceptTicket = ["getRecommendActivityLists", "getActivityLists", "getActivityInfo"];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 创建拼团订单
     * @Author: asus
     * @Date: 2020/5/29
     * @Time: 15:46
     * @Interface createCollageOrder
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createCollageOrder()
    {
        $data = input('post.');
        if (empty($data['share_id']) || $data['share_id'] == $this->userId) {
            $data['share_id'] = 0;
        }

        if (!empty($data['obj'])) {
            foreach ($data['obj'] as $k => $v) {
                if (empty($v)) {
                    return returnResponse('1001', '请填写信息');
                }
                if ($k == "phone") {
                    //验证手机号
                    if (strlen($v) != 11) {
                        return returnResponse('1001', '请输入正确的手机号');
                    }

                    $chars = "/^1(3|4|5|6|7|8|9)\d{9}$/";
                    if (!preg_match($chars, $v)) {
                        return returnResponse('1001', '手机号输入有误');
                    }
                }
                $data[$k] = $v;
            }
        }

        unset($data['obj']);

        if (empty($data['id'])) {
            return returnResponse('1001', '请选择活动');
        }

        if (empty($data['student_id'])) {
            return returnResponse('1001', '请选择学员');
        }
        $where['id'] = $data['id'];
        $where['status'] = 2;
        $where['activity_start_time'] = ['<=', time()];
        $where['activity_end_time'] = ['>=', time()];
        $activity = Crud::getData('zht_activity', 1, $where, "activity_type,activity_course_category,activity_limit,activity_iscourse,course_id,course_num_id,mem_id,activity_price,activity_enroll_num");
        if (empty($activity)) {
            return returnResponse('1002', '活动不存在');
        }

        //判断是否限量
        if ($activity['activity_limit'] != 0) {
            //查询订单
            $activityCount = Crud::getData('zht_activity_order', 2, ['user_id' => $this->userId, 'activity_id' => $data['id'], 'status' => 3], 'id');
            if (count($activityCount) >= $activity['activity_limit']) {
                return returnResponse('1002', '活动限量无法参与多次');
            }
        }
        //判断学生绑定关系是否存在
        $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $data['student_id'], 'is_del' => 1], 'id');
        if (empty($student)) {
            return returnResponse('1002', '学员选择错误');
        }

        //计算支付价格
        //获取阶梯价格
        $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $data['id'], 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
        array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $activity['activity_price']]);
        //halt($ladderPrice);
        $price = 0;
        foreach ($ladderPrice as $values) {
            if ($activity['activity_enroll_num'] >= $values['ladder_num']) {
                $price = $values['ladder_price'];
                break;
            } else {
                continue;
            }
        }

        $data['activity_id'] = $data['id'];
        unset($data['id']);
        $no = time() . rand(999, 9999);
        $data['activity_order_num'] = $no;
        $data['mem_id'] = $activity['mem_id'];
        $data['iscourse_type'] = $activity['activity_iscourse'];
        $data['user_id'] = $this->userId;
        $data['status'] = $price == 0 ? 1 : 2;
        $data['price'] = 1;
        $data['price'] = $price;
        $data['course_category'] = $activity['activity_course_category'];
        $data['course_id'] = $activity['course_id'];
        $data['course_num_id'] = $activity['course_num_id'];
        $data['activity_type'] = $activity['activity_type'];
        Db::startTrans();
        try {
            if ($price == 0) {
                $studentName = Crud::getData("lmport_student", 1, ['id' => $data['student_id']], 'student_name,sex,birthday,id_card,province,city,area,address,community,school,class,province_num,city_num,area_num');
                if (empty($studentName)) {
                    throw new Exception('学员信息异常');
                }
                //添加 学生-机构绑定表
                $studentMember = Crud::getData('lmport_student_member', 1, ['mem_id' => $activity['mem_id'], 'student_id' => $data['student_id']], "id,student_status");
                if (empty($studentMember)) {
                    $data = [
                        'mem_id' => $activity['mem_id'],
                        'student_id' => $data['student_id'],
                        'student_name' => $studentName['student_name'],
                        'student_type' => 3,
                        'customer_type' => 4,
                        'student_status' => 3,
                        'sex' => $studentName['sex'],
                        'birthday' => $studentName['birthday'],
                        'id_card' => $studentName['id_card'],
                        'return_visit_id' => time() . rand(999, 9999),
                        'student_identifier' => time() . rand(999, 9999),
                        'phone' => $studentName['phone'],
                        'province' => $studentName['province'],
                        'city' => $studentName['city'],
                        'area' => $studentName['area'],
                        'address' => $studentName['address'],
                        'community' => $studentName['community'],
                        'school' => $studentName['school'],
                        'class' => $studentName['class'],
                        'province_num' => $studentName['province_num'],
                        'city_num' => $studentName['city_num'],
                        'area_num' => $studentName['area_num']
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
                //添加活动参与记录
                $add = Crud::setAdd('zht_activity_avatar', ['activity_id' => $data['activity_id'], 'avatar' => $this->userInfo['img'], 'price' => 0, 'type' => $activity['activity_type'], 'nickname' => $this->userInfo['name']]);
                if (empty($add)) {
                    throw new Exception('添加活动参与记录失败');
                }

                $table = $activity['activity_course_category'] == 1 ? "zht_online_course" : "zht_course";
                //活动关联线下课程时
                if ($activity['activity_course_category'] == 2) {
                    //添加课时记录表
                    if ($activity['activity_course_category'] == 2) {
                        $courseNum = Crud::getData("zht_course_num", 1, ['id' => $activity['course_num_id']], 'course_section_num');
                        if (empty($courseNum)) {
                            throw new Exception('课时异常');
                        }
                        $courseHourRecord = [
                            'course_id' => $activity['course_id'],
                            'student_id' => $data['student_id'],
                            'student_member_id' => $import,
                            'mem_id' => $activity['mem_id'],
                            'sum_class_hour' => $courseNum['course_section_num'],
                            'stay_row_num' => $courseNum['course_section_num'],
                        ];
                        $courseHour = Crud::setAdd('zht_course_hour_record', $courseHourRecord, 2);
                        if (empty($courseHour)) {
                            throw new Exception('添加学生上课记录表失败');
                        }
                    }
                }

                //创建活动订单
                $data['student_member_id'] = $import;
                $createOrder = Crud::setAdd('zht_activity_order', $data, 2);
                if (empty($createOrder)) {
                    throw new Exception('创建活动订单失败');
                }
                //创建主订单
                $orderNo = time() . mt_rand(999, 9999);
                $datas = [
                    'order_num' => $orderNo,
                    'user_id' => $this->userId,
                    'status' => 2,
                    'order_source' => 4,
                    'price' => $price,
                    'paytype' => 2,
                    'student_id' => $data['student_id'],
                    'student_member_id' => $import
                ];

                $orderNumId = Crud::setAdd('zht_order_num', $datas, 2);
                if (empty($orderNumId)) {
                    throw new Exception('创建主订单失败');
                }
                //绑定课程 获取课程详情
                $course = Crud::getData($table, 1, ['id' => $activity['course_id']], 'course_name,course_start_time,course_end_time,course_type');

                //创建子订单
                $orderData = [
                    'order_id' => time() . mt_rand(999, 9999),
                    'order_num' => $orderNo,
                    'mem_id' => $activity['mem_id'],
                    'course_id' => $activity['course_id'],
                    'course_name' => empty($course) ? '' : $course['course_name'],
                    'activity_order_id' => $createOrder,
                    'activity_id' => $data['activity_id'],
                    'course_hour_record_id' => empty($courseHour) ? '' : $courseHour,
                    'course_num' => empty($courseNum['course_section_num']) ? 0 : $courseNum['course_section_num'],
                    'surplus_course_num' => empty($courseNum['course_section_num']) ? 0 : $courseNum['course_section_num'],
                    'course_num_id' => $activity['course_num_id'],
                    'course_start_time' => empty($course['course_start_time']) ? 0 : $course['course_start_time'],
                    'course_end_time' => empty($course['course_end_time']) ? 0 : $course['course_end_time'],
                    'course_category' => $activity['activity_course_category'],
                    'course_type' => $course['course_type'],
                    'order_source' => 4,
                    'status' => 2,
                    'discount_price' => $activity['activity_price'] - $price,
                    'price' => $price,
                    'original_price' => $activity['activity_price'],
                    'user_id' => $this->userId,
                    'student_id' => $data['student_id'],
                    'student_member_id' => $import
                ];
                $order = Crud::setAdd('zht_order', $orderData, 2);
                if (empty($order)) {
                    throw new Exception('创建子订单失败');
                }


                // name as parent_name,phone,qq,email
                $user['parent_name'] = $this->userInfo['name'];
                $user['phone'] = $this->userInfo['phone'];
                $user['qq'] = $this->userInfo['email'];
                $user['email'] = $this->userInfo['email'];
                $user['student_id'] = $data['student_id'];
                $user['mem_id'] = $activity['mem_id'];
                $user['student_member_id'] = $import;
                //判断机构与家长是否绑定
                $parent = Crud::getData('parent', 1, ['student_member_id' => $import, 'mem_id' => $activity['mem_id'], 'is_del' => 1], 'id');
                if (empty($parent)) {
                    //添加家长表
                    $addParent = Crud::setAdd('parent', $user);
                    if (empty($addParent)) {
                        throw new Exception('添加家长表失败');
                    }
                }

                //判断家长信息与学生关联
                $studentParentRelation = Crud::getData('student_parent_relation', 1, ['student_id' => $data['student_id'], 'mem_id' => $activity['mem_id'], 'parent_id' => $this->userId, 'is_del' => 1], 'id');
                if (empty($studentParentRelation)) {
                    //添加关系表
                    $addRelation = Crud::setAdd('student_parent_relation', ['student_id' => $data['student_id'], 'mem_id' => $activity['mem_id'], 'student_member_id' => $import, 'parent_id' => $this->userId]);
                    if (empty($addRelation)) {
                        throw new Exception('添加家长信息与学生关联失败');
                    }
                }

                $wxPayParam['activityId'] = $createOrder;
                $wxPayParam['status'] = 1;
            }
            if ($price > 0) {
                //创建活动订单
                $createOrder = Crud::setAdd('zht_activity_order', $data, 2);
                if (empty($createOrder)) {
                    throw new Exception('创建活动订单失败');
                }
                //获取支付参数
                $config = config('wxpayConfig');
                $app = Factory::payment($config);

                $openid = $this->userInfo['x_openid'];

                $result = $app->order->unify([
                    'body' => '购买',
                    'out_trade_no' => $no,
                    'total_fee' => $price * 100,//
                    'notify_url' => "https://zht.insooner.com/xcx/v3/collageCallback",
                    'trade_type' => 'JSAPI',
                    'openid' => $openid
                ]);

                if ($result['return_code'] !== 'SUCCESS') {
                    throw new Exception('获取支付参数失败');
                }

                //获取支付配置信息
                $jssdk = $app->jssdk;
                $wxPayParam = $jssdk->sdkConfig($result['prepay_id'], false);
                $wxPayParam['activityId'] = $createOrder;
                $wxPayParam['status'] = 2;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }

        return returnResponse('1000', '', $wxPayParam);
    }

    /**
     * @Notes: 活动订单详情
     * @Author: asus
     * @Date: 2020/6/2
     * @Time: 9:13
     * @Interface getActivityOrderInfo
     * @return false|string
     */
    public function getActivityOrderInfo()
    {
        if (!$orderId = input('post.id')) {
            return returnResponse('1001', '请选择订单');
        }
        $page = input("post.page/d", 1);
        $pageSize = input("post.page_size/d", 16);
        $where = [
            'ao.id' => $orderId,
            'ao.user_id' => $this->userId,
            'ao.is_del' => 1,
        ];
        $join = [
            ['yx_zht_activity a', 'ao.activity_id = a.id', 'left']
        ];
        $field = "a.status as activity_status,a.activity_num,a.id,a.activity_img,ao.activity_id,a.activity_title,a.activity_start_time,a.activity_end_time,a.activity_enroll_num,a.activity_price,ao.price,ao.status,ao.activity_type,ao.settlement_price,ao.return_price,ao.iscourse_type,ao.course_category,ao.course_id,ao.course_num_id,ao.id as activityOrderId";
        $result = Crud::getRelationData('zht_activity_order', 1, $where, $join, 'ao', '', $field);
        //halt(Db::name("zht_activity_order")->getLastSql());
        if (empty($result)) {
            return returnResponse('1002', '订单异常');
        }
        $time = $result['activity_end_time'] - time();
        if ($result['activity_type'] == 1) {
            //拼团
            //已完成
            $activityAvatar = Crud::getData('zht_activity_avatar', 2, ['activity_id' => $result['id'], 'is_del' => 1], 'avatar,nickname,price', 'id DESC', $page, $pageSize);
            $result['avatar'] = $activityAvatar;
            if ($result['status'] == 1) {
                //进行中
                $result['time'] = $time;
                //拼团 获取拼团价格阶梯
                $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $result['id'], 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
                //halt(Db::name("zht_ladder_price")->getLastSql());
                array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $result['activity_price']]);
                foreach ($ladderPrice as $k => $v) {
                    if ($result['activity_enroll_num'] >= $v['ladder_num']) {
                        if ($k == 0) {
                            $tips['need'] = bcsub($result['activity_num'], $result['activity_enroll_num']);
                            $tips['fill'] = $result['activity_num'];
                            $tips['price'] = $v['ladder_price'];
                            $tips['reg'] = 0;
                        } else {
                            $tips['need'] = bcsub($ladderPrice[$k - 1]['ladder_num'], $result['activity_enroll_num']);
                            $tips['fill'] = $ladderPrice[$k - 1]['ladder_num'];
                            $tips['price'] = $ladderPrice[$k - 1]['ladder_price'];
                            $tips['reg'] = bcsub($v['ladder_price'], $ladderPrice[$k - 1]['ladder_price'], 2);
                        }
                        break;
                    }
                    continue;
                }
                array_pop($ladderPrice);
                $result['ladderPrice'] = array_reverse($ladderPrice);
                $result['tips'] = $tips;

            } elseif ($result['status'] == 3) {
                //是否关联课程
                if ($result['iscourse_type'] == 1) {
                    $table = $result['course_category'] == 1 ? "zht_online_course" : "zht_course";
                    $join = [
                        ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
                    ];
                    $field = "zc.course_name,zc.course_img,zy.name";
                    $course = Crud::getRelationData($table, 1, ['zc.id' => $result['course_id'], 'zc.is_del' => 1], $join, 'zc', '', $field);
                    //Db::name($table)->getLastSql()
                    if (empty($course)) {
                        return returnResponse('1002', '关联课程异常');
                    }
                    //获取课时
                    $courseNum = Crud::getData('zht_course_num', 1, ['id' => $result['course_num_id']], 'course_section_num');
                    if (empty($courseNum)) {
                        return returnResponse('1002', '课时异常');
                    }
                    $course['course_num'] = $courseNum['course_section_num'];
                    $result['course'] = $course;
                    //获取课程订单id
                    $orderId = Crud::getData('zht_order', 1, ['activity_order_id' => $result['activityOrderId'], 'user_id' => $this->userId], 'id');
                    if (empty($orderId)) {
                        return returnResponse('1002', '课程订单异常');
                    }
                    $result['orderId'] = $orderId['id'];
                } else {
                    $result['text'] = "平台或活动机构将在48小时内联系您告知领取方式,请留意电话短信";
                }
            }
        }
        return returnResponse('1000', '', $result);
    }

}