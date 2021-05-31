<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use app\lib\exception\ReturnMissException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use Yansongda\Pay\Pay;
use EasyWeChat\Factory;

/**
 * 订单
 */
class Order extends Base
{
    protected $exceptTicket = [];

    // protected $allowTourist = ['access_token'];


    /**
     * @Notes: 创建订单并获取支付参数
     * @Author: asus
     * @Date: 2020/5/25
     * @Time: 16:08
     * @Interface createOrder
     * @return string
     */
    public function createOrder()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse("1001", '请选择课程');
        }
        $courseCategory = input('post.course_category/d', 2);
        if (!$courseNumId = input('post.course_num_id')) {
            return returnResponse("1001", '请选择课时');
        }
        if ($courseCategory == 1) {
            //线上
        } else {
            //线下
            $table = 'zht_course';
        }
        if (!$studentId = input('post.student_id/d')) {
            return returnResponse("1001", '请选择报名学员');
        }

        //判断课程是否存在
        $course = Crud::getData($table, 1, ['id' => $courseId, 'is_del' => 1, 'type' => 1], 'course_type,course_start_time,course_end_time,course_name,mem_id,discount_start_time,discount_end_time,discount');
        if (empty($course)) {
            return returnResponse('1002', '课程不存在');
        }
        if ($courseCategory == 2) {
            //判断课时
            $courseNum = Crud::getData("zht_course_num", 1, ['id' => $courseNumId, 'course_id' => $courseId, 'is_del' => 1], 'id,course_section_num,course_section_price,enroll_num,surplus_num');
            if (empty($courseNum)) {
                return returnResponse('1002', '课时不存在');
            }
            if ($courseNum['enroll_num'] >= $courseNum['surplus_num']) {
                return returnResponse('1002', '课时报名人数已满');
            }
        }


        //判断学生绑定关系是否存在
        $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
        if (empty($student)) {
            return returnResponse('1002', '学员选择错误');
        }

        //计算折扣
        if ($course['discount_start_time'] > time() || $course['discount_end_time'] < time()) {
            $discountPrice = 0;
            $price = $courseNum['course_section_price'];
            $discount = 10;
        } else {
            $discount = $course['discount'];
            $dis = bcdiv($discount, 10, 2);
            $price = bcmul($courseNum['course_section_price'], $dis, 2);
            $discountPrice = bcsub($courseNum['course_section_price'], $price, 2);
            $discount = $course['discount'];

        }
        //创建订单
        Db::startTrans();
        try {
            if ($price == 0) {
                //判断学生是否绑定该机构
                $studentMember = Crud::getData('lmport_student_member', 1, ['mem_id' => $course['mem_id'], 'student_id' => $studentId], "id,student_status");
                if (empty($studentMember)) {
                    $studentName = Crud::getData('lmport_student', 1, ['id' => $studentId], 'student_name,phone,birthday,sex,id_card,province,city,area,address,community,school,class,province_num,city_num,area_num');
                    $data = [
                        'mem_id' => $course['mem_id'],
                        'student_id' => $studentId,
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

                if ($courseCategory == 2) {
                    // 添加学生上课记录表
                    $courseHourRecord = [
                        'course_id' => $courseId,
                        'student_id' => $studentId,
                        'student_member_id' => $import,
                        'mem_id' => $course['mem_id'],
                        "sum_class_hour" => $courseNum['course_section_num'],
                        'stay_row_num' => $courseNum['course_section_num'],
                    ];
                    $courseHour = Crud::setAdd('zht_course_hour_record', $courseHourRecord, 2);
                    if (empty($courseHour)) {
                        throw new Exception('添加学生上课记录表失败');
                    }
                }

                // name as parent_name,phone,qq,email
                $user['parent_name'] = $this->userInfo['name'];
                $user['phone'] = $this->userInfo['phone'];
                $user['qq'] = $this->userInfo['email'];
                $user['email'] = $this->userInfo['email'];
                $user['student_id'] = $studentId;
                $user['mem_id'] = $course['mem_id'];
                $user['student_member_id'] = $import;
                //判断机构与家长是否绑定
                $parent = Crud::getData('parent', 1, ['student_member_id' => $import, 'mem_id' => $course['mem_id'], 'is_del' => 1], 'id');
                if (empty($parent)) {
                    //添加家长表
                    $addParent = Crud::setAdd('parent', $user);
                    if (empty($addParent)) {
                        throw new Exception('添加家长表失败');
                    }
                }

                //判断家长信息与学生关联
                $studentParentRelation = Crud::getData('student_parent_relation', 1, ['student_id' => $studentId, 'mem_id' => $course['mem_id'], 'parent_id' => $this->userId, 'is_del' => 1], 'id');
                if (empty($studentParentRelation)) {
                    //添加关系表
                    $addRelation = Crud::setAdd('student_parent_relation', ['student_id' => $studentId, 'mem_id' => $course['mem_id'], 'student_member_id' => $import, 'parent_id' => $this->userId]);
                    if (empty($addRelation)) {
                        throw new Exception('添加家长信息与学生关联失败');
                    }
                }

            }
            //创建主订单
            $orderNo = time() . mt_rand(999, 9999);
            $data = [
                'order_num' => $orderNo,
                'user_id' => $this->userId,
                'status' => $price == 0 ? 2 : 1,
                'order_source' => 4,
                'price' => $price,
                'paytype' => 2,
                'student_id' => $studentId,
                'student_member_id' => empty($import) ? '' : $import,
            ];
            $orderNumId = Crud::setAdd('zht_order_num', $data, 2);
            if (empty($orderNumId)) {
                throw new Exception('创建主订单失败');
            }

            //创建子订单
            $orderData = [
                'order_id' => time() . mt_rand(999, 9999),
                'order_num' => $orderNo,
                'mem_id' => $course['mem_id'],
                'course_id' => $courseId,
                'course_name' => $course['course_name'],
                'course_num' => $courseNum['course_section_num'],
                'course_num_id' => $courseNumId,
                'course_start_time' => $course['course_start_time'],
                'course_end_time' => $course['course_end_time'],
                'course_category' => $courseCategory,
                'course_type' => $course['course_type'],
                'order_source' => 4,
                'status' => $price == 0 ? 2 : 1,
                'discount_price' => $discountPrice,
                'price' => $price,
                'original_price' => $courseNum['course_section_price'],
                'user_id' => $this->userId,
                'student_id' => $studentId,
                'discount' => $discount,
                'student_member_id' => empty($import) ? '' : $import,
                "course_hour_record_id" => empty($courseHour) ? '' : $courseHour,

            ];
            $order = Crud::setAdd('zht_order', $orderData, 2);
            if (empty($order)) {
                throw new Exception('创建子订单失败');
            }
            //占用课时名额
            $updateNum = Crud::setUpdate('zht_course_num', ['id' => $courseNum['id']], ['update_time' => time(), 'enroll_num' => $courseNum['enroll_num'] + 1]);
            if (empty($updateNum)) {
                throw new Exception('创建订单失败');
            }

            if ($price > 0) {
                $config = config('wxpayConfig');
                $app = Factory::payment($config);
                $openid = $this->userInfo['x_openid'];
                $result = $app->order->unify([
                    'body' => '购买',
                    'out_trade_no' => $orderNo,
                    'total_fee' => $price * 100,
                    'notify_url' => "https://zht.insooner.com/xcx/v2/orderCallback",
                    'trade_type' => 'JSAPI',
                    'openid' => $openid
                ]);

                if ($result['return_code'] !== 'SUCCESS') {
                    throw new Exception('获取支付参数失败');
                }

                //获取支付配置信息
                $jssdk = $app->jssdk;
                $wxPayParam = $jssdk->sdkConfig($result['prepay_id'], false);
                $wxPayParam['status'] = 2;
            } else {
                $wxPayParam['status'] = 1;
            }
            $wxPayParam['orderId'] = $order;

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }

        return returnResponse('1000', '', $wxPayParam);
    }


    /**
     * @Notes: 获取订单列表
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 13:31
     * @Interface getOrderLists
     * @return string
     */
    public function getOrderLists()
    {

        $status = input('post.status/d', 0);
        if ($status > 0) {
            $where['zo.status'] = ["=", $status];
        }
        $where['zo.user_id'] = ["=", $this->userId];
        $where['zo.is_del'] = ["=", 1];
        $where['zo.course_id'] = ['>', 0];
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $join = [
            ['yx_member m', 'zo.mem_id = m.uid', 'left'],
        ];
        $wheres['zo.status'] = ['neq', 7];
        $field = "zo.id,zo.course_id,zo.course_category,zo.course_name,m.cname,zo.course_num,zo.price,zo.discount_price,zo.original_price,zo.status,zo.course_type";
        $list = Crud::getRelationDataAndWhere('zht_order', 2, $where, $join, 'zo', 'zo.create_time DESC', $field, $page, $pageSize, '', $wheres);
        //halt(Db::name("zht_order")->getLastSql());
        if (count($list) > 0) {
            foreach ($list as &$item) {
                if ($item['course_category'] == 1) {
                    $table = "";
                } else {
                    $table = "zht_course";
                }
                $joins = [
                    ['yx_zht_category zcy', 'zc.category_id = zcy.id', 'left']
                ];
                $course = Crud::getRelationData($table, 1, ['zc.id' => $item['course_id']], $joins, 'zc', '', 'zcy.name,zc.course_img');
                $item['name'] = empty($course) ? '' : $course['name'];
                $item['course_img'] = empty($course) ? '' : $course['course_img'];
            }
        }
        return returnResponse('1000', '', $list);
    }

    /**
     * @Notes: 订单详情
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 13:31
     * @Interface getOrderInfo
     * @return string
     */
    public function getOrderInfo()
    {
        if (!$orderId = input('post.id')) {
            return returnResponse('1001', '请选择订单');
        }

        $where = [
            'zo.user_id' => $this->userId,
            'zo.is_del' => 1,
            'zo.id' => $orderId
        ];
        $join = [
            ['yx_member m', 'zo.mem_id = m.uid', 'left'],
        ];
        $field = "zo.course_hour_record_id,zo.evaluate,zo.course_num,zo.activity_id,zo.activity_order_id,zo.id as order_num_id,zo.course_id,zo.student_id,zo.create_time,zo.order_id,zo.mem_id,zo.course_name,zo.course_category,zo.course_type,zo.status,zo.discount_price,zo.price,zo.original_price,m.cname";
        $order = Crud::getRelationData('zht_order', 1, $where, $join, 'zo', '', $field);
        //halt(Db::name('zht_order')->getLastSql());
        if (empty($order)) {
            return returnResponse('1002', '订单存在');
        }
        if ($order['course_category'] == 1) {
            $table = "";
        } else {
            $table = "zht_course";
        }
        //获取机构名称
        $joins = [
            ['yx_zht_category zcy', 'zc.category_id = zcy.id', 'left']
        ];
        $course = Crud::getRelationData($table, 1, ['zc.id' => $order['course_id']], $joins, 'zc', '', 'zc.course_img,zcy.name');
        $order['name'] = empty($course) ? '' : $course['name'];
        $order['course_img'] = $course['course_img'];
        //获取学员信息
        $student = Crud::getData('lmport_student', 1, ['id' => $order['student_id']], 'student_name,phone');
        if (empty($student)) {
            return returnResponse('1002', '学员异常');
        }
        $order['create_time'] = date('Y-m-d H:i', $order['create_time']);
        $order['student_name'] = $student['student_name'];
        $order['realname'] = empty($this->userInfo['realname']) ? '' : $this->userInfo['name'];
        $order['phone'] = $student['phone'];
        //是否关联活动活动
        if ($order['activity_id'] > 0) {
            //查看活动
            $activity = Crud::getData('zht_activity', 1, ['id' => $order['activity_id']], "activity_title,activity_img,activity_type");
            if (!empty($activity)) {
                $order['activity'] = $activity;
            }
        }
        //排课信息
        if (in_array($order['status'], [3, 4, 5, 6])) {
            //获取学员以上多少节课
            $count = Crud::getData('zht_student_class_list', 1, ['order_id' => $orderId, 'student_course_type' => ['in', '2,4']], 'count(id) as count');
            $studentClassList = Crud::getData('zht_student_class_list', 1, ['order_id' => $order['order_id'], 'is_del' => 1], 'arrange_course_id');
            // halt($studentClassList);
            $studentClass = Crud::getData("zht_arrange_course", 1, ['id' => $studentClassList['arrange_course_id'], 'is_del' => 1], 'arrange_course_name,arrange_course_num,course_num,start_arrange_course,end_arrange_course');

            $studentClass['start_arrange_course'] = date('Y-m-d', $studentClass['start_arrange_course']);
            $studentClass['end_arrange_course'] = date('Y-m-d', $studentClass['end_arrange_course']);
            $order['studentClass'] = $studentClass;
            $order['count'] = $count['count'];
        }

        return returnResponse('1000', '', $order);
    }

    /**
     * @Notes: 取消订单
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 15:19
     * @Interface cancelOrder
     * @return string
     */
    public function cancelOrder()
    {
        if (!$orderId = input('post.id/d')) {
            return returnResponse('1001', '请选择订单');
        }

        $order = Crud::getData('zht_order', 1, ['user_id' => $this->userId, 'status' => 1, 'id' => $orderId], 'order_num,course_num_id');
        if (empty($order)) {
            return returnResponse('1002', '订单不存在');
        }
        //修改子订单 与主订单状态
        Db::startTrans();
        try {
            $updateOrder = Crud::setUpdate('zht_order', ['id' => $orderId], ['update_time' => time(), 'status' => 7]);
            if (empty($updateOrder)) {
                throw new Exception("修改子订单状态失败");
            }
            $updateOrderNum = Crud::setUpdate('zht_order_num', ['order_num' => $order['order_num'], 'user_id' => $this->userId, 'status' => 1], ['update_time' => time(), 'status' => 7]);
            if (empty($updateOrderNum)) {
                throw new Exception("修改主订单状态失败");
            }
            //返还活动名额
            $courseNum = Crud::getData('zht_course_num', 1, ['id' => $order['course_num_id']], 'enroll_num');
            if (empty($courseNum)) {
                throw new Exception("课时异常");
            }
            $updateCourseNum = Crud::setUpdate('zht_course_num', ['id' => $order['course_num_id']], ['enroll_num' => $courseNum['enroll_num'] - 1]);
            if (empty($updateCourseNum)) {
                throw new Exception("退还课时名额异常");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }
        return returnResponse('1000', '取消成功');
    }

    /**
     * @Notes: 重新支付
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 18:51
     * @Interface rePayment
     * @return string
     */
    public function rePayment()
    {
        //校验订单状态
        if (!$orderId = input('post.id/d')) {
            return returnResponse('1001', '请选择订单');
        }

        $order = Crud::getData('zht_order', 1, ['user_id' => $this->userId, 'status' => 1, 'id' => $orderId], 'order_num,course_num_id,course_category,course_id');
        if (empty($order)) {
            return returnResponse('1002', '订单不存在');
        }
        if ($order['course_category'] == 1) {
            $onlineCourse = Crud::getData("zht_order", 1, ['course_id' => $order['course_id'], 'user_id' => $this->userId, 'status' => 2], 'id');
            if (!empty($onlineCourse)) {
                return returnResponse("1001", '您已购买，无需重复购买！');
            }
        }
        Db::startTrans();
        try {
            //
            $no = time() . rand(999, 9999);
            //修改主订单号
            $orderNum = Crud::getData('zht_order_num', 1, ['order_num' => $order['order_num'], 'user_id' => $this->userId, 'status' => 1], 'id,price');
            if (empty($orderNum)) {
                throw new Exception('主订单异常');
            }
            $updateOrderNum = Crud::setUpdate('zht_order_num', ['id' => $orderNum['id']], ['order_num' => $no, 'update_time' => time()]);
            if (empty($updateOrderNum)) {
                throw new Exception('修改主订单号失败');
            }
            //修改子订单号
            $updateOrder = Crud::setUpdate('zht_order', ['id' => $orderId], ['order_num' => $no, 'update_time' => time()]);
            if (empty($updateOrder)) {
                throw new Exception('修改子订单号失败');
            }
            //获取支付参数
            $config = config('wxpayConfig');
            $app = Factory::payment($config);

            $openid = $this->userInfo['x_openid'];

            $result = $app->order->unify([
                'body' => '购买',
                'out_trade_no' => $no,
                'total_fee' => $orderNum['price'] * 100,
                'notify_url' => "https://yxcs.insooner.com/xcx/v3/orderCallback",
                'trade_type' => 'JSAPI',
                'openid' => $openid
            ]);

            if ($result['return_code'] !== 'SUCCESS') {
                throw new Exception('获取支付参数失败');
            }

            //获取支付配置信息
            $jssdk = $app->jssdk;
            $wxPayParam = $jssdk->sdkConfig($result['prepay_id'], false);
            $wxPayParam['orderId'] = $order;
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }
        return returnResponse('1000', '', $wxPayParam);
    }

    /**
     * @Notes: 评论订单课程
     * @Author: asus
     * @Date: 2020/5/27
     * @Time: 14:47
     * @Interface addEvaluate
     * @return string
     */
    public function addEvaluate()
    {
        if (!$orderId = input('post.id/d')) {
            return returnResponse('1001', '请选择订单');
        }
        $courseCategory = input('post.course_category/d', 2);
        $score = input('post.score/d', 5);
        $content = input('post.content', '');


        $order = Crud::getData('zht_order', 1, ['id' => $orderId, 'user_id' => $this->userId, 'status' => 4], 'evaluate,course_id,course_category,course_num');
        if (empty($order)) {
            return returnResponse('1002', '订单异常');
        }
        if ($order['evaluate'] == 2) {
            return returnResponse('1002', '订单已评价');
        }

        if ($courseCategory == 1) {
            //线上
        } else {
            $table = "zht_course";
        }
        //查询课程
        $course = Crud::getData($table, 1, ['id' => $order['course_id']], "score,evaluate_num");
        if (empty($course)) {
            return returnResponse('1002', '课程异常');
        }

        Db::startTrans();
        try {
            //添加评价
            $addEvaluate = Crud::setAdd('zht_evaluate', ['user_id' => $this->userId, 'score' => $score, 'content' => $content, 'course_id' => $order['course_id'], 'course_category' => $order['course_category'], 'class_hour' => $order['course_num']]);
            if (empty($addEvaluate)) {
                throw new Exception('添加评论失败');
            }
            //修改评分
            $sum = ($course['evaluate_num'] * $course['score']) + $score;
            $avg = $sum / ($course['evaluate_num'] + 1);
            $sc = number_format($avg, 1);
            //throw new Exception("总分".$sum.'---平均分'.$sc);
            $updateScore = Crud::setUpdate($table, ['id' => $order['course_id']], ['update_time' => time(), 'score' => $sc, 'evaluate_num' => $course['evaluate_num'] + 1]);
            if (empty($updateScore)) {
                throw new Exception('修改课程分数失败');
            }

            //修改订单评价状态
            $updateOrder = Crud::setUpdate('zht_order', ['id' => $orderId], ['evaluate' => 2, 'update_time' => time()]);
            if (empty($updateOrder)) {
                throw new Exception('修改订单评价状态失败');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }

        return returnResponse('1000', '评论成功');

    }

    /**
     * @Notes: 获取订单排课信息
     * @Author: asus
     * @Date: 2020/6/2
     * @Time: 13:51
     * @Interface getStudentClassByOrderId
     * @return false|string
     */
    public function getStudentClassByOrderId()
    {

        $page = input("post.page/d", 1);
        $pageSize = input('post.page_size/d', 16);

        if (!$orderId = input('post.order_id')) {
            return returnResponse("1001", '请选择订单');
        }
        $order = Crud::getData("zht_order", 1, ['order_id' => $orderId, 'is_del' => 1, 'user_id' => $this->userId], 'status');
        if (empty($order)) {
            return returnResponse("1002", '订单异常');
        }

        if (!in_array($order['status'], [3, 4, 5, 6])) {
            return returnResponse("1002", '此订单暂时未排课');
        }

        //获取课程表
        $where = [
            'scl.order_id' => $orderId,
            'scl.is_del' => 1
        ];
        $join = [
            ['yx_zht_course_timetable zct', 'scl.course_timetable_id = zct.id', 'left'],
            ['yx_zht_course zc', 'zct.course_id = zc.id', 'left'],
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'],
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'],
            ['yx_lmport_student ls', 'scl.student_id = ls.id', 'left'],
            ['yx_zht_arrange_course zac', 'scl.arrange_course_id = zac.id', 'left']
        ];
        $week = ['日', '一', '二', '三', '四', '五', '六'];
        $field = "scl.id,zc.course_img,zc.course_name,zct.week,zct.day_time_start,zct.time_slot,zct.attend_class_num,zct.course_hour,t.teacher_nickname,c.province,c.city,c.area,c.address,ls.student_name,zac.arrange_course_name";
        $studengClass = Crud::getRelationData("zht_student_class_list", 2, $where, $join, 'scl', 'zct.day_time_start ASC', $field, $page, $pageSize);
        if (count($studengClass) > 0) {
            foreach ($studengClass as &$item) {
                if (date("Y-m-d", $item['day_time_start']) == date('Y-m-d')) {
                    $item['time'] = "今天";
                } else {
                    $item['time'] = date("m月d日", $item['day_time_start']);
                }

                unset($item['day_time_start']);
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
                $item['week'] = "周" . $week[$item['week']];
            }
        }
        return returnResponse('1000', '', $studengClass);
    }
}