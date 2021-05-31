<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/11 0011
 * Time: 15:38
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\common\controller\BaseController;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\IsTime;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Course extends BaseController
{
    //获取课程列表
    public static function getjgCourse($page = '1', $name = '', $cg_id = '', $st_id = '', $type = '', $course_status = '', $mem_id = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'c.is_del' => 1,
//                'c.type' => 1,
//                'c.mid' => $mem_data['mem_id'],
//            'ca.type'=>1,
//            'ca.is_del'=>1,
//            'st.type'=>1,
//            'st.is_del'=>1,
            ];
        } else {
            throw new ISUserMissException();
        }
        $mem_ids = bindingMember::getbindingjgMemberId();
        if (isset($mem_id) && !empty($mem_id)) {
            //验证传过的机构ID
            $isbindingjgMember = bindingMember::isbindingjgMember($mem_id);
            if ($isbindingjgMember != 1000) {
                return $isbindingjgMember;
            }
            $where['c.mid'] = $mem_id;
        } else {
            $where['c.mid'] = ['in', $mem_ids];
        }

        //名称搜索
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        //学科大分类
        (isset($cg_id) && !empty($cg_id)) && $where['cu.cid'] = $cg_id;
        //能力大分类
        (isset($st_id) && !empty($st_id)) && $where['cu.st_id'] = $st_id;
        //上下架状态
        (isset($type) && !empty($type)) && $where['c.type'] = $type;
        //进行中状态
        (isset($course_status) && !empty($course_status)) && $where['c.course_status'] = $course_status;
        $table = request()->controller();
        $join = [
            ['yx_study_type st', 'c.st_id = st.id', 'left'], //学习能力大分类
            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'], //课目ID
            ['yx_category ca', 'cu.cid = ca.id', 'left'], //大分类
            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'], //课程ID
            ['yx_member m', 'c.mid = m.uid', 'left'], //课程ID
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'c.id,c.recom,c.present_price,c.c_num,ca.name caname,cl.name clname,cu.name cuname,c.course_status,c.type,m.cname,cl.classroom_relation,cu.curriculum_relation', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取课程详情
    public static function getjgCoursedetails($course_id)
    {
        $where = [
            'is_del' => 1,
//            'type' => 1,
            'id' => $course_id,
        ];

        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,img,mid,present_price,c_num,classroom_id,classroom_type,teacher_id,teacher_type,curriculum_id,curriculum_cid,original_price,curriculum_csid,title,start_time,end_time,start_age,end_age,surplus_num,teacher_name,arrange_time');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['img'] = handle_img_take($info['img']);
            $info['mem_id'] = $info['img'];
            //教室
            if (!empty($info['classroom_type'])) {
                $info['valjs'][0] = $info['classroom_type'];
            }
            if (!empty($info['classroom_id'])) {
                $info['valjs'][1] = $info['classroom_id'];
            }
            //老师
//            if (!empty($info['teacher_type'])) {
//                $info['valls'][0] = $info['teacher_type'];
//            }
//            if (!empty($info['teacher_id'])) {
//                $info['valls'][1] = $info['teacher_id'];
//            }
            //课目
            if (!empty($info['curriculum_id'])) {
                $info['valkm'][0] = $info['curriculum_cid'];
            }
            if (!empty($info['curriculum_cid'])) {
                $info['valkm'][1] = $info['curriculum_csid'];
            }
            if (!empty($info['curriculum_csid'])) {
                $info['valkm'][2] = $info['curriculum_id'];
            }
            unset($info['curriculum_id']);
            unset($info['classroom_id']);
            unset($info['teacher_id']);
            unset($info['classroom_type']);
            unset($info['teacher_type']);
            unset($info['curriculum_cid']);
            unset($info['curriculum_csid']);
            //时间处理
//            if ($info['start_time']) {
//                $info['start_time'] = $info['start_time'] * 1000;
//            }
//            if ($info['end_time']) {
//                $info['end_time'] = $info['end_time'] * 1000;
//            }
            return jsonResponseSuccess($info);
        }
    }

    //获取课程详情(备用)
    public static function getjgCoursedetailss($course_id)
    {
        $where = [
            'c.is_del' => 1,
            'c.type' => 1,
            'c.id' => $course_id,
//            'ca.type'=>1,
//            'ca.is_del'=>1,
//            'st.type'=>1,
//            'st.is_del'=>1,
        ];
        $table = request()->controller();
        $join = [
            ['yx_category ca', 'c.cid = ca.id', 'left'], //大分类
            ['yx_category_small cas', 'c.csid = cas.id', 'left'], //小分类
            ['yx_study_type st', 'c.st_id = st.id', 'left'], //学习能力大分类
            ['yx_study_type_son sts', 'c.sts_id = sts.id', 'left'] //学习能力小分类
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'c.cid,c.csid,c.st_id,c.sts_id,c.name,c.title,c.enroll_num,c.recom,c.details,ca.name caname,cas.name casname,c.wheel_img');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['wheel_img'] = handle_img_take($info['wheel_img']);
            if (!empty($info['cid'])) {
                $info['valkm'][0] = $info['cid'];
            }
            if (!empty($info['csid'])) {
                $info['valkm'][1] = $info['csid'];
            }
            if (!empty($info['st_id'])) {
                $info['valnl'][0] = $info['st_id'];
            }
            if (!empty($info['sts_id'])) {
                $info['valnl'][1] = $info['sts_id'];
            }
            return jsonResponseSuccess($info);
        }
    }

    //添加课程
    public static function addjgCourse()
    {
        $mem_data = self::isuserData();
        $data = input();

        //验证课程库存状态
        $data = isNumType($data);

        if ($mem_data['type'] != 2) {
//            $data['mid'] = $mem_data['mem_id'];
            throw new ISUserMissException();
        }
        if ($data['img']) {
            $data['img'] = handle_img_deposit($data['img']);
        }
        $data['present_price'] = $data['present_price'] * $data['c_num'];
        //课目一级分类
        if (!empty($data['valkm'][0])) {
            $data['curriculum_cid'] = $data['valkm'][0];
        }
        //课目二级分类
        if (!empty($data['valkm'][1])) {
            $data['curriculum_csid'] = $data['valkm'][1];
        }
        //课目
        if (!empty($data['valkm'][2])) {
            $data['curriculum_id'] = $data['valkm'][2];
        }
        unset($data['valkm']);
        //教室
        if (!empty($data['valjs'][0])) {
            $data['classroom_type'] = $data['valjs'][0];
        }
        if (!empty($data['valjs'][1])) {
            $data['classroom_id'] = $data['valjs'][1];
        }
        unset($data['valjs']);
        //老师分类ID
        if (!empty($data['valls'][0])) {
            $data['teacher_type'] = $data['valls'][0];
        }
        //老师
        if (!empty($data['valls'][1])) {
            $data['teacher_id'] = $data['valls'][1];
        }

        unset($data['valls']);
        //开始结束时间
//        if ($data['timeis']) {
//            $data['start_time'] = $data['timeis'][0] / 1000;
//            $data['end_time'] = $data['timeis'][1] / 1000;
//        }
        unset($data['timeis']);
//        $time_array = IsTime::TimeScreen($data);
        unset($data['selection_array']);
        //验证课目如果某机构没有直接添加
        $isCurriculum = Crud::isCurriculum($data, 2);
        if (!is_array($isCurriculum)) {
            return $isCurriculum;
        }

//        foreach ($isCurriculum as $ck=>$cv){
//            foreach ($data['mem_id']  as $dk=>$dv ){
//                if($dv ==$cv['mid']){
//                    $data['curriculum_id'] = $cv['id'];
//                }
//            }
//        }


        //验证教室如果某机构没有直接添加
        $isClassroom = Crud::isClassroom($data, 2);
        if (!is_array($isClassroom)) {
            return $isClassroom;
        }

        $mem_ids = $data['mem_id'];
        unset($data['mem_id']);
        $table = request()->controller();
        foreach ($isClassroom as $lk=>$lv){
            foreach ($isCurriculum as $ck=>$cv){
                foreach ($mem_ids  as $k=>$v ){
                    if($v ==$cv['mid'] && $v==$lv['mem_id']){
                        $data['curriculum_id'] = $cv['id'];
                        $data['classroom_id'] = $lv['id'];
                        $data['mid'] = $v;
                        $course_id = Crud::setAdd($table, $data, 2);
                    }
                }
            }
        }



//        $mem_ids = $data['mem_id'];
//        unset($data['mem_id']);
//        $table = request()->controller();
//        foreach ($mem_ids as $k => $v) {
//            $data['mid'] = $v;
//            $course_id = Crud::setAdd($table, $data, 2);
//        }


        //机构添加分类


        //存时间段
//        foreach ($time_array as $k => $v) {
//            $time_data = [
//                'course_id' => $course_id,
//                'classroom_id' => $data['classroom_id'],
//                'teacher_id' => $data['teacher_id'],
//                'day' => $v['day'],
//                'time_slot' => $v['time_slot'],
//                'type' => $v['type'],
//            ];
//            $table = 'course_timetable';
//            $course_time = Crud::setAdd($table, $time_data, 1);
//        }
        if (!$course_id) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($course_id);
        }
    }

    //获取修改课程数据(开始时间于结束时间)
    public static function getCourseTime($course_id)
    {
        $where = [
            'id' => $course_id,
            'type' => 1,
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'start_time,end_time');
        if ($info) {
            $info['start_time'] = $info['start_time'] * 1000;
            $info['end_time'] = $info['end_time'] * 1000;
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    //修改课程
    public static function setjgCourse()
    {
        $data = input();
        if ($data['img']) {
            $data['img'] = handle_img_deposit($data['img']);
        }
//        $data['present_price'] = $data['present_price'] * $data['c_num'];
        //课目一级分类
        if (!empty($data['valkm'][0])) {
            $data['curriculum_cid'] = $data['valkm'][0];
        }
        //课目二级分类
        if (!empty($data['valkm'][1])) {
            $data['curriculum_csid'] = $data['valkm'][1];
        }
        //课目
        if (!empty($data['valkm'][2])) {
            $data['curriculum_id'] = $data['valkm'][2];
        }
        unset($data['valkm']);
        //教室
        if (!empty($data['valjs'][0])) {
            $data['classroom_type'] = $data['valjs'][0];
        }
        if (!empty($data['valjs'][1])) {
            $data['classroom_id'] = $data['valjs'][1];
        }
        unset($data['valjs']);
        //老师分类ID
        if (!empty($data['valls'][0])) {
            $data['teacher_type'] = $data['valls'][0];
        }
        //老师
        if (!empty($data['valls'][1])) {
            $data['teacher_id'] = $data['valls'][1];
        }
        if (isset($data['start_time']) && !empty($data['start_time'])) {
            $data['start_time'] = $data['start_time'] / 1000;
            $data['end_time'] = $data['end_time'] / 1000;
        }

        unset($data['valls']);
        //开始结束时间
//        if ($data['timeis']) {
//            $data['start_time'] = $data['timeis'][0] / 1000;
//            $data['end_time'] = $data['timeis'][1] / 1000;
//        }
        unset($data['timeis']);
//        $time_array = IsTime::TimeScreen($data);
        unset($data['CourseObj']);
        unset($data['selection_array']);
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $data['course_id'],
        ];
        $course_id = $data['course_id'];
        unset($data['course_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);

//        if ($time_array) {
//            //删除以前时间段
//            $table = 'course_timetable';
//            $where = [
//                'course_id' => $course_id,
//                'classroom_type' => 1,//1机构教室，2综合体教室，3社区教室
//            ];
//            $time_update = [
//                'is_del' => 2
//            ];
//            $info = Crud::setUpdate($table, $where, $time_update);
//            if ($info) {
//                //存时间段
//                foreach ($time_array as $k => $v) {
//                    $time_data = [
//                        'course_id' => $course_id,
//                        'classroom_id' => $data['classroom_id'],
//                        'teacher_id' => $data['teacher_id'],
//                        'day' => $v['day'],
//                        'time_slot' => $v['time_slot'],
//                        'type' => $v['type'],
//                    ];
//                    $table = 'course_timetable';
//                    $course_timetable_data = Crud::setAdd($table, $time_data, 1);
//                }
//            }
//        }


        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除课程
    public static function deljgCourse($course_id)
    {
        $where = [
            'is_del' => 1,
            'id' => $course_id,
        ];
        $data = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //上下架操作
    public static function editjgCourseType($course_id, $type)
    {
        $where = [
            'id' => $course_id,
        ];
        $data = [
            'type' => $type
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //上下架操作
    public static function editjgCourseStatus($course_id, $course_status)
    {
        $where = [
            'id' => $course_id,
        ];
        $data = [
            'course_status' => $course_status
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //机构上传课程
    public static function setjgCourses()
    {
        $data = input();
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $where = [
                'uid' => $user_data['mem_id'],
                'is_del' => 1,
                'status' => 1,
            ];
        }
        $data['is_verification'] = 3;
        if (isset($data['mlicense']) && !empty($data['mlicense'])) {
            $mlicense_array = [];
            foreach ($data['mlicense'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['mlicense'] = serialize($mlicense_array);
        }

        unset($data['uid']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //修改单个日期
    public static function editjgTime($course_id)
    {
        $where = [
            'id' => $course_id,
            'type' => 1,
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'start_time,end_time');
        if ($info) {
            $info['start_time'] = $info['start_time'] * 1000;
            $info['end_time'] = $info['end_time'] * 1000;
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    //获取单个时间时间段
    public static function getjgTimeSection()
    {
        $data = input();
        $where = [
            'id' => $data['course_id'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'start_time,end_time,classroom_id,teacher_id,curriculum_id');
        if ($info) {
            $info['course_id'] = $data['course_id'];
            $info['someday'] = $data['someday'] / 1000;
        }
        $res = IsTime::isTimeReturns($info);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new NothingMissException();
        }
    }

    //修改单个日期
    public static function addjgsingleTime()
    {
        $data = input();
        $someday = $data['someday'] / 1000;
        //删除本天的所有数据
        $table = 'course_timetable';
        $where = [
            'course_id' => $data['course_id'],
            'day' => $someday,
        ];
        $upData = [
            'is_del' => 2
        ];
        $del_time = Crud::setUpdate($table, $where, $upData);
        if ($del_time) {
            $merge_array = [];
            foreach ($data['selection_array']['morning'] as $mk => $mv) {
                $merge_array[] = [
                    'time_slot' => $mv,
                    'type' => 1,
                ];
            }
            foreach ($data['selection_array']['afternoon'] as $mk => $mv) {
                $merge_array[] = [
                    'time_slot' => $mv,
                    'type' => 2,
                ];
            }
            foreach ($data['selection_array']['night'] as $mk => $mv) {
                $merge_array[] = [
                    'time_slot' => $mv,
                    'type' => 3,
                ];
            }
            $where1 = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['course_id'],
            ];
            $table1 = request()->controller();
            $course_data = Crud::getData($table1, $type = 1, $where1, $field = 'teacher_id,classroom_id');
            if ($course_data) {
                $table = 'course_timetable';
                foreach ($merge_array as $k => $v) {
                    $addData = [
                        'time_slot' => $v['time_slot'],
                        'type' => $v['type'],
                        'classroom_id' => $course_data['classroom_id'],
                        'teacher_id' => $course_data['teacher_id'],
                        'course_id' => $data['course_id'],
                        'day' => $someday,
                    ];
                    $info = Crud::setAdd($table, $addData);
                }
            }
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new NothingMissException();
        }


    }


}