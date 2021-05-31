<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 20:12
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\IsTime;

class ExperienceCourse extends BaseController
{
    //获取体验课程列表
    public static function getpcExperienceCourse($mem_id, $page = '1', $name = '', $cg_id = '', $st_id = '', $type = '', $course_status = '')
    {
        $where = [
            'c.is_del' => 1,
            'c.mid' => $mem_id,
        ];
        //名称搜索
        (isset($name) && !empty($name)) && $where['cu.name'] = ['like', '%' . $name . '%'];
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
            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'], //课目ID
            ['yx_category ca', 'cu.cid = ca.id', 'left'], //大分类
            ['yx_study_type st', 'cu.st_id = st.id', 'left'], //学习能力大分类
            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'], //课程ID
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.id,c.recom,c.present_price,c.c_num,ca.name caname,cl.name clname,cu.name cuname,c.course_status,c.type', $page);
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

    //获取体验课程详情
    public static function getpcExperienceCoursedetails($course_id)
    {
        $where = [
            'is_del' => 1,
//            'type' => 1,
            'id' => $course_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,img,present_price,c_num,classroom_id,classroom_type,teacher_id,teacher_type,curriculum_id,curriculum_cid,original_price,curriculum_csid,title,start_time,end_time,surplus_num,start_age,end_age,arrange_time,teacher_name');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['img'] = handle_img_take($info['img']);
            //教室
            if (!empty($info['classroom_type'])) {
                $info['valjs'][0] = $info['classroom_type'];
            }
            if (!empty($info['classroom_id'])) {
                $info['valjs'][1] = $info['classroom_id'];
            }
            //老师
            if (!empty($info['teacher_type'])) {
                $info['valls'][0] = $info['teacher_type'];
            }
            if (!empty($info['teacher_id'])) {
                $info['valls'][1] = $info['teacher_id'];
            }
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
            if ($info['start_time']) {
                $info['start_time'] = $info['start_time'] * 1000;
            }
            if ($info['end_time']) {
                $info['end_time'] = $info['end_time'] * 1000;
            }
            return jsonResponseSuccess($info);
        }
    }


    //添加体验课程
    public static function addpcExperienceCourse()
    {
        $data = input();
        $data['mid'] = $data['mem_id'];
        unset($data['mem_id']);

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

        unset($data['valls']);
        //开始结束时间
//        if ($data['timeis']) {
//            $data['start_time'] = $data['timeis'][0] / 1000;
//            $data['end_time'] = $data['timeis'][1] / 1000;
//        }
        unset($data['timeis']);
//        $time_array = IsTime::TimeScreen($data);
        unset($data['selection_array']);
        $table = request()->controller();
        $course_id = Crud::setAdd($table, $data, 2);
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
            //添加课程数
            $course_num = Crud::setIncsMemberId($data['mid']);
            Crud::setIncMemberCaid($data['curriculum_cid'],$data['mid']);
            return jsonResponseSuccess($course_id);
        }
    }

    //获取修改体验课程数据(开始时间于结束时间)
    public static function getpcExperienceCourseTime($course_id)
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

    //修改体验课程
    public static function setpcExperienceCourse()
    {
        $data = input();
        if ($data['img']) {
            $data['img'] = handle_img_deposit($data['img']);
        }
//        $data['present_price'] = $data['present_price'] * $data['c_num'];
        //课目一级分类
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
//                'course_id' => $course_id
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

    //删除体验课程
    public static function delpcExperienceCourse($course_id)
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

    //上下架操作体验课程
    public static function editpcExperienceCourseType($course_id, $type)
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

    //开始，未开始，结束设置操作体验课程
    public static function editpcExperienceCourseStatus($course_id, $course_status)
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


    //修改单个日期返回开始结束时间
    public static function editpcExperienceTime($course_id)
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

    //返回单日的时间段状态列表
    public static function getpcExperienceTimeSection()
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

    //修改单个日期操作（入库）
    public static function addpcExperiencesingleTime()
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