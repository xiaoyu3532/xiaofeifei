<?php

namespace app\xcx\controller\v3;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use app\xcx\controller\v2\Base;


/**
 * 首页
 */
class Home extends Base
{
    protected $exceptTicket = ["getCatrgory", "getCourse", "getCourseInfo", "getTeacherInfo", "getMemberInfo", "getMemberCourse", 'getEvaluates', 'getcourseIsRecommend', 'getZiYue'];

    // protected $allowTourist = ['access_token'];


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
            $where['zc.category_id'] = ['=', $categoryId];
            $where['zc.type'] = ['=', 1];
            $where['zc.is_del'] = ['=', 1];
            $where['m.is_del'] = ['=', 1];

        } else {
            //传入搜索内容
            //$where[] = ['zc.course_name|m.cname', 'like', "%" . $search . "%"];
            $where['zc.course_name'] = ['like', "%" . $search . "%"];
            $where['zc.type'] = ['=', 1];
            $where['zc.is_del'] = ['=', 1];
            $where['m.is_del'] = ['=', 1];
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
        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
            $table = 'zht_online_course';
            $where['zc.expiration_time'] = [">=", time()];
            $field = 'zc.enroll_num,zc.original_price,zc.id as course_id,zc.course_type,zc.course_img,zy.name,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        } else {
            //线下
            $table = 'zht_course';
            $field = 'zc.id as course_id,zc.course_type,zc.course_img,zy.name,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';

        }
        // halt($table);
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
        //halt(Db::name($table)->getLastSql());
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
                $item['course_category'] = $courseCategory;
                //获取课程课时报名人数
                if ($courseCategory == 2) {
                    $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
                    //$item['enroll_num'] = $enrollNum['enroll_num'];
                    $item['enroll_num'] = bcsub($enrollNum['surplus_num'], $enrollNum['enroll_num']);
                    if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                        $dis = bcdiv($item['discount'], 10, 2);
                        $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                    } else {
                        $item['present_price'] = $enrollNum['present_price'];
                    }

                } else {
                    //价格
                    if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                        $dis = bcdiv($item['discount'], 10, 2);
                        $price = bcmul($dis, $item['original_price'], 2);
                        $item['present_price'] = $price;
                        // $item['original_price'] = $price == $item['original_price'] ? "" : $item['original_price'];
                    } else {
                        //$item['original_price'] = "";
                        $item['present_price'] = $item['original_price'];
                    }
                    unset($item['original_price']);
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
            return returnResponse("1001", '无机构信息');
        }
        $courseId = $data['course_id'];

        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $courseCategory = input('post.course_category/d', 2);
        $where['zc.id'] = ['=', $courseId];
        $where['zc.is_del'] = ['=', 1];
        $where['m.is_del'] = ['=', 1];
        if ($courseCategory == 1) {
            //线上
            $table = "zht_online_course";
            $field = 'zc.course_img,zc.expiration_time,zc.id as course_id,zc.original_price,zc.discount_start_time,zc.enroll_num,zc.discount_end_time,zc.discount,zc.course_name,zc.teacher_id,zc.mem_id,zy.name,zc.course_details,zc.surplus_num,zc.start_age,zc.end_age,t.teacher_nickname,t.sex,t.brief,m.logo,m.longitude,m.latitude,m.province,m.city,m.area,m.address,m.cname,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
            $where['zc.expiration_time'] = ['>=', time()];
        } else {
            //线下
            $table = 'zht_course';
            $field = 'zc.id as course_id,zc.discount_start_time,zc.enroll_num,zc.discount_end_time,zc.discount,zc.course_wheel_img,zc.course_img,zc.course_name,zc.teacher_id,zc.mem_id,zy.name,zc.course_details,zc.surplus_num,zc.start_age,zc.end_age,t.teacher_nickname,t.sex,t.brief,m.logo,m.longitude,m.latitude,m.province,m.city,m.area,m.address,m.cname,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        }

        $join = [
            ['yx_teacher t', 'zc.teacher_id = t.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
        ];
        //, 'zc.type' => 1
        $result = Crud::getRelationData($table, 1, $where, $join, 'zc', '', $field);
        if (empty($result)) {
            return returnResponse("1002", "课程不存在");
        }
        $result['addr'] = $result['province'] . $result['city'] . $result['area'] . $result['address'];
        unset($result['province']);
        unset($result['city']);
        unset($result['area']);
        unset($result['address']);
        $result['teache_img'] = "";//固定
        $result['course_category'] = $courseCategory;
        //判断是否收藏
        $isCollection = Crud::getData('zht_collection', 1, ['user_id' => $this->userId, 'is_del' => 1, 'course_category' => $courseCategory, 'course_id' => $courseId], 'id');
        $result['is_collection'] = empty($isCollection) ? 0 : 1;

        //获取课程总报名人数与课程总人数  线下课
        if ($courseCategory == 2) {
            $result['banner'] = unserialize($result['course_wheel_img']);
            unset($result['course_wheel_img']);
            $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $courseId, 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num, IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
            $result['enroll_num'] = $enrollNum['enroll_num'];
            $result['surplus_num'] = $enrollNum['surplus_num'];
            if ($result['discount_start_time'] <= time() && $result['discount_end_time'] >= time()) {
                $dis = bcdiv($result['discount'], 10, 2);
                $price = bcmul($dis, $enrollNum['present_price'], 2);
                $result['present_price'] = $price;
                $result['original_price'] = $price == $enrollNum['present_price'] ? "" : $enrollNum['present_price'];
            } else {
                $result['original_price'] = "";
                $result['present_price'] = $enrollNum['present_price'];
            }
        } else {  //线上课
            $result["course_img"] = [$result['course_img']];
            //获取线上课程目录
            $wh = [
                "zvoc.online_course_id" => $courseId,
                'zvoc.is_del' => 1,
                'zvc.is_del' => 1,
            ];
            $jo = [
                ['yx_zht_video_catalog zvc', 'zvoc.video_catalog_id = zvc.id']
            ];
            $fi = "zvc.url,zvoc.id as video_online_course_id";
            $banner = Crud::getRelationData("zht_video_online_course", 1, $wh, $jo, 'zvoc', 'zvoc.id ASC', $fi);
            //halt(Db::name("zht_video_online_course")->getLastSql());
            $result['banner'] = empty($banner) ? [] : [$banner['url']];
            $result['video_online_course_id'] = empty($banner) ? "" : $banner['video_online_course_id'];
            //价格
            if ($result['discount_start_time'] <= time() && $result['discount_end_time'] >= time()) {
                $dis = bcdiv($result['discount'], 10, 2);
                $price = bcmul($dis, $result['original_price'], 2);
                $result['present_price'] = $price;
                $result['original_price'] = $price == $result['original_price'] ? "" : $result['original_price'];
            } else {
                $result['present_price'] = $result['original_price'];
                $result['original_price'] = "";

            }
        }

        unset($result['discount_start_time']);
        unset($result['discount_end_time']);
        unset($result['discount']);
        return returnResponse("1000", '', $result);
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
            $table = 'zht_online_course';
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
        //  halt($course['evaluate_num']);
        $course['score'] = floatval($course['score']);
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

        if ($courseCategory == 1) {
            //线上
            $table = 'zht_online_course';
        } else {
            //线下
            $table = 'zht_course';
            if (!$courseNumId = input('post.course_num_id')) {
                return returnResponse("1001", '请选择课时');
            }
        }
        //判断课程是否存在
        $course = Crud::getData($table, 1, ['id' => $courseId, 'is_del' => 1, 'type' => 1], 'discount_start_time,discount_end_time,discount,original_price');
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
        } else {
            if ($course['discount_start_time'] > time() || $course['discount_end_time'] < time()) {
                $disPrice = 0;
            } else {
                $dis = bcdiv($course['discount'], 10, 2);
                //$discount = number_format($courseNum['course_section_price'] * $course['discount'], 2);
                $discount = bcmul($course['original_price'], $dis, 2);
                $disPrice = bcsub($course['original_price'], $discount, 2);
            }
        }

        return returnResponse('1000', '', [
            'discount' => $disPrice
        ]);
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
                    $table = 'zht_online_course';
                    $field = 'zc.original_price,zc.course_img,zy.name,zc.course_name,zc.type,discount_start_time,discount_end_time,discount,enroll_num';
                } else {
                    //线下
                    $table = 'zht_course';
                    $field = 'zc.course_img,zy.name,zc.course_name,zc.type,discount_start_time,discount_end_time,discount';
                }
                $join = [
                    ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
                ];
                $where = [
                    'zc.id' => $item['course_id']
                ];

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
                } else {
                    $item['enroll_num'] = $course['enroll_num'];
                    if ($course['discount_start_time'] <= time() && $course['discount_end_time'] >= time()) {
                        $dis = bcdiv($course['discount'], 10, 2);
                        $item['present_price'] = bcmul($dis, $course['original_price'], 2);
                    } else {
                        $item['present_price'] = $course['original_price'];
                    }
                    unset($item['original_price']);
                }

            }
        }
        return returnResponse('1000', '', $list);
    }


    /**
     * @Notes: 获取课程目录
     * @Author: asus
     * @Date: 2020/7/1
     * @Time: 13:29
     * @Interface getVideoCatalog
     * @return string
     */
    public function getVideoCatalog()
    {
        if (!$courseId = input('post.course_id/d')) {
            return returnResponse('1001', '缺少参数');
        }
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [
            "zvoc.online_course_id" => $courseId,
            'zvoc.is_del' => 1,
            'zvc.is_del' => 1,
        ];
        $join = [
            ['yx_zht_video_catalog zvc', 'zvoc.video_catalog_id = zvc.id']
        ];
        $field = "zvoc.id,zvoc.online_course_type,zvc.section_name,zvc.url,zvc.video_time";
        $video = Crud::getRelationData("zht_video_online_course", 2, $where, $join, 'zvoc', 'zvoc.id ASC', $field, $page, $pageSize);
        //halt(Db::name("zht_video_online_course")->getLastSql());
        $count = Crud::getData("zht_video_online_course", 1, ['is_del' => 1, 'online_course_id' => $courseId], 'count(id) as count');
        $result['count'] = $count['count'];

        $course = Crud::getData("zht_online_course", 1, ['id' => $courseId, 'is_del' => 1], 'expiration_time');
        if (empty($course)) {
            return returnResponse('1001', '课程异常');
        }
        $result['expiration_time'] = date("Y-m-d", $course['expiration_time']);
        if (count($video) > 0) {
            //判断是否学习
            foreach ($video as &$item) {
                $record = Crud::getData("zht_video_online_course_record", 1, ['video_online_course_id' => $item['id'], 'user_id' => $this->userId, 'is_del' => 1, 'online_course_id' => $courseId], 'id');
                $item['status'] = empty($record) ? 2 : 1;

            }
        }
        $result['video'] = $video;
        return returnResponse("1000", '', $result);
    }

    /**
     * @Notes: 判断课节是否能播放
     * @Author: asus
     * @Date: 2020/7/1
     * @Time: 14:35
     * @Interface judgePlay
     * @return string
     */
    public function judgePlay()
    {
        if (!$courseId = input("post.course_id/d")) {
            return returnResponse('1001', '缺少参数');
        }
        if (!$id = input("post.id/d")) {
            return returnResponse('1001', '缺少参数');
        }
        //判断是否为试听课
        $video = Crud::getData("zht_video_online_course", 1, ['id' => $id, "online_course_id" => $courseId, 'is_del' => 1], 'online_course_type');
        if (empty($video)) {
            return returnResponse('1002', '视频异常', ["status" => 2]);
        }
        if ($video['online_course_type'] == 2) {
            //试听课
            return returnResponse('1000', '', ["status" => 1]);
        } else {
            //判断是否购买
            $order = Crud::getData("zht_order", 1, ['course_id' => $courseId, "user_id" => $this->userId, 'course_category' => 1, 'status' => 2, 'is_del' => 1], 'id');
            if (empty($order)) {
                return returnResponse('1002', '请购买视频', ["status" => 2]);
            }

            return returnResponse('1000', '', ["status" => 1]);
        }
    }

    /**
     * @Notes: 添加课节阅读记录
     * @Author: asus
     * @Date: 2020/7/1
     * @Time: 16:49
     * @Interface addLearningRecord
     * @return string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function addLearningRecord()
    {
        if (!$courseId = input("post.course_id/d")) {
            return returnResponse('1001', '缺少参数');
        }
        if (!$id = input("post.id/d")) {
            return returnResponse('1001', '缺少参数');
        }
        $data['user_id'] = $this->userId;
        $data['online_course_id'] = $courseId;
        $data['video_online_course_id'] = $id;
        $data['record_time'] = 0;

        //判断是否为试听课
        $video = Crud::getData("zht_video_online_course", 1, ['id' => $id, "online_course_id" => $courseId, 'is_del' => 1], 'online_course_type');
        if (empty($video)) {
            return returnResponse('1002', '视频异常');
        }
        if ($video['online_course_type'] == 2) {
            //试听课
            $record = Crud::getData("zht_video_online_course_record", 1, ['user_id' => $this->userId, 'online_course_id' => $courseId, 'video_online_course_id' => $id], 'id');
            if (empty($record)) {
                $add = Crud::setAdd("zht_video_online_course_record", $data);
                if (empty($add)) {
                    return returnResponse('1003', '添加阅读记录失败');
                }
            }

        } else {
            //判断是否购买
            $order = Crud::getData("zht_order", 1, ['course_id' => $courseId, "user_id" => $this->userId, 'course_category' => 1, 'status' => 2, 'is_del' => 1], 'id');
            if (empty($order)) {
                return returnResponse('1002', '请购买视频', ["status" => 2]);
            }

            $record = Crud::getData("zht_video_online_course_record", 1, ['user_id' => $this->userId, 'online_course_id' => $courseId, 'video_online_course_id' => $id], 'id');
            if (empty($record)) {
                $add = Crud::setAdd("zht_video_online_course_record", $data);
                if (empty($add)) {
                    return returnResponse('1003', '添加阅读记录失败');
                }
            }

        }
        return returnResponse('1000', '');
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
            return returnResponse("1001", '无机构信息');
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

        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
            $table = 'zht_online_course';
            $field = 'zc.enroll_num,zc.id as course_id,zc.course_img,zy.name,zc.present_price,zc.course_name,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';

        } else {
            //线下
            $table = 'zht_course';
            $field = 'zc.id as course_id,zc.course_img,zy.name,zc.present_price,zc.course_name,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';

        }

        $order = "distance ASC";
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
        return returnResponse('1000', '', $result);
    }

    public function getZiYue()
    {
        return returnResponse('1000', '', ['id' => 4412]);
    }

}