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
     *
     * 获取附近课程列表
     * $type 1是传经纬度，2是不传
     * $cid 为0没有分类，0.1时为传入学习子分类 条件为type 2
     * $mtype 1从机构详情进入列表
     */
    public static function getCourse($longitude = '', $latitude = '', $cid = '0', $cou_name = '', $type, $sts_id = '', $mtype = '', $mem_id = '', $page = 1)
    {
        if ($cid == 0) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
//                'm.type' => 3,
                'm.status' => 1,
                'ca.type' => 1,
                'ca.is_del' => 1,
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
                'ca.id' => $cid,
//                'cs.type' => 1,
//                'cs.is_del' => 1,
//                'cs.id' => $cid,
            ];
        }
        (isset($cou_name) && !empty($cou_name)) && $where['cu.name'] = ['like', '%' . $cou_name . '%'];
        if (empty($latitude) || empty($longitude)) {
            $latitude = '30.2741500000';
            $longitude = '120.1551500000';
        }
        if ($mtype == 1) { //从机构详情页跳过来的
            $where['m.uid'] = $mem_id;
        }
//        $table = 'course_copy';
        $table = request()->controller();
        $join = [
            ['yx_member m', 'c.mid = m.uid', 'left'],
            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
            ['yx_category ca', 'cu.cid = ca.id', 'left'],
            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
        ];
        $alias = 'c';
        if ($type == 1) {
            $field = ['c.id,c.img,c.mid,c.title,c.present_price,c.c_num,c.original_price,c.enroll_num,cu.name,c.recruit,c.start_age,c.end_age,c.end_time,m.cname,ca.name caname,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
            $order = 'distance';
        } elseif ($type == 2) {
            $field = ['c.id,c.img,c.mid,c.title,c.present_price,c.c_num,c.original_price,c.enroll_num,cu.name,c.recruit,m.cname,c.start_age,c.end_age,c.end_time,ca.name caname,c.aid,cl.longitude,cl.latitude'];
//            $order = 'fire';
            $order = '';
        }
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page);
        if (!$info) {
            throw new CourseMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['status'] = 1;
                if (!empty($v['img'])) {
                    $info[$k]['img'] = get_take_img($v['img']);
                }
                if ($v['c_num'] <= 0) {
                    $info[$k]['present_price'] = 0;
                    $info[$k]['original_price'] = 0;
                } else {
                    if ($v['present_price'] <= 0) {
                        $info[$k]['present_price'] = 0;
                    } else {
                        $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'], 2);
                    }
                    if ($v['original_price'] <= 0) {
                        $info[$k]['original_price'] = 0;
                    } else {
                        $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'], 2);
                    }
                }
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
//                unset($k['start_age']);
//                unset($k['end_age']);
            }
            return jsonResponse('1000', '成功获取附近课程列表', $info);
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
        $field = ['c.id,c.c_num,c.img,c.mid,c.title,c.present_price,c.c_num,c.original_price,c.enroll_num,c.name,c.recruit,m.cname,ca.name caname,c.recom,m.service_student,m.found_time,m.kf_phone,c.arrange_time,m.is_verification'];
        $order = 'c.sort asp';
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, $pageSize);
        if (!$info) {
            throw new CourseMissException();
        } else {
            foreach ($info as $k => $v) {
                if ($v['present_price'] > 0) {
                    $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'], 2);
                    $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'], 2);
                }
            }
            return jsonResponse('1000', '成功获取推荐机构及课程', $info);
        }
    }

    /**
     * 获取课程详情页 （普通课，体验课，秒杀课，社区活动课，等以后会有拼团之类的课程）
     * cou_id 课程ID
     * seckill_id 秒杀课程ID
     * experience_id 体验课程ID
     * community_id 活动课程ID
     * status 1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课程
     */
    public static function getCourseDetails()
    {  //yx_seckill_course 秒杀课程  yx_experience_course 体验课程 yx_community_course 活动课程
        $data = input();
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $data['latitude'] = '30.2741500000';
            $data['longitude'] = '120.1551500000';
        }
        //判断课程是否正常
        (new CourseIDSMustBePostiveInt())->goCheck();
        //判断是哪种类型的课进详情页
