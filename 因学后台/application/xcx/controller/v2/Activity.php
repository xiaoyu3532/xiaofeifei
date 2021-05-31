<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use EasyWeChat\Factory;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;

/**
 * 活动
 */
class Activity extends Base
{
    protected $exceptTicket = ["getRecommendActivityLists", "getActivityLists", "getActivityInfo"];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 获取首页活动推荐
     * @Author: asus
     * @Date: 2020/5/28
     * @Time: 10:27
     * @Interface getRecommendActivityLists
     * @return string
     */
    public function getRecommendActivityLists()
    {

        $where['activity_start_time'] = ['<=', time()];
        $where['activity_end_time'] = ['>=', time()];
        $where['is_recommend'] = ["=", 1];
        $where['is_del'] = ["=", 1];
        $where['status'] = ["=", 2];
        $activity = Crud::getData('zht_activity', 2, $where, "id,activity_img,activity_enroll_num,activity_title,activity_type,activity_price,activity_end_time - unix_timestamp(now()) as time", 'time DESC', 1, 10);
        $result['time'] = empty($activity[0]['time']) ? 0 : $activity[0]['time'];
        if (count($activity) > 0) {
            foreach ($activity as &$item) {

                if ($item['activity_type'] == 1) {
                    //获取阶梯价格
                    $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $item['id'], 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
                    array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $item['activity_price']]);
                    //halt($ladderPrice);
                    foreach ($ladderPrice as $values) {
                        if ($item['activity_enroll_num'] >= $values['ladder_num']) {
                            $item['activity_original_price'] = $item['activity_price'];
                            $item['activity_price'] = $values['ladder_price'];
                            break;
                        } else {
                            continue;
                        }
                    }

                }
            }
        }

        $ac = array_chunk($activity, 2);
        $result['activity'] = $ac;
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取活动列表
     * @Author: asus
     * @Date: 2020/5/28
     * @Time: 10:34
     * @Interface getActivityLists
     * @return string
     */
    public function getActivityLists()
    {
        $page = input("post.page/d", 1);
        $pageSize = input("post.page_size/d", 16);
        $where['activity_start_time'] = ['<=', time()];
        $where['activity_end_time'] = ['>=', time()];
        $where['is_del'] = ['=', 1];
        $where['status'] = ['=', 2];
        $activity = Crud::getData('zht_activity', 2, $where, "id,activity_img,activity_title,activity_type,activity_enroll_num,activity_price,activity_end_time", 'id DESC', $page, $pageSize);
        if (count($activity) > 0) {
            foreach ($activity as &$item) {
                $item['time'] = $item['activity_end_time'] - time();
                if ($item['activity_type'] == 1) {
                    //获取阶梯价格
                    $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $item['id'], 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
                    array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $item['activity_price']]);
                    //halt($ladderPrice);
                    foreach ($ladderPrice as $values) {
                        if ($item['activity_enroll_num'] >= $values['ladder_num']) {
                            $item['activity_original_price'] = $item['activity_price'];
                            $item['activity_price'] = $values['ladder_price'];
                            break;
                        } else {
                            continue;
                        }
                    }

                }
            }
        }
        return returnResponse('1000', '', $activity);
    }

    /**
     * @Notes: 获取活动详情
     * @Author: asus
     * @Date: 2020/5/29
     * @Time: 10:55
     * @Interface getActivityInfo
     * @return string
     */
    public function getActivityInfo()
    {
        $data = input();
        if (empty($data['id'])) {
            return returnResponse('1001', '请选择活动');
        }
        $id = $data['id'];

        $activity = Crud::getData('zht_activity', 1, ['id' => $id, 'is_del' => 1], 'id,status,activity_ismusic,activity_music,mem_id,activity_details,activity_title,activity_end_time,activity_price,activity_distribution,activity_num,activity_enroll_num,activity_rotation_chart,activity_type,activity_rule,visit_num');
        if (empty($activity)) {
            return returnResponse('1002', '活动不存在');
        }

        $activity['activity_rotation_chart'] = unserialize($activity['activity_rotation_chart']);
        $ti = $activity['activity_end_time'] - time();
        if ($ti > 0) {
            $activity['time'] = $ti;
        } else {
            $activity['time'] = 0;
        }
        unset($activity['activity_end_time']);
        $surplus = $activity['activity_num'] - $activity['activity_enroll_num']; //剩余活动数量
        $activity['surplus'] = $surplus;

        //获取购买人头像
        $user = Crud::getData('zht_activity_avatar', 2, ['activity_id' => $id, 'is_del' => 1], "avatar", 'id DESC', 1, 5);
        $activity['user'] = $user;
        $tips = [];
        if ($activity['activity_type'] == 1) {
            //拼团 获取拼团价格阶梯
            $ladderPrice = Crud::getDataunpage('zht_ladder_price', 2, ['activity_id' => $id, 'is_del' => 1], 'ladder_num,ladder_price', 'ladder_num DESC');
            array_push($ladderPrice, ['ladder_num' => 0, 'ladder_price' => $activity['activity_price']]);
            foreach ($ladderPrice as $k => $v) {
                if ($activity['activity_enroll_num'] >= $v['ladder_num']) {
                    $activity['activity_original_price'] = $activity['activity_price'];
                    $activity['activity_price'] = $v['ladder_price'];
                    if ($k == 0) {
                        $tips['need'] = bcsub($activity['activity_num'], $activity['activity_enroll_num']);
                        $tips['fill'] = $activity['activity_num'];
                        $tips['price'] = $v['ladder_price'];
                        $tips['reg'] = 0;
                    } else {
                        $tips['need'] = bcsub($ladderPrice[$k - 1]['ladder_num'], $activity['activity_enroll_num']);
                        $tips['fill'] = $ladderPrice[$k - 1]['ladder_num'];
                        $tips['price'] = $ladderPrice[$k - 1]['ladder_price'];
                        $tips['reg'] = bcsub($v['ladder_price'], $ladderPrice[$k - 1]['ladder_price'], 2);
                    }
                    break;
                }
                continue;
            }
            array_pop($ladderPrice);
            $activity['ladderPrice'] = array_reverse($ladderPrice);
            $activity['tips'] = $tips;
        }
        $shareId = empty($data['share_id']) ? 0 : $data['share_id'];
        if (!empty($shareId) && $shareId > 0) {
            //修改活动访问量 以及用户分享访问量 用户与机构分销绑定表访问数量
            Db::startTrans();
            try {
                $updateAcyivity = Crud::setUpdate("zht_activity", ['id' => $id], ['update_time' => time(), 'visit_num' => $activity['visit_num'] + 1]);
                if (empty($updateAcyivity)) {
                    throw new Exception('修改活动访问量失败');
                }
                $activityUserDistribution = Crud::getData("zht_activity_user_distribution", 1, ['user_id' => $shareId, 'mem_id' => $activity['mem_id'], 'activity_id' => $id, 'is_del' => 1], 'id,visit_num');
                if (empty($activityUserDistribution)) {
                    throw new Exception('用户数据异常');
                }

                $update = Crud::setUpdate("zht_activity_user_distribution", ['id' => $activityUserDistribution['id']], ['visit_num' => $activityUserDistribution['visit_num'] + 1, 'update_time' => time()]);
                if (empty($update)) {
                    throw new Exception('修改用户活动访问量失败');
                }

                $distributionRelation = Crud::getData("zht_distribution_relation", 1, ['user_id' => $shareId, 'mem_id' => $activity["mem_id"], 'is_del' => 1], 'id,visit_num');
                if (empty($distributionRelation)) {
                    throw new Exception('用户机构数据异常');
                }
                $updateDisRelation = Crud::setUpdate("zht_distribution_relation", ['id' => $distributionRelation['id']], ['visit_num' => $distributionRelation['visit_num'] + 1, 'update_time' => time()]);
                if (empty($updateDisRelation)) {
                    throw new Exception('修改用户机构活动访问量失败');
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return returnResponse('1003', $e->getMessage());
            }

        }

        return returnResponse('1000', '', $activity);
    }

    /**
     * @Notes: 获取活动字段
     * @Author: asus
     * @Date: 2020/5/29
     * @Time: 13:45
     * @Interface getActivityField
     * @return string
     */
    public function getActivityField()
    {
        if (!$activityId = input('post.id')) {
            return returnResponse('1001', '请选择活动');
        }
        $fileds = Crud::getData('zht_activity', 1, ['id' => $activityId, 'is_del' => 1], 'activity_field_ids');
        if (empty($fileds)) {
            return returnResponse('1001', '请选择活动');
        }
        $name = unserialize($fileds['activity_field_ids']);

        $arr = [];
        if (count($name[0]) > 0) {
            for ($i = 0; $i < count($name); $i++) {
                //获取报名所需字段
                $fi = Crud::getData('zht_activity_field', 1, ['id' => $name[$i][0], 'is_del' => 1], 'name,field');
                $arr[] = $fi;
            }
        }


        return returnResponse('1000', '', $arr);
    }

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

                //活动关联课程时
                if ($activity['activity_iscourse'] == 1) {
                    //添加课时记录表
                    $table = $activity['activity_course_category'] == 1 ? "" : "zht_course";
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
                    'total_fee' => $price * 100,
                    'notify_url' => "https://zht.insooner.com/xcx/v2/collageCallback",
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
     * @Notes: 获取活动订单
     * @Author: asus
     * @Date: 2020/5/29
     * @Time: 17:44
     * @Interface getActivityList
     * @return string
     */
    public function getActivityOrderList()
    {
        $status = input('post.status/d', 0);
        $where = [];
        if ($status > 0) {
            $where['ao.status'] = ['=', $status];
        }
        $search = input("post.search");
        if (!empty(trim($search))) {
            $where['a.activity_title'] = ['like', "%" . $search . "%"];
        }
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where['ao.user_id'] = ["=", $this->userId];
        $where['ao.is_del'] = ["=", 1];
        $wheres['ao.status'] = ["neq", 2];
        $join = [
            ['yx_zht_activity a', 'ao.activity_id = a.id']
        ];
        //halt($where);
        $field = "ao.id,ao.activity_id,ao.status,ao.price,a.activity_title,a.activity_img,a.activity_end_time,ao.activity_type,a.activity_enroll_num,a.activity_num";
        $result = Crud::getRelationDataAndWhere('zht_activity_order', 2, $where, $join, 'ao', 'ao.id DESC', $field, $page, $pageSize, '', $wheres);//halt($result);
        if (count($result) > 0) {
            foreach ($result as &$item) {
                if ($item['status'] == 1) {
                    // 进行中
                    $item['time'] = $item['activity_end_time'] - time();
                    $item['diff'] = $item['activity_num'] - $item['activity_enroll_num'];

                }
                unset($item['activity_end_time']);
                unset($item['activity_num']);
                unset($item['activity_enroll_num']);
            }
        }
        return returnResponse('1000', '', $result);
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
                    $table = $result['course_category'] == 1 ? "" : "zht_course";
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

    /**
     * @Notes: 分享活动
     * @Author: asus
     * @Date: 2020/6/3
     * @Time: 13:43
     * @Interface shareActivity
     * @return string
     */
    public function shareActivity()
    {
        if (!$activityId = input('post.id/d')) {
            return returnResponse('1001', '请选择分享活动');
        }
        $activity = Crud::getData("zht_activity", 1, ['status' => 2, 'is_del' => 1, 'id' => $activityId], "share_num,mem_id");
        if (empty($activity)) {
            return returnResponse('1001', '该活动无法分享');
        }
        Db::startTrans();
        try {
            //修改活动分享数
            $updateAvtivity = Crud::setUpdate('zht_activity', ['id' => $activityId], ['update_time' => time(), 'share_num' => $activity['share_num'] + 1]);
            if (empty($updateAvtivity)) {
                throw new Exception("修改活动分享数失败");
            }
            //获取用户个人活动分享数
            $activityUserDistribution = Crud::getData("zht_activity_user_distribution", 1, ['user_id' => $this->userId, 'mem_id' => $activity['mem_id'], 'activity_id' => $activityId, 'is_del' => 1], 'id,share_num');
            if (empty($activityUserDistribution)) {
                //新增
                $add = Crud::setAdd('zht_activity_user_distribution', ['user_id' => $this->userId, 'mem_id' => $activity['mem_id'], 'activity_id' => $activityId, 'share_num' => 1]);
                if (empty($add)) {
                    throw new Exception("添加用户活动分享次数失败");
                }
            } else {
                //修改
                $update = Crud::setUpdate("zht_activity_user_distribution", ['id' => $activityUserDistribution['id']], ['update_time' => time(), 'share_num' => $activityUserDistribution['share_num'] + 1]);
                if (empty($update)) {
                    throw new Exception("修改用户活动分享次数失败");
                }
            }
            //获取个人与机构绑定关系表 分享数量
            $distributionRelation = Crud::getData("zht_distribution_relation", 1, ['user_id' => $this->userId, 'mem_id' => $activity["mem_id"], 'is_del' => 1], 'id,share_num');
            if (empty($distributionRelation)) {
                $addDisRelation = Crud::setAdd("zht_distribution_relation", ['user_id' => $this->userId, 'mem_id' => $activity['mem_id'], 'share_num' => 1]);
                if (empty($addDisRelation)) {
                    throw new Exception("修改用户机构总分享量失败");
                }
            } else {
                $updateDisRelation = Crud::setUpdate("zht_distribution_relation", ['id' => $distributionRelation['id']], ['update_time' => time(), 'share_num' => $distributionRelation['share_num'] + 1]);
                if (empty($updateDisRelation)) {
                    throw new Exception("修改用户机构总分享量失败");
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }
        return returnResponse('1000', '分享成功');
    }
}