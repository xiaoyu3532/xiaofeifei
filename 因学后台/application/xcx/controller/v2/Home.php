<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;


/**
 * 首页
 */
class Home extends Base
{
    protected $exceptTicket = ["getCatrgory", "getCourse", "getCourseInfo", "getTeacherInfo", "getMemberInfo", "getMemberCourse", 'getEvaluates', 'getcourseIsRecommend'];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 获取首页分类
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 16:09
     * @Interface getCatrgory
     * @return string
     */
    public function getCatrgory()
    {
        $result = Crud::getDataunpage('zht_category', 2, ['is_del' => 1, 'type' => 1], 'id as category_id,name', 'sort DESC');
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 首页附近推荐课程
     * @Author: asus
     * @Date: 2020/6/11
     * @Time: 16:20
     * @Interface getcourseIsRecommend
     * @return string
     */
    public function getcourseIsRecommend()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [];

        //推荐课程
        $where['zc.is_recommend'] = 1;
        $where['zc.type'] = 1;
        $where['zc.is_del'] = 1;
        $where['zc.activity_type'] = 2;
        $where['m.is_del'] = 1;

        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $field = 'zc.id as course_id,zc.course_type,zc.course_img,zc.discount_start_time,zc.discount_end_time,zc.discount,zy.name,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        //线下
        $table = 'zht_course';
        //距离排序
        $order = "distance ASC";

        $join = [
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
        ];

        $result = Crud::getRelationData($table, 2, $where, $join, 'zc', $order, $field, $page, $pageSize);
        //halt(Db::table($table)->getLastSql());
        if (count($result) > 0) {
            foreach ($result as &$item) {
                //获取课程课时报名人数
                $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
                $item['enroll_num'] = bcsub($enrollNum['surplus_num'], $enrollNum['enroll_num']);
                $item['course_category'] = 2;

                if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                    $dis = bcdiv($item['discount'], 10, 2);
                    $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                } else {
                    $item['present_price'] = $enrollNum['present_price'];
                }
                unset($item['discount_start_time']);
                unset($item['discount_end_time']);
                unset($item['discount']);
            }
        }

        $results = [
            'result' => $result
        ];


