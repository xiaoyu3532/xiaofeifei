<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/22 0022
 * Time: 13:55
 */

namespace app\pc\controller\v2;


use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\pc\controller\v1\BaseController;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class ZhtOnlineCourse extends BaseController
{
    const table_zht_online_course = 'zht_online_course';
    const table_zht_video = 'zht_video';
    const table_zht_video_catalog = 'zht_video_catalog';

    //获取线上课程
    public static function getZhtOnlineCourse($course_name = '', $mem_id = '', $page = 1, $pageSize = 8)
    {
        $where = [
            'zoc.is_del' => 1
        ];
        if (empty($mem_id)) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $where['mem_id'] = ['in', $mem_ids];
        } else {
            $where['mem_id'] = $mem_id;
        }

        //学科分类
        if (isset($category_ids) && !empty($category_ids)) {
            $where['zoc.category_id'] = $category_ids[0];
            $where['zoc.category_small_id'] = $category_ids[1];
        }
        (isset($course_name) && !empty($course_name)) && $where['zoc.course_name'] = ['like', '%' . $course_name . '%']; //课程名
        $join = [
            ['yx_member m', 'zoc.mem_id = m.id', 'left'],  //机构表
            ['yx_zht_category zc', 'zoc.category_id = zc.id', 'left'],  //一级分类
            ['yx_category_small cs', 'zoc.category_small_id = cs.id', 'left'],  //二级分类
        ];
        $alias = 'zoc';
        $table = self::table_zht_online_course;
        $online_course_info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zoc.create_time desc', $field = 'zoc.*,m.cname,zc.name category_name,cs.category_small_name', $page, $pageSize);
        if ($online_course_info) {
            foreach ($online_course_info as $k => $v) {
                $online_course_info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $online_course_info[$k]['course_time'] = date('Y-m-d H:i:s', $v['course_start_time']) . '-' . date('Y-m-d H:i:s', $v['course_end_time']);
                $online_course_info[$k]['category_array_name'] = $v['category_name'] . '-' . $v['category_small_name'];
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zoc.id');
            $info_data = [
                'info' => $online_course_info,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }


    //添加线上课程

    /**
     * course_img 课程图片 封面图
     * course_name 课程标题
     * title  课程简介
     * original_price 原价
     * course_details  课程详情
     * category_ids  一级类目，二级类目
     * course_wheel_img 轮播图
     * start_age 最小年龄
     * end_age 最大年龄
     * discount 折扣
     * discount_time 折扣时间
     * course_keyword 关键字
     * course_wheel_img 轮播图
     * video_array 视频
     */
    public static function addZhtOnlineCourse()
    {
        $data = input();
        $Account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $Account_data['mem_id'];
        }

        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }

        if (isset($data['video_array']) && !empty($data['video_array'])) {
            $data['video_id'] = $data['video_array'][2];
        }

        //discount
        if (!isset($data['discount']) || empty($data['discount']) || $data['discount'] == 0) {
            $data['discount'] = 10;
        }

        if (isset($data['discount_time']) && !empty($data['discount_time'])) {
            $data['discount_start_time'] = $data['discount_time'][0] / 1000;
            $data['discount_end_time'] = $data['discount_time'][1] / 1000;
            if ($data['discount_start_time'] < time() && $data['discount_end_time'] > time()) {
                $data['present_price'] = $data['original_price'] * $data['discount'];
            } else {
                $data['present_price'] = $data['original_price'];
            }
        } else {
            $data['discount_start_time'] = time();
            $data['discount_end_time'] = time() + 31536000;
            $data['present_price'] = $data['original_price'];
        }

        if (isset($data['course_time']) && !empty($data['course_time'])) {
            $data['course_start_time'] = $data['course_time'][0] / 1000;
            $data['course_end_time'] = $data['course_time'][1] / 1000;
        } else {
            $data['course_start_time'] = time();
            $data['course_end_time'] = time() + 31536000;
        }

        if ($data['present_price'] == 0) {//1正常 2有免费
            $data['price_type'] = 2;
        }


        if (isset($data['course_wheel_img']) && !empty($data['course_wheel_img'])) { //轮播图
            $data['course_wheel_img'] = handle_img_deposit($data['course_wheel_img']);
        }
        //获取最低价格前台展示价格
        $table = self::table_zht_online_course;
        $online_course_id = Crud::setAdd($table, $data, 2);
        if (!$online_course_id) {
            throw new AddMissException();
        }
    }

    //修改课程 传online_course_id
    public static function editZhtOnlineCourse()
    {
        $data = input();
        $Account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $Account_data['mem_id'];
        }

        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }

        if (isset($data['video_array']) && !empty($data['video_array'])) {
            $data['video_id'] = $data['video_array'][2];
        }

        //discount
        if (!isset($data['discount']) || empty($data['discount']) || $data['discount'] == 0) {
            $data['discount'] = 10;
        }

        if (isset($data['discount_time']) && !empty($data['discount_time'])) {
            $data['discount_start_time'] = $data['discount_time'][0] / 1000;
            $data['discount_end_time'] = $data['discount_time'][1] / 1000;
            if ($data['discount_start_time'] < time() && $data['discount_end_time'] > time()) {
                $data['present_price'] = $data['original_price'] * $data['discount'];
            } else {
                $data['present_price'] = $data['original_price'];
            }
        } else {
            $data['discount_start_time'] = time();
            $data['discount_end_time'] = time() + 31536000;
            $data['present_price'] = $data['original_price'];
        }

        if (isset($data['course_time']) && !empty($data['course_time'])) {
            $data['course_start_time'] = $data['course_time'][0] / 1000;
            $data['course_end_time'] = $data['course_time'][1] / 1000;
        } else {
            $data['course_start_time'] = time();
            $data['course_end_time'] = time() + 31536000;
        }


        if (isset($data['course_wheel_img']) && !empty($data['course_wheel_img'])) { //轮播图
            $data['course_wheel_img'] = handle_img_deposit($data['course_wheel_img']);
        }
        //获取最低价格前台展示价格
        $table = self::table_zht_online_course;
        $online_course_id = Crud::setUpdate($table, ['id' => $data['online_course_id']], $data);
        if (!$online_course_id) {
            throw new AddMissException();
        }
    }

    //删除课程 传online_course_id
    public static function delZhtOnlineCourse($online_course_id)
    {
        $table = self::table_zht_online_course;
        $online_course_id = Crud::setUpdate($table, ['id' => $online_course_id], ['is_del' => 2]);
        if (!$online_course_id) {
            throw new D();
        }
    }

    //获取视频名称
    public static function getZhtVideo($video_name = '', $category_ids = '', $mem_id = '', $page = 1, $pageSize = 8)
    {
        $where = [
            'zv.is_del' => 1
        ];
        if (empty($mem_id)) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $where['mem_id'] = ['in', $mem_ids];
        } else {
            $where['mem_id'] = $mem_id;
        }

        //学科分类
        if (isset($category_ids) && !empty($category_ids)) {
            $where['zv.category_id'] = $category_ids[0];
            $where['zv.category_small_id'] = $category_ids[1];
        }
        (isset($video_name) && !empty($video_name)) && $where['zv.video_name'] = ['like', '%' . $video_name . '%']; //课程名
        $join = [
            ['yx_member m', 'zv.mem_id = m.id', 'left'],  //机构表
            ['yx_zht_category zc', 'zv.category_id = zc.id', 'left'],  //一级分类
            ['yx_category_small cs', 'zv.category_small_id = cs.id', 'left'],  //二级分类
        ];
        $alias = 'zv';
        $table = self::table_zht_video;
        $video_info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zv.create_time desc', $field = 'zv.*,m.cname,m.province,m.city,m.area,m.address,zc.name category_name,cs.category_small_name', $page, $pageSize);
        if ($video_info) {
            foreach ($video_info as $k => $v) {
//                $video_info[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
                $video_info[$k]['mcaddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $video_info[$k]['category_array_name'] = $v['category_name'] . '-' . $v['category_small_name'];
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zv.id');
            $info_data = [
                'info' => $video_info,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new AddMissException();
        }


    }

    //获取视频名称字段
    public function getZhtVideoField()
    {
        $arrye = [
            ['video_name', '视频文件夹名称'],
            ['category_array_name', '科目分类'],
            ['cname', '所属机构'],
            ['mcaddress', '机构地址'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加视频
    public static function addZhtVideo()
    {
        $data = input();
        $table = self::table_zht_video;
        $Account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $Account_data['mem_id'];
        }
        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }


    }

    //修改视频 传$data['video_id']
    public static function editZhtVideo()
    {
        $data = input();
        $table = self::table_zht_video;
        $Account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $Account_data['mem_id'];
        }
        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }
        $info = Crud::setUpdate($table, ['id' => $data['video_id']], $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }


    }

    //删除视频 传$data['video_id']
    public static function delZhtVideo($video_id)
    {
        $table = self::table_zht_video;
        $video_data = Crud::setUpdate($table, ['id' => $video_id], ['is_del' => 2]);
        if (!$video_data) {
            throw new DelMissException();
        }
        $table_zht_video_catalog = self::table_zht_video_catalog;
        $video_catalog_data = Crud::setUpdate($table_zht_video_catalog, ['video_id' => $video_id], ['is_del' => 2]);
        if (!$video_catalog_data) {
            throw new DelMissException();
        }

    }

    //获取视频目录
    public static function getZhtVideoCatalog($video_id, $page = 1, $pageSize = 8)
    {
        $where = [
            'id' => $video_id,
            'is_del' => 1
        ];
        $table = self::table_zht_video_catalog;
        $info = Crud::getData($table, 2, $where, '*', 'id desc', $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                $info[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $num = Crud::getCount($table, $where);
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new AddMissException();
        }
    }

    //获取视频目录字段
    public function getZhtVideoCatalogField()
    {
        $arrye = [
            ['section_name', '视频名称'],
            ['create_time_Exhibition', '上传时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加视频目录
    public static function addZhtVideoCatalog()
    {
        $data = input();
        $table = self::table_zht_video_catalog;
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //修改视频目录
    public static function eidtZhtVideoCatalog()
    {
        $data = input();
        $table = self::table_zht_video_catalog;
        $info = Crud::setUpdate($table, ['id' => $data['video_catalog_id']], $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //删除视频目录
    public static function delZhtVideoCatalog($video_catalog_id)
    {
        $table = self::table_zht_video_catalog;
        $video_data = Crud::setUpdate($table, ['id' => $video_catalog_id], ['is_del' => 2]);
        if (!$video_data) {
            throw new DelMissException();
        }
    }


}