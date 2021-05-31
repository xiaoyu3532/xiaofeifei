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
use app\validate\CourseIDSMustBePostiveInt;

class Course
{
    /**
     * 获取附近课程列表
     * $type 1是传经纬度，2是不传
     * $cid 为0没有分类，0.1时为传入学习子分类 条件为type 2
     * $mtype 1从机构详情进入列表
     */
    public static function getCourse($longitude='', $latitude='', $cid = '0', $cou_name = '', $type, $sts_id = '', $mtype = '', $mem_id='')
    {
        if ($cid == 0) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'm.type' => 3,
                'm.status' => 1,
                'ca.type' => 1,
                'ca.is_del' => 1,
            ];
        } elseif ($cid == 0.1) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.sts_id' => $sts_id,
                'm.type' => 3,
                'm.status' => 1,
            ];
        } else {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'm.type' => 3,
                'm.status' => 1,
                'ca.type' => 1,
                'ca.is_del' => 1,
                'ca.id' => $cid,
//                'cs.type' => 1,
//                'cs.is_del' => 1,
//                'cs.id' => $cid,
            ];
        }
        (isset($cou_name) && !empty($cou_name)) && $where['c.name'] = ['like', '%' . $cou_name . '%'];
        if ($mtype == 1) { //从机构详情页跳过来的
            $where['m.uid'] = $mem_id;
        }
        $table = request()->controller();
        $join = [
            ['yx_member m', 'c.mid = m.uid', 'left'],
            ['yx_category ca', 'c.cid = ca.id', 'left']
        ];
        $alias = 'c';
        if ($type == 1) {
            $field = ['c.id,c.img,c.title,c.present_price,c.original_price,c.enroll_num,c.name,c.recruit,c.aid,m.cname,ca.name caname,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
            $order = 'distance';
        } elseif ($type == 2) {
            $field = ['c.id,c.img,c.title,c.present_price,c.original_price,c.enroll_num,c.name,c.recruit,m.cname,ca.name caname,c.aid'];
            $order = 'fire';
        }
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, $pageSize);
        if (!$info) {
            throw new CourseMissException();
        } else {
            $info = Crud::getage($info, 2);
            foreach ($info as $k => $v) {
                $info[$k]['status'] = 1;
            }
            return jsonResponse('1000','成功获取附近课程列表',$info);
        }
    }

    /**
     * 获取推荐机构及课程
     * @throws CourseMissException
     * @throws \Exception
     * 要修改备用
     */
    public static function getCourserecom()
    {
        $where = [
            'c.is_del' => 1, //1正常，2删除
            'c.type' => 1, //1正常，2禁用
            'c.recom' => 1, //1推荐，2正常
            'm.type' => 3,
            'm.status' => 1,
            'ca.type' => 1,
            'ca.is_del' => 1,
        ];
        $table = request()->controller();
        $join = [
            ['yx_member m', 'c.mid = m.uid', 'left'],
            ['yx_category ca', 'c.cid = ca.id', 'left']
        ];
        $alias = 'c';
        $field = ['c.id,c.img,c.title,c.present_price,c.original_price,c.enroll_num,c.name,c.recruit,m.cname,ca.name caname,c.recom'];
        $order = 'c.sort asp';
//        $page = max(input('param.page/d', 1), 1);
//        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page = 1, $pageSize = 2);
        if (!$info) {
            throw new CourseMissException();
        } else {
            return jsonResponse('1000','成功获取推荐机构及课程',$info);
        }
    }

    /**
     * 获取课程详情页 （普通课，体验课，秒杀课，社区活动课，等以后会有拼团之类的课程）
     * cou_id 课程ID
     * seckill_id 秒杀课程ID
     * experience_id 体验课程ID
     * community_id 活动课程ID
     */
    public static function getCourseDetails()
    {  //yx_seckill_course 秒杀课程  yx_experience_course 体验课程 yx_community_course 活动课程
        $data = input();
        //判断课程是否正常
        (new CourseIDSMustBePostiveInt())->goCheck();
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $data['cou_id']
        ];
        $table = request()->controller();
        //验证课程
        $info = Crud::getData($table, $type = 1, $where, $field = 'id');
        if (!$info) {
            throw new CourseMissException();
        }
        //判断是哪种类型的课进详情页
        $where1 = [
            'c.is_del' => 1,
            'c.type' => 1,
            'm.type' => 3,
            'm.is_del' => 1,
            'm.status' => 1,
        ];
        if ((isset($data['seckill_id']) && !empty($data['seckill_id']))) {
            $where['seck.id'] = $data['seckill_id'];
            $where['seck.is_del'] = 1;
            $where['seck.type'] = 1;
            $join = [
                ['yx_seckill_course seck', 'c.id = seck.c_id', 'left'],
                ['yx_member m', 'c.mid = m.uid', 'left'],
            ];
            $field = 'c.img,c.name,c.title,c.aid,c.original_price,c.enroll_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,m.longitude,m.latitude,m.course_num,m.img mimg,m.remarks,seck.price,seck.num surplus_num';
            dump('秒杀课程进入详情');
            //这是秒杀课程进入详情
        } elseif ((isset($data['experience_id']) && !empty($data['experience_id']))) {
            $where['expe.id'] = $data['experience_id'];
            $where['expe.is_del'] = 1;
            $where['expe.type'] = 1;
            $join = [
                ['yx_experience_course expe', 'c.id = expe.c_id', 'left'],
                ['yx_member m', 'c.mid = m.uid', 'left'],
            ];
            $field = 'c.img,c.name,c.title,c.aid,c.original_price,c.enroll_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,m.longitude,m.latitude,m.course_num,m.img mimg,m.remarks,m.balance,m.give_type,m.ismember,expe.price';

            dump('体验课程进入详情');
            //这是体验课程进入详情
        } elseif ((isset($data['community_id']) && !empty($data['community_id']))) {
            $where['comm.id'] = $data['community_id'];
            $where['comm.is_del'] = 1;
            $where['comm.type'] = 1;
            $join = [
                ['yx_community_course comm', 'c.id = comm.c_id', 'left'],
                ['yx_member m', 'c.mid = m.uid', 'left'],
            ];
            $field = 'c.img,c.name,c.title,c.aid,c.original_price,c.enroll_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,m.longitude,m.latitude,m.course_num,m.img mimg,m.remarks,comm.price,comm.num surplus_num,comm.by_time';
            dump('活动课程进入详情');
            //这是活动课程进入详情
        } else {
            $where1['c.id'] = $data['cou_id'];
            $join = [
                ['yx_member m', 'c.mid = m.uid', 'left'],
            ];
            $field = 'c.img,c.name,c.title,c.aid,c.present_price,c.original_price,c.enroll_num,c.surplus_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,m.longitude,m.latitude,m.course_num,m.img mimg,m.remarks';
            dump('普通课程');
        }
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        if (!$info) {
            throw new CourseMissException();
        } else {
            //如果是体验课算库存
            if ((isset($data['experience_id']) && !empty($data['experience_id']))) {
                $info = Crud::Nums($info, 1);
            }
            //将年龄ID字符串变为数组
            $info = Crud::getage($info, 1);
            return jsonResponse('1000','成功获取课程详情页',$info);
        }


    }

}