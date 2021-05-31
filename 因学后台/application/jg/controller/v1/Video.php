<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/11 0011
 * Time: 15:38
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\IsTime;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Video extends BaseController
{
    //获取视频课程列表
    public static function getjgVideo($page = '1', $name = '',  $type = '', $mem_id = '',$course_video_id)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'c.is_del' => 1,
                'c.course_video_id'=>$course_video_id
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
        //获取绑定机构ID返回
        $mem_ids = bindingMember::getbindingjgMemberId();
        if (isset($mem_id) && !empty($mem_id)) {
            //验证传过的机构ID
            $isbindingjgMember = bindingMember::isbindingjgMember($mem_id);
            if ($isbindingjgMember != 1000) {
                return $isbindingjgMember;
            }
            $where['c.mem_id'] = $mem_id;
        } else {
            $where['c.mem_id'] = ['in', $mem_ids];
        }

        //名称搜索
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        //学科大分类
//        (isset($cg_id) && !empty($cg_id)) && $where['c.curriculum_cid'] = $cg_id;
        //能力大分类
//        (isset($st_id) && !empty($st_id)) && $where['cu.st_id'] = $st_id;
        //上下架状态
        (isset($type) && !empty($type)) && $where['c.type'] = $type;
        $table = request()->controller();
        $join = [
//            ['yx_study_type st', 'c.st_id = st.id', 'left'], //学习能力大分类
//            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'], //课目ID
//            ['yx_category ca', 'c.curriculum_cid = ca.id', 'left'], //大分类
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //机构
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.cname,c.id,c.name,c.type,c.sort,c.mem_id,c.video,c.browse_num,c.cover_img', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['cover_img'] = handle_img_take($v['cover_img']);
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取视频课程详情
    public static function getjgVideodetails($video_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $video_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,mem_id,name,sort,video,type,create_time,video_name,cover_img,introduction');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['cover_img'] = handle_img_take($info['cover_img']);
            if(!empty($info['video'])){
                $video_array = [
                    'video'=>$info['video'],
                    'video_name'=>$info['video_name'],
                ];
                $info['video'] = handle_video_take($video_array);
            }

            return jsonResponseSuccess($info);
        }
    }

    //添加视频课程
    public static function addjgVideo()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] != 2) {
           throw new ISUserMissException();
        }
        if ($data['cover_img']) {
            $data['cover_img'] = handle_img_deposit($data['cover_img']);
        }
        if ($data['video']) {
            $video_data = handle_video_deposit($data['video']);
        }

        $data['video'] = $video_data['video'];
        $data['video_name'] = $video_data['video_name'];
        unset($video_data);
        $table = request()->controller();
        $course_id = Crud::setAdd($table, $data, 2);
        //机构添加分类
        if (!$course_id) {
            throw new NothingMissException();
        } else {
            //给视频课程加课时
            $CourseVideo_num = Crud::setIncs('course_video',['id'=>$data['course_video_id']],'c_num');
            return jsonResponseSuccess($course_id);
        }
    }

    //修改课程
    public static function setjgVideo()
    {
        $data = input();
        $where = [
            'is_del' => 1,
            'id' => $data['video_id'],
        ];
//        $course_id = $data['course_id'];
        unset($data['course_id']);
        $table = request()->controller();
        if ($data['cover_img']) {
            $data['cover_img'] = handle_img_deposit($data['cover_img']);
        }
        if ($data['video']) {
            $video_data = handle_video_deposit($data['video']);
        }
        $data['video'] = $video_data['video'];
        $data['video_name'] = $video_data['video_name'];
        unset($video_data);
        $data['update_time'] =time();
        unset($data['video_id']);
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除课程
    public static function deljgVideo($video_id)
    {
        $where = [
            'is_del' => 1,
            'id' => $video_id,
        ];
        $data = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            $info_id = Crud::getData($table, 1,['id' => $video_id],'course_video_id');
            $CourseVideo_num = Crud::setDecs('course_video',['id'=>$info_id['course_video_id']],'c_num');
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //上下架操作
    public static function editjgCourseVideoType($course_id, $type)
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

    //复制课程
    public static function copyjgCoursess()
    {
        $data = input();
        //查询出该课程所有值
        $where = [
            'id' => $data['id'],
            'is_del' => 1
        ];
        $table = request()->controller();
        $Courses_data = Crud::getData($table,1, $where, $field = 'classroom_id,curriculum_id,img,name,title,present_price,original_price,enroll_num,surplus_num,arrange_time,start_time,end_time,details,category_id,cid,csid,sort,longitude,latitude,st_id,sts_id,c_num,wheel_img,classroom_type,teacher_name,curriculum_cid,curriculum_csid,start_age,end_age,num_type');
//        dump($Courses_data);
        //获取教室详情
        $where1 = [
            'id' => $Courses_data['classroom_id'],
            'is_del' => 1
        ];
        $table1 = 'classroom';
        $Classroom_data = Crud::getData($table1,1, $where1, $field = 'name,province,city,area,address,brief,img,type_id,longitude,latitude,province_num,city_num,area_num,classroom_relation');
        //获取课目
        $where2 = [
            'id' => $Courses_data['curriculum_id'],
            'is_del' => 1
        ];
        $table2 = 'curriculum';
        $Curriculum_data = Crud::getData($table2,1, $where2, $field = 'name,title,details,cid,csid,sort,longitude,latitude,st_id,sts_id,wheel_img,curriculum_relation');
//        dump($Curriculum_data);
        $mem_ids = $data['mem_id'];
        unset($data['mem_id']);
        //机构循环添加教室、课目、课程
        foreach ($mem_ids as $k=>$v){
            //添加课目
            $Curriculum_data['mid'] = $v;
            $Curriculum_id = Crud::setAdd($table2,$Curriculum_data,2);
            //添加教室
            $Classroom_data['mem_id'] = $v;
            $Classroom_id = Crud::setAdd($table1,$Classroom_data,2);
            //添加课程
            $Courses_data['mid'] = $v;
            $Courses_data['classroom_id'] = $Classroom_id;
            $Courses_data['curriculum_id'] = $Curriculum_id;
            $Courses_info= Crud::setAdd($table,$Courses_data);
        }
        if($Courses_info){
            return jsonResponseSuccess($Courses_info);
        }else{
            throw new AddMissException();
        }
    }

    //复制课程
    public static function copyjgCourses()
    {
        $data = input();
        $table = $table = request()->controller();
        $info = Crud::copyjgCourses($data, $table,$data['cou_status']);
        if ($info == 1000) {
            return jsonResponseSuccess($info);
        } else {
            return $info;
        }
    }

}