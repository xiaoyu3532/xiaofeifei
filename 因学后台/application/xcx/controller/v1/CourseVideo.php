<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 11:00
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CourseMissException;
use app\lib\exception\NothingMissException;
use app\validate\CourseIDSMustBePostiveInt;

class CourseVideo
{
    /**
     *
     * 获取附近视频课程列表
     * $type 1是传经纬度，2是不传
     * $cid 为0没有分类，0.1时为传入学习子分类 条件为type 2
     * $mtype 1从机构详情进入列表
     */
    public static function getCourseVideo($longitude = '', $latitude = '', $cid = '0', $cou_name = '', $type, $sts_id = '', $mtype = '', $mem_id = '', $page = 1)
    {
        if ($cid == 0) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
//                'm.type' => 3,
                'm.status' => 1,
//                'ca.type' => 1,
//                'ca.is_del' => 1,
            ];
        } elseif ($cid == 0.1) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.sts_id' => $sts_id,
//                'm.type' => 3,
                'm.status' => 1,
            ];
        } else {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
//                'm.type' => 3,
                'm.status' => 1,
                'ca.type' => 1,
                'ca.is_del' => 1,
                'c.curriculum_cid' => $cid,
//                'cs.type' => 1,
//                'cs.is_del' => 1,
//                'cs.id' => $cid,
            ];
        }
        (isset($cou_name) && !empty($cou_name)) && $where['c.name'] = ['like', '%' . $cou_name . '%'];
        if (empty($latitude) || empty($longitude)) {
            $latitude = '30.2741500000';
            $longitude = '120.1551500000';
        }
        if ($mtype == 1) { //从机构详情页跳过来的
            $where['m.uid'] = $mem_id;
        }
//        dump($where);exit;
//        $table = 'course_copy';
        $table = request()->controller();
        $join = [
            ['yx_member m', 'c.mem_id = m.uid', 'left'],
//            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
            ['yx_category ca', 'c.curriculum_cid = ca.id', 'left'],
//            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
        ];
        $alias = 'c';
        if ($type == 1) {
            $field = ['c.id,c.mem_id,c.create_time,c.sort,c.name,c.password,c.title,c.present_price,c.c_num,c.original_price,c.introduction,c.cover_img,ca.name caname,m.longitude,m.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
            $order = 'c.sort  desc';
        } elseif ($type == 2) {
            $field = ['c.id,c.mem_id,c.create_time,c.sort,c.name,c.password,c.title,c.present_price,c.c_num,c.original_price,c.introduction,c.cover_img,ca.name caname,m.longitude,m.latitude'];
//            $order = 'fire';
            $order = 'c.sort desc';
        }
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page,100);
        if (!$info) {
            throw new CourseMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['status'] = 1;
                if (!empty($v['cover_img'])) {
                    $info[$k]['cover_img'] = get_take_img($v['cover_img']);
                }
                if (!empty($v['password'])) {
                    $info[$k]['is_password']=1;
                }else{
                    $info[$k]['is_password']=2;
                }
            }
            return jsonResponse('1000', '成功获取附近课程列表', $info);
        }
    }

    /**
     * 获取视频大纲
     * @param $video_id
     * @param int $page
     * @param int $pageSize
     * @return string
     * @throws NothingMissException
     */
    public static function getVideo($video_id, $page = 1, $pageSize = 100)
    {
        $where = [
            'v.type' => 1,
            'v.is_del' => 1,
            'v.course_video_id' => $video_id
        ];
        $table = 'video';
        $join = [
            ['yx_course_video cv', 'v.course_video_id = cv.id', 'left'],
            ['yx_category ca', 'cv.curriculum_cid = ca.id', 'left'],
        ];
        $alias = 'v';
        $field = ['v.id,v.name,v.mem_id,v.duration,v.course_video_id,v.cover_img,v.sort,v.video,v.browse_num,v.create_time,v.introduction,ca.name caname'];
        $order = 'v.sort asc';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                if (!empty($v['cover_img'])) {
                    $info[$k]['cover_img'] = get_take_img($v['cover_img']);
                }
                $info[$k]['serial_num'] = $k+1;
            }
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }

    }

    /**
     * 验证视频密码
     * @param $video_id
     * @param $password
     */
    public static function ispassword($video_id, $password)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $video_id,
        ];
        $table = request()->controller();
        $CourseVideo_data = Crud::getData($table, 1, $where, 'password');
        if ($CourseVideo_data) {
            if ($CourseVideo_data['password'] == $password) {
                return jsonResponseSuccess(1);
            } else {
                return jsonResponse(2000, '密码错误');
            }
        } else {
            return jsonResponse(2000, '密码错误');
        }

    }

    /**
     * 返回播放视频
     * @param $course_video_id
     * @param $video_id
     */
    public static function getPlayVideo($video_id,$id=''){
        $where = [
            'v.is_del'=>1,
            'v.type'=>1,
            'v.course_video_id'=>$video_id,
        ];
        (isset($id) && !empty($id)) && $where['v.id'] = $id;
        $table = 'video';
        $join = [
            ['yx_course_video cv', 'v.course_video_id = cv.id', 'left'],
        ];
        $alias = 'v';
        $field = ['v.*,cv.c_num'];
        $order = 'v.sort asc';
        $info = Crud::getRelationData($table, 1, $where, $join, $alias, $order, $field);
        if($info){
            if (!empty($info['cover_img'])) {
                $info['cover_img'] = get_take_img($info['cover_img']);
            }
            if (!empty($info['video'])) {
                $info['video'] = get_take_video($info['video']);
            }
            return jsonResponseSuccess($info);
        }else{
            throw new NothingMissException();
        }
    }

    /**
     * 获取视频课程详情页 （普通课，体验课，秒杀课，社区活动课，等以后会有拼团之类的课程）
     * cou_id 课程ID
     * seckill_id 秒杀课程ID
     * experience_id 体验课程ID
     * community_id 活动课程ID
     * status 1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课程
     */
    public static function getCourseVideoDetails($video_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $video_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'name,cover_img,c_num,introduction');
        if (!$info) {
            throw new CourseMissException();
        } else {
            //判读是否是序列化字符串
            if (!empty($info['cover_img'])) {
                $info['cover_img'] = get_take_img($info['cover_img']);
            }
            return jsonResponse('1000', '成功获取视频课程详情页', $info);
        }
    }

}