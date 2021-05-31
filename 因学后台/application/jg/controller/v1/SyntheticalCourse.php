<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 19:34
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class SyntheticalCourse extends BaseController
{
    //获取综合体课程列表
    public static function getjgSynthetical($page = '1', $name = '', $cg_id = '', $st_id = '', $type = '', $course_status = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'c.is_del' => 1,
//                'c.type' => 1,
                'c.mid' => $mem_data['mem_id'],
//            'ca.type'=>1,
//            'ca.is_del'=>1,
//            'st.type'=>1,
//            'st.is_del'=>1,
            ];
        } else {
            throw new ISUserMissException();
        }
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
            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'], //教室ID
            ['yx_synthetical_name sn', 'c.syntheticalcn_id = sn.id', 'left'], //获取综合体名称
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'c.id,c.recom,c.present_price,c.c_num,ca.name caname,cl.name clname,cu.name cuname,c.course_status,c.type,c.apply_type,sn.name snname,c.syntheticalcn_id', $page);
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

    //机构申请综合课程
    public static function addjgSynthetical()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] == 2) {
            $data['mid'] = $mem_data['mem_id'];
        }
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
        //老师分类ID
        if (!empty($data['valls'][0])) {
            $data['teacher_type'] = $data['valls'][0];
        }
        //老师
        if (!empty($data['valls'][1])) {
            $data['teacher_id'] = $data['valls'][1];
        }
        unset($data['valls']);
        $data['type'] = 2;
        $table = request()->controller();
        $course_id = Crud::setAdd($table, $data, 2);
        if (!$course_id) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($course_id);
        }
    }

    //获取综合体课程详情
    public static function getjgSyntheticaldetails($course_id)
    {
        $where = [
            'is_del' => 1,
//            'type' => 1,
            'id' => $course_id,
        ];

        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,img,present_price,c_num,classroom_id,classroom_type,teacher_id,teacher_type,curriculum_id,curriculum_cid,original_price,curriculum_csid,title,start_time,end_time,start_age,end_age,surplus_num,syntheticalcn_id,arrange_time,teacher_name');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['img'] = handle_img_take($info['img']);
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

    //修改综合体课程
    public static function setjgSynthetical()
    {
        $data = input();
        if(isset($data['end_time'])&&!empty($data['end_time'])){
            $data['end_time'] = $data['end_time']/1000;
        }
        if ($data['img']) {
            $data['img'] = handle_img_deposit($data['img']);
        }
        if(isset($data['start_time'])&&!empty($data['start_time'])){
            $data['start_time'] = $data['start_time']/1000;
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
        unset($data['valls']);
        unset($data['CourseObj']);
        unset($data['selection_array']);
        $where = [
            'is_del' => 1,
            'id' => $data['course_id'],
        ];
        unset($data['course_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //上下架操作
    public static function editjgSyntheticalType($course_id, $type)
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

    //删除课程
    public static function deljgSynthetical($course_id)
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

    //修改申请状态
    public static function setjgSyntheticalCourse($seckillcourse_id, $type)
    {
        $where = [
            'id' => $seckillcourse_id,
        ];
        $data = [
            'apply_type' => $type
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }


}