        return returnResponse('1000', '', $results);
    }

    /**
     * @Notes: 获取课程
     * @Author: asus
     * @Date: 2020/5/22
     * @Time: 17:36
     * @Interface getCourse
     * @return string
     */
    public function getCourse()
    {
        $categoryId = input('post.category_id');
        $search = input('post.search');
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [];
        // 指定分类课程
        if (!empty($categoryId)) {

            $where['zc.category_id'] = $categoryId;
            $where['zc.type'] = 1;
            $where['zc.is_del'] = 1;
            $where['m.is_del'] = 1;

        } else {
            //传入搜索内容
            $where['zc.course_name'] = ['like', "%" . $search . "%"];
            $where['zc.type'] = 1;
            $where['zc.is_del'] = 1;
            $where['m.is_del'] = 1;
            //记录热搜
            if (!empty(trim($search))) {
                $findSearch = Crud::getData("zht_search", 1, ['search_name' => $search, 'search_type' => 1, 'is_del' => 1], 'num');
                if (empty($findSearch)) {
                    Crud::setAdd("zht_search", ['search_name' => $search, 'search_type' => 1, 'num' => 1]);
                } else {
                    Crud::setUpdate("zht_search", ['search_name' => $search, 'search_type' => 1], ['update_time' => time(), 'num' => $findSearch['num'] + 1]);
                }
            }
        }
        $where['zc.activity_type'] = 2;
        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $field = 'zc.id as course_id,zc.course_type,zc.course_img,zy.name,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
        } else {
            //线下
            $table = 'zht_course';
        }

        //排序问题
        $order = input('post.order');
        if ($order == 1 || empty($order)) {
            //综合排序
            $order = "distance ASC,zc.present_price ASC";
        } elseif ($order == 2) {
            //距离排序
            $order = "distance ASC";
        } elseif ($order == 3) {
            //金额排序
            $order = "zc.present_price ASC";
        }
        $join = [
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
        ];

        $result = Crud::getRelationData($table, 2, $where, $join, 'zc', $order, $field, $page, $pageSize);

        if (count($result) > 0) {
            if (!empty(trim($search))) {
                //记录课程
                $findSearchCourse = Crud::getData("zht_search", 1, ['search_name' => $result[0]['course_name'], 'search_type' => 2, 'is_del' => 1], 'num');
                if (empty($findSearchCourse)) {
                    Crud::setAdd("zht_search", ['search_name' => $result[0]['course_name'], 'search_type' => 2, 'num' => 1]);
                } else {
                    Crud::setUpdate("zht_search", ['search_name' => $result[0]['course_name'], 'search_type' => 2], ['update_time' => time(), 'num' => $findSearchCourse['num'] + 1]);
                }
            }
            foreach ($result as &$item) {
                //获取课程课时报名人数
                if ($courseCategory == 2) {
                    $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,min(course_section_price) as present_price");
                    $item['enroll_num'] = $enrollNum['enroll_num'];
                    $item['course_category'] = $courseCategory;
                    if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                        $dis = bcdiv($item['discount'], 10, 2);
                        $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                    } else {
                        $item['present_price'] = $enrollNum['present_price'];
                    }
                    unset($item['discount_start_time']);
                    unset($item['discount_end_time']);
                    unset($item['discount']);
                }

            }
        }

        $results = [
            'result' => $result
        ];


        return returnResponse('1000', '', $results);

    }

    /**
     * @Notes: 获取课程详情
     * @Author: asus
     * @Date: 2020/5/22
     * @Time: 10:48
     * @Interface getCourseInfo
     * @return string
     */
    public function getCourseInfo()
    {
        $data = input();
        if (empty($data['course_id'])) {
            return returnResponse("1001", '请选择机构');
        }
        $courseId = $data['course_id'];

        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;

        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
        } else {
            //线下
            $table = 'zht_course';
        }

        $join = [
            ['yx_teacher t', 'zc.teacher_id = t.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
        ];
        //'zc.type' => 1
        $field = 'zc.id as course_id,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_wheel_img,zc.course_img,zc.course_name,zc.teacher_id,zc.mem_id,zy.name,zc.course_details,zc.surplus_num,zc.start_age,zc.end_age,t.teacher_nickname,t.sex,t.brief,m.logo,m.longitude,m.latitude,m.province,m.city,m.area,m.address,m.cname,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        $result = Crud::getRelationData($table, 1, ['zc.id' => $courseId, 'zc.is_del' => 1, 'm.is_del' => 1], $join, 'zc', '', $field);
        if (empty($result)) {
            return returnResponse("1002", "课程不存在");
        }

        $result['banner'] = unserialize($result['course_wheel_img']);
        unset($result['course_wheel_img']);
        $result['addr'] = $result['province'] . $result['city'] . $result['area'] . $result['address'];
        unset($result['province']);
        unset($result['city']);
        unset($result['area']);
        unset($result['address']);
        $result['teache_img'] = "";//固定
        $result['course_category'] = $courseCategory;
        //判断是否收藏
        $isCollection = Crud::getData('zht_collection', 1, ['user_id' => $this->userId, 'is_del' => 1, 'course_category' => $courseCategory, 'course_id' => $courseId], 'id');
        // halt(Db::name("zht_collection")->getLastSql());
        $result['is_collection'] = empty($isCollection) ? 0 : 1;
        //获取课程总报名人数与课程总人数  'course_category' => $courseCategory
        $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $courseId, 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num, IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
        $result['enroll_num'] = $enrollNum['enroll_num'];
        $result['surplus_num'] = $enrollNum['surplus_num'];
        if ($result['discount_start_time'] <= time() && $result['discount_end_time'] >= time()) {
            $dis = bcdiv($result['discount'], 10, 2);
            $result['original_price'] = $enrollNum['present_price'];
            $result['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
        } else {
            $result['original_price'] = $enrollNum['present_price'];
            $result['present_price'] = $enrollNum['present_price'];
        }
        unset($result['discount_start_time']);
        unset($result['discount_end_time']);
        unset($result['discount']);
        return returnResponse("1000", '', $result);
    }

    /**
     * @Notes: 获取教师详情
     * @Author: asus
     * @Date: 2020/5/22
     * @Time: 11:04
     * @Interface getTeacherInfo
     * @return string
     */
    public function getTeacherInfo()
    {
        if (!$teacherId = input('post.teacher_id/d')) {
            return returnResponse("1001", '无教师信息');
        }
        $result = Crud::getData("teacher", 1, ['id' => $teacherId, 'is_del' => 1], "teacher_nickname,sex,grade,brief,teacher_age");
        if (empty($result)) {
            return returnResponse("1002", '教师不存在');
        }

        $result['teache_img'] = "";//固定
        return returnResponse("1000", '', $result);
    }

    /**
     * @Notes: 查看机构详情
     * @Author: asus
     * @Date: 2020/5/22
     * @Time: 11:48
     * @Interface getMemberInfo
     * @return string
     */
    public function getMemberInfo()
    {
        $data = input();
        if (empty($data['mem_id'])) {
            return returnResponse("1001", '无机构信息');
        }
        $memId = $data['mem_id'];

        //获取机构基础信息
        $result = Crud::getData('member', 1, ['uid' => $memId, 'is_del' => 1], 'cname,phone,latitude,longitude,logo,wheel_img, title as introduction,province,city,area,address');
        if (empty($result)) {
            return returnResponse('1002', '机构不存在');
        }
        $result['banner'] = unserialize($result['wheel_img']);
        unset($result['wheel_img']);
        $result['addr'] = $result['province'] . $result['city'] . $result['area'] . $result['address'];
        unset($result['province']);
        unset($result['city']);
        unset($result['area']);
        unset($result['address']);

        $where = [
            'mem_id' => $memId,
            'is_del' => 1,
            'type' => 1
        ];
        $field = "id";
        $course = Crud::getData('zht_course', 2, $where, $field);
        $onlineCourse = Crud::getData("zht_online_course", 2, $where, $field);
        $result['count'] = bcadd(count($course), count($onlineCourse));
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取机构课程
     * @Author: asus
     * @Date: 2020/5/22
     * @Time: 16:58
     * @Interface getMemberCourse
     * @return string
     */
    public function getMemberCourse()
    {
        if (!$memId = input('post.mem_id/d')) {
            return returnResponse("1001", '请选择机构');
        }

        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
            $res['count'] = 0;
            $res['result'] = [];
            return returnResponse('1000', '', $res);
        } else {
            //线下
            $table = 'zht_course';
        }

        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [
            'zc.mem_id' => $memId,
            'zc.is_del' => 1,
            'zc.type' => 1,
            'm.is_del' => 1
        ];
        $join = [
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
        ];

        $order = "distance ASC";
        $field = 'zc.id as course_id,zc.course_img,zy.name,zc.present_price,zc.course_name,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))) AS distance';
        $course = Crud::getRelationData($table, 2, $where, $join, 'zc', $order, $field, $page, $pageSize);
        //获取课程报名人数
        if (count($course) > 0) {
            foreach ($course as &$item) {
                $item['course_category'] = $courseCategory;
                //获取课程课时报名人数
                if ($courseCategory == 2) {
                    $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num");
                    $item['enroll_num'] = $enrollNum['enroll_num'];
                }

            }
        }

        $result['result'] = $course;
        $result['count'] = count($course);
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取课程评论
     * @Author: asus
     * @Date: 2020/5/25
     * @Time: 9:44
     * @Interface getEvaluates
     * @return string
     */
    public function getEvaluates()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse('1001', '缺少参数');
        }
        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
        } else {
            //线下
            $table = 'zht_course';
        }
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $course = Crud::getData($table, 1, ['id' => $courseId, 'is_del' => 1, 'type' => 1], 'score,evaluate_num');
        if (empty($course)) {
            return returnResponse('1002', '课程不存在');
        }
        //查询评价
        $where = [
            'ze.course_id' => $courseId,
            'ze.course_category' => $courseCategory,
            'ze.is_del' => 1
        ];
        $join = [
            ['yx_user u', 'ze.user_id = u.id', 'left'],
        ];
        $field = "ze.score,ze.content,FROM_UNIXTIME(ze.create_time, '%Y-%m-%d') as create_time ,ze.class_hour,u.name,u.img";
        $evaluaetes = Crud::getRelationData("zht_evaluate", 2, $where, $join, 'ze', '', $field, $page, $pageSize);
        $result['score'] = $course['score'];
        $result['evaluate_num'] = $course['evaluate_num'];
        $result['evaluate'] = $evaluaetes;
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取课程课时
     * @Author: asus
     * @Date: 2020/5/25
     * @Time: 10:22
     * @Interface getCourseNum
     * @return string
     */
    public function getCourseNum()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse('1001', '缺少参数');
        }
        $course = Crud::getData("zht_course", 1, ['id' => $courseId, 'is_del' => 1, 'type' => 1], 'discount_start_time,discount_end_time,discount');
        if (empty($course)) {
            return returnResponse('1001', '课程异常');
        }

        //计算折扣
        if ($course['discount_start_time'] > time() || $course['discount_end_time'] < time()) {
            $discount = 1;
        } else {
            $discount = bcdiv($course['discount'], 10, 2);
        }

        $result = Crud::getDataunpage('zht_course_num', 2, ['course_id' => $courseId, 'is_del' => 1], 'id as course_num_id,course_section_num,course_section_price,enroll_num,surplus_num', 'course_section_num ASC');
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $item['course_section_price'] = bcmul($discount, $item['course_section_price'], 2);
            }
        }
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取课程优惠价格
     * @Author: asus
     * @Date: 2020/5/25
     * @Time: 13:43
     * @Interface getDiscount
     * @return string
     */
    public function getDiscount()
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
        //判断课程是否存在
        $course = Crud::getData($table, 1, ['id' => $courseId, 'is_del' => 1, 'type' => 1], 'discount_start_time,discount_end_time,discount');
        if (empty($course)) {
            return returnResponse('1002', '课程不存在');
        }
        if ($courseCategory == 2) {
            //判断课时
            $courseNum = Crud::getData('zht_course_num', 1, ['id' => $courseNumId, 'course_id' => $courseId, 'is_del' => 1], 'course_section_price,enroll_num,surplus_num');
            if (empty($courseNum)) {
                return returnResponse('1002', '课时不存在');
            }

            if ($courseNum['enroll_num'] >= $courseNum['surplus_num']) {
                return returnResponse('1002', '课时报名人数已满');
            }
            //返回折扣
            if ($course['discount_start_time'] > time() || $course['discount_end_time'] < time()) {
                $disPrice = 0;
            } else {
                $dis = bcdiv($course['discount'], 10, 2);
                //$discount = number_format($courseNum['course_section_price'] * $course['discount'], 2);
                $discount = bcmul($courseNum['course_section_price'], $dis, 2);
                $disPrice = bcsub($courseNum['course_section_price'], $discount, 2);
            }
        }

        return returnResponse('1000', '', [
            'discount' => $disPrice
        ]);
    }

    /**
     * @Notes: 添加收藏
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 9:39
     * @Interface addCollection
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function addCollection()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse("1001", '请选择课程');
        }
        $courseCategory = input('post.course_category/d', 2);

        $isCollection = Crud::getData('zht_collection', 1, ['user_id' => $this->userId, 'is_del' => 1, 'course_category' => $courseCategory, 'course_id' => $courseId], 'id');
        if (!empty($isCollection)) {
            return returnResponse("1002", '课程已被收藏');
        }
        //添加收藏
        $add = Crud::setAdd('zht_collection', ['user_id' => $this->userId, 'course_id' => $courseId, 'course_category' => $courseCategory], 1);
        if (empty($add)) {
            return returnResponse("1002", '收藏失败');
        }
        return returnResponse("1000", '收藏成功');
    }

    /**
     * @Notes: 取消收藏
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 9:41
     * @Interface cancelCollection
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function cancelCollection()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse("1001", '请选择课程');
        }
        $courseCategory = input('post.course_category/d', 2);

        $isCollection = Crud::getData('zht_collection', 1, ['user_id' => $this->userId, 'is_del' => 1, 'course_category' => $courseCategory, 'course_id' => $courseId], 'id');
        if (empty($isCollection)) {
            return returnResponse("1002", '课程未被收藏无法取消');
        }
        //取消收藏
        $cancel = Crud::setUpdate('zht_collection', ['user_id' => $this->userId, 'course_id' => $courseId, 'course_category' => $courseCategory], ['is_del' => 2, 'update_time' => time()]);
        if (empty($cancel)) {
            return returnResponse("1002", '取消失败');
        }
        return returnResponse("1000", '取消成功');
    }

    /**
     * @Notes: 获取收藏列表
     * @Author: asus
     * @Date: 2020/5/27
     * @Time: 15:18
     * @Interface getCollections
     * @return string
     */
    public function getCollections()
    {

        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $list = Crud::getData('zht_collection', 2, ['user_id' => $this->userId, 'is_del' => 1], "course_id,course_category", 'create_time DESC', $page, $pageSize);
        if (count($list) > 0) {
            foreach ($list as &$item) {
                if ($item['course_category'] == 1) {
                    //线上
                } else {
                    //线下
                    $table = 'zht_course';
                }
                $join = [
                    ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
                ];
                $where = [
                    'zc.id' => $item['course_id']
                ];
                $field = 'zc.course_img,zy.name,zc.present_price,zc.course_name,zc.type,discount_start_time,discount_end_time,discount';
                $course = Crud::getRelationData($table, 1, $where, $join, 'zc', '', $field);
                if (!empty($course)) {
                    $item['course_img'] = $course['course_img'];
                    $item['name'] = $course['name'];
                    $item['course_name'] = $course['course_name'];
                    $item['type'] = $course['type'];
                }
                if ($item['course_category'] == 2) {
                    //获取课程课时报名人数
                    $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,min(course_section_price) as present_price");
                    $item['enroll_num'] = $enrollNum['enroll_num'];
                    if ($course['discount_start_time'] <= time() && $course['discount_end_time'] >= time()) {
                        $dis = bcdiv($course['discount'], 10, 2);
                        $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                    } else {
                        $item['present_price'] = $enrollNum['present_price'];
                    }


                }

            }
        }
        return returnResponse('1000', '', $list);
    }

    public function share()
    {
        //添加访问量
        Crud::setAdd("zht_record", ['record_type' => 1]);
    }

}