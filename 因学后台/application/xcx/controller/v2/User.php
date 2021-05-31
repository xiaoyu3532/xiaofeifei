<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Cache;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use EasyWeChat\Factory;


/**
 * 用户
 */
class User extends Base
{
    protected $exceptTicket = ['getPhoneCode'];
    // protected $allowTourist = ['access_token'];


    /**
     * @Notes: 获取用户基础数据
     * @Author: asus
     * @Date: 2020/5/19
     * @Time: 17:42
     * @Interface getUserInfo
     * @return string
     */
    public function getUserInfo()
    {
        return returnResponse('1000', '', [
            'user_id' => $this->userId,
            'name' => $this->userInfo['name'] ?? '',
            'phone' => $this->userInfo['phone'] ?? '',
            'sex' => $this->userInfo['sex'] ?? '',
            'img' => $this->userInfo['img'] ?? '',
            'qq' => $this->userInfo['qq'] ?? '',
            'email' => $this->userInfo['email'] ?? '',
            "realname" => $this->userInfo['realname'] ?? '',
            'work_address' => $this->userInfo['work_address'] ?? ''
        ]);

    }

    /**
     * @Notes:上传用户头像昵称
     * @Author: asus
     * @Date: 2020/5/20
     * @Time: 13:23
     * @Interface uploadUserInfo
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function uploadUserInfo()
    {
        $name = input('post.name'); //昵称
        $img = input('post.img');  //头像
        $update = Crud::setUpdate("user", ['id' => $this->userId], ['name' => $name, 'img' => $img, 'update_time' => time()]);
        if (empty($update)) {
            return returnResponse('10002', '修改数据失败');
        }
        return jsonResponse('1000', '更新成功');
    }

    /**
     * @Notes: 更新用户个人数据
     * @Author: asus
     * @Date: 2020/5/20
     * @Time: 13:36
     * @Interface updateUserInfo
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function updateUserInfo()
    {


        //TODO 数据未验证 例如邮箱
        $arr = input('post.');
        $param = [];
        if (!empty($arr['realname']) && $arr['realname'] != $this->userInfo['realname']) {
            $param['realname'] = $arr['realname'];
        }


        if (!empty($arr['sex']) && $arr['sex'] != $this->userInfo['sex']) {
            $param['sex'] = $arr['sex'];
        }

        if (!empty($arr['qq']) && $arr['qq'] != $this->userInfo['qq']) {
            $param['qq'] = $arr['qq'];
        }

        if (!empty($arr['email']) && $arr['email'] != $this->userInfo['email']) {
            $param['email'] = $arr['email'];
        }

        if (!empty($arr['work_address']) && $arr['work_address'] != $this->userInfo['work_address']) {
            $param['work_address'] = $arr['work_address'];
        }

        if (count($param) == 0) {
            return returnResponse('1001', '无数据更新');
        }
        $param['update_time'] = time();

        $update = Crud::setUpdate("user", ['id' => $this->userId], $param);
        if (empty($update)) {
            return returnResponse('1002', '修改数据失败');
        }
        return returnResponse('1000', '更新成功');
    }

    /**
     * @Notes: 获取学员信息
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 11:00
     * @Interface getUserStudents
     * @return string
     */
    public function getUserStudents()
    {
        $where = [
            'us.user_id' => $this->userId,
            'us.is_del' => 1
        ];

        $join = [
            ['yx_lmport_student ls', 'us.student_id = ls.id and ls.is_del = 1', 'left'],
        ];
        $field = 'us.relation,ls.student_name,ls.sex,ls.phone,ls.year_age,ls.class,ls.school,ls.id as student_id,us.is_default';

        $result = Crud::getRelationData('user_student', '2', $where, $join, 'us', $order = 'us.is_default DESC', $field, 1, 50);

        if (count($result) > 0) {
            foreach ($result as &$item) {
                if (empty($item['class'])) {
                    $item['class'] = '-';
                }
                if (empty($item['school'])) {
                    $item['school'] = '-';
                }
            }
        }
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 添加学员
     * @Author: asus
     * @Date: 2020/5/20
     * @Time: 17:34
     * @Interface addUserStudent
     * @return string
     */
    public function addUserStudent()
    {

        $data = [];
        if (!$studentName = input('post.student_name')) {
            return returnResponse('1001', '请填写学生姓名');
        }
        $data['student_name'] = $studentName;

        if (!$phone = input('post.phone')) {
            return returnResponse('1001', '请填写手机号');
        }
        $data['phone'] = $phone;

        if (!$code = input('post.code')) {
            return returnResponse('1001', '请填写手机验证码');
        }

        $codes = cache::get($phone);
        if (empty($code)) {
            return returnResponse('1001', '手机号有误');
        }
        if ($codes != $code) {
            return returnResponse('1001', '验证码有误');
        }

        if (!$birthday = input('post.birthday')) {
            return returnResponse('1001', '请填写出生日期');
        }
        $data['birthday'] = $birthday;
        $data['year_age'] = CalculationAge($birthday);

        if (!$sex = input('post.sex')) {
            return returnResponse('1001', '请选择性别');
        }
        $data['sex'] = $sex;

        $relation = input('post.relation');
        if (!empty($relation)) {
            $data['relation'] = $relation;
        }

        $idCard = input('post.id_card');
        if (!empty($idCard)) {
            $data['id_card'] = $idCard;
        }

        $province = input('post.province'); //省
        if (!empty($province)) {
            $data['province'] = $province;
        }
        $city = input('post.city'); //市
        if (!empty($city)) {
            $data['city'] = $city;
        }
        $area = input('post.area');
        if (!empty($area)) {
            $data['area'] = $area;
        }
        $address = input('post.address');
        if (!empty($address)) {
            $data['address'] = $address;
        }
        $school = input('post.school');
        if (!empty($school)) {
            $data['school'] = $school;
        }
        $class = input('post.class');
        if (!empty($class)) {
            $data['class'] = $class;
        }
        $data['user_name'] = $this->userInfo['name'];
        $default = input('post.is_default');
        $isDefault = $default == 1 ? 1 : 2;


        //查询学员是否存在
        $studentInfo = Crud::getData("lmport_student", '1', ['student_name' => $studentName, 'phone' => $phone, 'is_del' => 1], 'id');
        if (!empty($studentInfo)) {
            return returnResponse('1002', "该学员已存在");
        }

        Db::startTrans();
        try {
            //添加学员信息
            $addStudent = Crud::setAdd("lmport_student", $data, 2);

            if (empty($addStudent)) {
                throw new Exception("添加学员失败");
            }

            //绑定关系
            if ($isDefault == 2) {
                //设置为默认 其他已有绑定关系修改为普通
                $ids = Crud::getDataunpage("user_student", '2', ["user_id" => $this->userId, 'is_default' => 2, 'is_del' => 1], 'id');

                if (count($ids) > 0) {
                    //修改关系
                    $idCounts = array_column($ids, 'id');
                    $where = [
                        'user_id' => $this->userId,
                        'is_default' => 2,
                        'is_del' => 1
                    ];

                    $updateDefault = Crud::setUpdate("user_student", $where, ['update_time' => time(), 'is_default' => 1]);
                    if ($updateDefault != count($ids)) {
                        throw new Exception("修改默认失败");
                    }

                }
            }

            //添加绑定关系
            $addStudentRelation = Crud::setAdd("user_student", ['user_id' => $this->userId, 'student_id' => $addStudent, 'relation' => $relation, 'is_default' => $isDefault]);
            if (empty($addStudentRelation)) {
                throw new Exception("关系绑定失败");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse("1002", $e->getMessage());
        }
        return returnResponse("1000", '添加成功');
    }

    /**
     * @Notes: 删除学员
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 13:27
     * @Interface deleteStudent
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function deleteStudent()
    {
        if (!$studentId = input('post.student_id/d')) {
            return returnResponse("1001", '请选择学员');
        }

        $studentInfo = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
        if (empty($studentInfo)) {
            return returnResponse("1002", '学员信息错误');
        }
        //删除绑定关系
        $update = Crud::setUpdate('user_student', ['id' => $studentInfo['id']], ['is_del' => 2, 'update_time' => time()]);
        if (empty($update)) {
            return returnResponse("1002", '删除失败');
        }
        return returnResponse("1000", '删除成功');

    }

    /**
     * @Notes: 获取一位学员信息
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 13:39
     * @Interface getOneSudent
     * @return string
     */
    public function getOneSudent()
    {
        if (!$studentId = input('post.student_id/d')) {
            return returnResponse("1001", '请选择学员');
        }

        $where = [
            'us.user_id' => $this->userId,
            'us.is_del' => 1,
            'us.student_id' => $studentId
        ];
        $join = [
            ['yx_lmport_student ls', 'us.student_id = ls.id and ls.is_del = 1', 'left'],
        ];
        $field = 'us.relation,ls.student_name,ls.sex,ls.phone,ls.birthday,ls.province,ls.city,ls.area,ls.id_card,ls.address,ls.class,ls.school,ls.id as student_id';

        $result = Crud::getRelationData('user_student', 1, $where, $join, 'us', $order = 'us.is_default DESC', $field);

        if (empty($result)) {
            return returnResponse("1002", '学员信息错误');
        }
        return returnResponse("1000", '', $result);
    }

    /**
     * @Notes: 修改学员信息
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 14:27
     * @Interface updateOneSudent
     * @return string
     */
    public function updateOneSudent()
    {
        $data = [];
        if (!$studentId = input('post.student_id/d')) {
            return returnResponse("1001", '请选择学员');
        }


        if (!$studentName = input('post.student_name')) {
            return returnResponse('1001', '请填写学生姓名');
        }
        $data['student_name'] = $studentName;

        if (!$phone = input('post.phone')) {
            return returnResponse('1001', '请填写手机号');
        }
        $data['phone'] = $phone;

        if (!$code = input('post.code')) {
            return returnResponse('1001', '请填写手机验证码');
        }

        $codes = cache::get($phone);
        if (empty($code)) {
            return returnResponse('1001', '手机号有误');
        }
        if ($codes != $code) {
            return returnResponse('1001', '验证码有误');
        }

        if (!$birthday = input('post.birthday')) {
            return returnResponse('1001', '请填写出生日期');
        }
        $data['birthday'] = $birthday;
        $data['year_age'] = CalculationAge($birthday);

        if (!$sex = input('post.sex')) {
            return returnResponse('1001', '请选择性别');
        }
        $data['sex'] = $sex;

        $relation = input('post.relation');
        if (!empty($relation)) {
            $data['relation'] = $relation;
        }

        $idCard = input('post.id_card');
        if (!empty($idCard)) {
            $data['id_card'] = $idCard;
        }

        $province = input('post.province'); //省
        if (!empty($province)) {
            $data['province'] = $province;
        }
        $city = input('post.city'); //市
        if (!empty($city)) {
            $data['city'] = $city;
        }
        $area = input('post.area');
        if (!empty($area)) {
            $data['area'] = $area;
        }
        $address = input('post.address');
        if (!empty($address)) {
            $data['address'] = $address;
        }
        $school = input('post.school');
        if (!empty($school)) {
            $data['school'] = $school;
        }
        $class = input('post.class');
        if (!empty($class)) {
            $data['class'] = $class;
        }
        //$data['user_name'] = $this->userInfo['name'];
        $default = input('post.is_default');
        $isDefault = $default == 1 ? 1 : 2;
        $data['update_time'] = time();

        //获取学员信息
        $student = Crud::getData('lmport_student', 1, ['id' => $studentId, 'is_del' => 1], 'student_name,phone');
        if (empty($student)) {
            return returnResponse('1002', "该学员不存在");
        }
        if ($student['student_name'] != $studentName || $student['phone'] != $phone) {
            //手机号或者名字有一个不一致时
            //查询学员是否存在
            $studentInfo = Crud::getData("lmport_student", '1', ['student_name' => $studentName, 'phone' => $phone, 'is_del' => 1], 'id');
            if (!empty($studentInfo)) {
                return returnResponse('1002', "该学员已存在");
            }

        }

        Db::startTrans();
        try {
            //修改学员信息
            $addStudent = Crud::setUpdate("lmport_student", ['id' => $studentId, 'is_del' => 1], $data);

            if (empty($addStudent)) {
                throw new Exception("添加学员失败");
            }

            //绑定关系
            if ($isDefault == 2) {
                //设置为默认 其他已有绑定关系修改为普通
                $ids = Crud::getDataunpage("user_student", '2', ["user_id" => $this->userId, 'is_default' => 2, 'is_del' => 1], 'id');

                if (count($ids) > 0) {
                    //修改关系
                    $idCounts = array_column($ids, 'id');
                    $where = [
                        'user_id' => $this->userId,
                        'is_default' => 2,
                        'is_del' => 1
                    ];

                    $updateDefault = Crud::setUpdate("user_student", $where, ['update_time' => time(), 'is_default' => 1]);
                    if ($updateDefault != count($ids)) {
                        throw new Exception("修改默认失败");
                    }

                }
            }

            //修改绑定关系
            $addStudentRelation = Crud::setUpdate("user_student", ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], ['relation' => $relation, 'update_time' => time(), 'is_default' => $isDefault]);
            if (empty($addStudentRelation)) {
                throw new Exception("关系绑定失败");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse("1002", $e->getMessage());
        }
        return returnResponse("1000", "修改成功");
    }

    /**
     * @Notes: 绑定学员
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 14:43
     * @Interface bindStudent
     * @return string
     */
    public function bindStudent()
    {
        if (!$studentName = input('post.student_name')) {
            return returnResponse('1001', '请填写学生姓名');
        }
        $data['student_name'] = $studentName;

        if (!$phone = input('post.phone')) {
            return returnResponse('1001', '请填写手机号');
        }
        $data['phone'] = $phone;

        if (!$code = input('post.code')) {
            return returnResponse('1001', '请填写手机验证码');
        }

        // TODO 校验手机验证码

        //查询学员是否存在
        $studentInfo = Crud::getData("lmport_student", '1', ['student_name' => $studentName, 'phone' => $phone, 'is_del' => 1], 'id');
        if (empty($studentInfo)) {
            return returnResponse('1002', "该学员不存在");
        }

        //查询是否bind
        $bind = Crud::getData("user_student", 1, ['user_id' => $this->userId, 'student_id' => $studentInfo['id'], 'is_del' => 1], 'id');
        if (!empty($bind)) {
            return returnResponse('1002', "已绑定此学员");
        }
        $default = input('post.is_default');
        $isDefault = $default == 1 ? 1 : 2;
        Db::startTrans();
        try {
            //绑定关系
            if ($isDefault == 2) {
                //设置为默认 其他已有绑定关系修改为普通
                $ids = Crud::getDataunpage("user_student", '2', ["user_id" => $this->userId, 'is_default' => 2, 'is_del' => 1], 'id');
                if (count($ids) > 0) {
                    //修改关系
                    $idCounts = array_column($ids, 'id');
                    $where = [
                        'user_id' => $this->userId,
                        'is_default' => 2,
                        'is_del' => 1
                    ];

                    $updateDefault = Crud::setUpdate("user_student", $where, ['update_time' => time(), 'is_default' => 1]);
                    if ($updateDefault != count($ids)) {
                        throw new Exception("修改默认失败");
                    }

                }
            }

            //添加绑定关系
            $addStudentRelation = Crud::setAdd("user_student", ['user_id' => $this->userId, 'student_id' => $studentInfo['id'], 'is_default' => $isDefault]);
            if (empty($addStudentRelation)) {
                throw new Exception("关系绑定失败");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse("1002", $e->getMessage());
        }

        return returnResponse("1000", '绑定成功');

    }

    /**
     * @Notes: 获取未读消息数量
     * @Author: asus
     * @Date: 2020/5/27
     * @Time: 16:32
     * @Interface getMessageUnreadCount
     * @return string
     */
    public function getMessageUnreadCount()
    {
        $count = Crud::getData('zht_message', 1, ['user_id' => $this->userId, 'is_del' => 1, 'is_read' => 1], "count(id) as count");
        return returnResponse('1000', '', $count);
    }

    /**
     * @Notes: 获取消息列表
     * @Author: asus
     * @Date: 2020/6/1
     * @Time: 10:48
     * @Interface getMessages
     * @return string
     */
    public function getMessages()
    {

        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $message = Crud::getDatas('zht_message', 2, ['user_id' => $this->userId, 'type' => 1, 'is_del' => 1], "id,student_id,type,class_hour,course_id,course_category,create_time", 'create_time DESC', $page, $pageSize);
        if (count($message) > 0) {
            foreach ($message as &$item) {
                $item['create_time'] = date("Y-m-d H:i", $item['create_time']);
                $table = $item['course_category'] == 1 ? "" : "zht_course";
                $course = Crud::getData($table, 1, ['id' => $item['course_id'], 'is_del' => 1], 'course_name');
                //halt($course);
                $student = Crud::getData("lmport_student", 1, ['id' => $item['student_id'], 'is_del' => 1], 'student_name');
                if ($item['type'] == 1) {
                    //上课提醒
                    $string = "亲爱的家长,你为孩子%s报名的《%s》第%s课时马上要开始了，记得提醒%s同学准时上课哦~";
                    $txt = sprintf($string, $student['student_name'], $course['course_name'], $item['class_hour'], $student['student_name']);
                    $item['title'] = "课程即将开始";
                    $item['content'] = $txt;
                }
            }
        }
        $count = Crud::getDataunpage('zht_message', 2, ['user_id' => $this->userId, 'is_del' => 1, 'is_read' => 1], 'id');

        if (count($count) > 0) {
            //修改未读为已读
            Db::startTrans();
            try {

                $update = Crud::setUpdate('zht_message', ['user_id' => $this->userId, 'is_read' => 1], ['update_time' => time(), 'is_read' => 2]);
                if ($update != count($count)) {
                    throw new Exception('更新阅读状态失败');
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return returnResponse('1002', $e->getMessage());
            }

        }

        return returnResponse('1000', '', $message);
    }

    /**
     * @Notes: 获取个人佣金信息
     * @Author: asus
     * @Date: 2020/6/4
     * @Time: 10:08
     * @Interface getCommission
     * @return string
     */
    public function getCommission()
    {

        $startTime = date("Y-m-01");
        $endTime = date('Y-m-d', strtotime("$startTime +1 month -1 day"));
        $start = strtotime($startTime . " 00:00:00");
        $end = strtotime($endTime . " 23:59:59");
        $where["share_id"] = $this->userId;
        $where['create_time'] = ['>=', $start];
        $where['create_time'] = ['<=', $end];
        $where['is_del'] = ['=', 1];
        $result = Crud::getData('zht_distribution', 1, $where, 'IFNULL(sum(month_commission),0) as month,IFNULL(sum(basics_commission),0) as basics,IFNULL(sum(exclusive_commission),0) as excl,count(id) as countMonth');
        $result['month_balance'] = bcadd(bcadd($result['month'], $result['basics'], 2), $result['excl'], 2);
        unset($result['month']);
        unset($result['basics']);
        unset($result['excl']);

        $wheres["share_id"] = $this->userId;
        $wheres['is_del'] = ['=', 1];
        $total = Crud::getData('zht_distribution', 1, $wheres, 'IFNULL(sum(month_commission),0) as month,IFNULL(sum(basics_commission),0) as basics,IFNULL(sum(exclusive_commission),0) as excl,count(id) as count');
        $result['count'] = $total['count'];
        $result['total_balance'] = bcadd(bcadd($total['month'], $total['basics'], 2), $total['excl'], 2);
        unset($total);
        $result['balance'] = $this->userInfo['commission'];
        $result['content'] = "1.平台产品标注佣金“金额/比例”，都由入驻机构发布设置，费用由入驻机构经营所得支付；\r\n
                              2.平台显示的“专属佣金”，是指用户与课程商家单独约定的推广“返佣”，仅对约定商家发布产品有效；\r\n
                              注：上述两项功能“因学云”仅提供技术平台支持，相关权益问题请与课程机构（抓取课程机构名称、联系电话）联系。";
        return returnResponse('1000', '', $result);

    }

    /**
     * @Notes: 获取分销记录
     * @Author: asus
     * @Date: 2020/6/4
     * @Time: 10:28
     * @Interface getCommissionList
     * @return string
     */
    public function getCommissionList()
    {
        $page = input('post.page/d', 1);
        $pageSize = input("post.page_size/d", 16);
        $result = Crud::getData("zht_distribution", 2, ['share_id' => $this->userId, 'is_del' => 1], "shared_id,activity_id,mem_id,shared_id,basics_commission,exclusive_commission,month_commission,distribution_type,create_time", 'id DESC', $page, $pageSize);
        if (count($result) > 0) {
            foreach ($result as &$item) {
                if ($item['distribution_type'] == 1) {
                    $activity = crud::getData('zht_activity', 1, ['id' => $item['activity_id']], "activity_title");
                    $item['title'] = empty($activity) ? "" : $activity['activity_title'];
                    $user = Crud::getData("user", 1, ['id' => $item['shared_id']], "name,img");
                    $item['img'] = $user['img'];
                    $item['nickname'] = $user['name'];
                    unset($user);
                } else {
                    $item['title'] = "月绩佣金";
                    $mem = Crud::getData("member", 1, ['uid' => $item['mem_id'], 'is_del' => 1], 'cname');
                    $item['content'] = empty($mem) ? "" : $mem['cname'];
                }
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                unset($item['shared_id']);
                unset($item['activity_id']);
                unset($item['mem_id']);
                unset($item['mem_id']);
            }
        }
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 提现
     * @Author: asus
     * @Date: 2020/6/4
     * @Time: 13:24
     * @Interface withdrawal
     * @return string
     */
    public function withdrawal()
    {
        if (!$money = input('post.money')) {
            return returnResponse('1001', '请输入提现金额');
        }
        if ($money < 0.3) {
            return returnResponse('1001', '提现金额最低为0.3元');
        }

        if ($this->userInfo['commission'] < $money) {
            return returnResponse('1001', '提现金额超过余额');
        }
        $commission = $this->userInfo['commission'] - $money;
        Db::startTrans();
        try {
            //减掉佣金
            $updateUser = Crud::setUpdate('user', ['id' => $this->userId], ['update_time' => time(), 'commission' => $commission]);
            if (empty($updateUser)) {
                throw new Exception('扣除佣金失败');
            }
            //添加记录
            $orderNo = time() . rand(999, 9999);
            $add = Crud::setAdd("zht_money_record", ['order_no' => $orderNo, 'user_id' => $this->userId, 'money' => $money, 'money_type' => 2, 'examine_status' => 2]);
            if (empty($add)) {
                throw new Exception('添加提现记录失败');
            }
            // $config = config('wxpayConfig');
            // $app = Factory::payment($config);
            // $toBalance = $app->transfer->toBalance([
            //     'partner_trade_no' => $orderNo, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            //     'openid' => $this->userInfo['x_openid'],
            //     'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            //     're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            //     'amount' => $money * 100, // 企业付款金额，单位为分
            //     'desc' => '提现', // 企业付款操作说明信息。必填
            // ]);
            // if ($toBalance['return_code'] != "SUCCESS" || $toBalance['result_code'] != "SUCCESS") {
            //     throw new Exception($toBalance['err_code_des']);
            // }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1003', $e->getMessage());
        }
        return returnResponse('1000', '提现成功');
    }

    /**
     * @Notes: 提现列表
     * @Author: asus
     * @Date: 2020/6/4
     * @Time: 15:50
     * @Interface withdrawalList
     * @return string
     */
    public function withdrawalList()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);

        $data = Crud::getData("zht_money_record", 2, ['user_id' => $this->userId, 'is_del' => 1], "money,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%s') as create_time", 'id DESC', $page, $pageSize);
        return returnResponse('1000', '', $data);
    }

    /**
     * @Notes: 获取手机验证码
     * @Author: asus
     * @Date: 2020/6/5
     * @Time: 13:56
     * @Interface getCode
     * @return string
     */
    public function getPhoneCode()
    {

        if (!$phone = input("post.phone")) {
            return returnResponse('1001', '请输入手机号');
        }

        if (strlen($phone) != 11) {
            return returnResponse('1001', '请输入正确的手机号');
        }

        $chars = "/^1(3|4|5|6|7|8|9)\d{9}$/";
        if (!preg_match($chars, $phone)) {
            return returnResponse('1001', '手机号输入有误');
        }

        $str = '1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr, 0, 4);//substr(string,start,length);返回字符串的一部分
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendPhoneSms($phone, $content);
        if (!empty($response)) {
            Cache::set($phone, $code, 900);
            return jsonResponse('1000', $response, '验证码发送成功');
        }
    }
}