//        $where1 = [
//            'c.is_del' => 1,
//            'c.type' => 1,
//            'm.is_del' => 1,
//            'm.status' => 1,
//        ];
        if ($data['status'] == 4) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'],
                ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
            ];
            $field = ['sc.id cou_id,cl.name clname, sc.start_age,sc.end_age,cu.wheel_img,sc.img,sc.mid,cu.name,cu.details,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,cl.address,m.logo,m.img mimg,cl.longitude,cl.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,cl.province,cl.city,cl.area,sc.arrange_time,m.kf_phone,m.found_time,m.service_student,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
            (new CourseIDSMustBePostiveInt())->goCheck();
            $alias = 'sc';
            $table = 'seckill_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

//            dump('秒杀课程进入详情');
            //这是秒杀课程进入详情
        } elseif ($data['status'] == 2) {
            $where1 = [
                'ec.is_del' => 1,
                'ec.type' => 1,
                'ec.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'ec.mid = m.uid', 'left'],
                ['yx_classroom cl', 'ec.classroom_id = cl.id', 'left'],
            ];
            $field = ['ec.id cou_id,cl.name clname,ec.start_age,ec.end_age,cu.wheel_img,ec.img,ec.mid,cu.name,cu.details,ec.title,ec.present_price,ec.original_price,ec.enroll_num,ec.surplus_num,ec.start_time,ec.end_time,ec.c_num,m.cname,cl.address,m.logo,m.img mimg,cl.longitude,cl.latitude,m.course_num,m.logo,m.give_type,m.uid,m.ismember,m.balance,m.wheel_img mwheel_img,m.remarks,m.introduction,cl.province,cl.city,cl.area,ec.arrange_time,m.kf_phone,m.found_time,m.service_student,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
            $alias = 'ec';
            $table = 'experience_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump($info_course);exit;
//            dump('体验课程进入详情');
            //这是体验课程进入详情
        } elseif ($data['status'] == 3) {
            $where1 = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],//社区课目
                ['yx_community_classroom cl', 'cc.classroom_id = cl.id', 'left'], //社区教室
                ['yx_community_name cn', 'cc.community_id = cn.id', 'left'], //社区教室
            ];
            $field = 'cc.id cou_id,cl.name clname,cc.img,cu.wheel_img,cu.name,cu.details,cc.community_id,cc.title,cc.start_age,cc.end_age,cc.original_price,cc.enroll_num,cc.start_time,cc.end_time,cc.c_num,cc.present_price,cc.surplus_num,cc.by_time,cl.latitude,cl.longitude,cl.province,cl.city,cl.area,cl.address,cc.arrange_time,cn.kf_phone,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance';
            $alias = 'cc';
            $table = 'community_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('活动课程进入详情');
            //这是活动课程进入详情
        } elseif ($data['status'] == 1) {
            $where1 = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_member m', 'c.mid = m.uid', 'left'],
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
            ];
            $field = ['c.id cou_id,cl.name clname,cu.wheel_img,c.img,c.mid,cu.name,cu.details,c.title,c.aid,c.present_price,c.start_age,c.end_age,c.original_price,c.enroll_num,c.surplus_num,c.start_time,c.end_time,c.c_num,m.cname,cl.address,m.logo,m.img mimg,cl.longitude,cl.latitude,m.course_num,m.wheel_img mwheel_img,m.remarks,m.introduction,cl.province,cl.city,cl.area,c.arrange_time,m.kf_phone,m.found_time,m.service_student,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
//            dump('普通课程');
            (new CourseIDSMustBePostiveInt())->goCheck();
            $alias = 'c';
            $table = request()->controller();
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        } elseif ($data['status'] == 5) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cou_id'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'], //机构
                ['yx_classroom scm', 'sc.classroom_id = scm.id', 'left'], //教室
                ['yx_synthetical_name sn', 'sc.syntheticalcn_id = sn.id', 'left'], //社区
            ];
            $field = ['sc.id cou_id,scm.name clname,sc.start_age,sc.end_age,sc.syntheticalcn_id,cu.wheel_img,cu.details,sc.img,sc.mid,cu.name,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,scm.address,m.logo,m.img mimg,scm.longitude,scm.latitude,m.course_num,m.wheel_img mwheel_img,m.remarks,m.introduction,scm.province,scm.city,scm.area,sc.arrange_time,sn.kf_phone,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-scm.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(scm.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-scm.longitude*PI()/180)/2),2)))*1000) AS distance'];
            $alias = 'sc';
            $table = 'synthetical_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

//            dump('活动课程进入详情');
            //这是活动课程进入详情
        }
        if (!$info_course) {
            throw new CourseMissException();
        } else {

            //计算单价
            if ($info_course['present_price'] > 0) {
                $info_course['unit_price'] = round($info_course['present_price'] / $info_course['c_num'], 2);
            }

//            $info_course['original_price'] = round($info_course['original_price'] / $info_course['c_num'],2);
//            if ($data['status'] == 2) {
//                $info_course = Crud::Nums($info_course, 1);
//            }
            //将年龄ID字符串变为数组
            $info_course['age_name'] = $info_course['start_age'] . '~' . $info_course['end_age'];
            $info_course['status'] = $data['status'];

            //判读是否是序列化字符串
            if (!empty($info_course['wheel_img']) && is_serialized($info_course['wheel_img'])) {
                $info_course['wheel_img'] = get_take_img($info_course['wheel_img']);
            }
            if (!empty($info_course['mwheel_img']) && is_serialized($info_course['mwheel_img'])) {
                $mwheel_img = get_take_img($info_course['mwheel_img']);
                $info_course['mwheel_img'] = $mwheel_img[0];
            }else{
                if(isset($info_course['mimg'])){
                    $info_course['mwheel_img'] = $info_course['mimg'];
                }
            }
            if (!empty($info_course['logo']) && is_serialized($info_course['logo'])) {
                $info_course['logo'] = get_take_img($info_course['logo']);
            }
            if (!empty($info_course['img']) && is_serialized($info_course['img'])) {
                $info_course['img'] = get_take_img($info_course['img']);
            }
            $info_course['type_id'] = 2;

            return jsonResponse('1000', '成功获取课程详情页', $info_course);
        }
    }

}