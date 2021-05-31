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
 * 专属课程
 */
class Exclusive extends Base
{
    protected $exceptTicket = ["getExclusiveActivity", 'getExclusiveMember', 'getExclusiveCourse'];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 获取专属活动
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 17:04
     * @Interface getExclusiveActivity
     * @return string
     */
    public function getExclusiveActivity()
    {
        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $where['za.activity_start_time'] = ['<=', time()];
        $where['za.activity_end_time'] = ['>=', time()];
        $where['za.is_recommend'] = ['=', 1];
        $where['za.is_del'] = ['=', 1];
        $where['za.status'] = ['=', 2];
        $where['m.is_del'] = ['=', 1];
        $where['m.is_exclusive'] = ['=', 2];
        $join = [
            ['yx_member m', 'za.mem_id = m.uid']
        ];
        $field = "za.id,za.activity_img,za.activity_enroll_num,za.activity_title,za.activity_type,za.activity_price,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance";
        $activity = Crud::getRelationData('zht_activity', 2, $where, $join, 'za', 'distance ASC', $field, 1, 8);
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


        return returnResponse('1000', '', $activity);
    }

    /**
     * @Notes: 获取专属机构
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 17:40
     * @Interface getExclusiveMember
     * @return string
     */
    public function getExclusiveMember()
    {
        $result = Crud::getDataunpage("member", 2, ['is_exclusive' => 2, 'is_del' => 1], "uid as mem_id,cname,province,city,area,address");
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
            }
        }
        $results = [
            'result' => $result
        ];
        return returnResponse('1000', '', $results);
    }

    public function getExclusiveCourse()
    {
        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;

        $categoryId = input('post.category_id');
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $type = input('post.type/d', 1);
        $where = [];
        if (!empty($categoryId)) {
            $where['zc.category_id'] = $categoryId;
        }
        if ($type == 2) {
            $where['zc.price_type'] = ['=', 2];
        }
        $memIds = input('post.mem_ids/a', []);
        if (count($memIds) > 0) {
            $where['m.uid'] = ['in', $memIds];
        }
        // $price = input('post.price/d');
        // if ($price == 2) {
        //     $where['zc.price_type'] = ['=', 2];
        // }
        $where['m.is_exclusive'] = ['=', 2];
        $where['m.is_del'] = ['=', 1];
        $where['zc.type'] = ['=', 1];
        $where['zc.is_del'] = ['=', 1];
        $where['zc.activity_type'] = ['=', 2];
        $courseCategory = input('post.course_category/d', 2);
        if ($courseCategory == 1) {
            //线上
            $table = 'zht_online_course';
            $field = 'zc.enroll_num,zc.original_price,zc.id as course_id,zc.course_type,zc.course_img,zy.name,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';

        } else {
            //线下
            $table = 'zht_course';
            $field = 'zc.id as course_id,zc.course_type,zc.course_img,zy.name,zc.discount_start_time,zc.discount_end_time,zc.discount,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';

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
        //halt(Db::name($table)->getLastSql());
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $item['course_category'] = $courseCategory;
                //获取课程课时报名人数
                if ($courseCategory == 2) {
                    $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
                    $item['enroll_num'] = bcsub($enrollNum['surplus_num'],$enrollNum['enroll_num']);
                    $item['course_category'] = $courseCategory;
                    if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                        $dis = bcdiv($item['discount'], 10, 2);
                        $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                    } else {
                        $item['present_price'] = $enrollNum['present_price'];
                    }
                }else{
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
}