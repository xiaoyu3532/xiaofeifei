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
use app\lib\exception\AddressMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\IsTime;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class CourseVideo extends BaseController
{
    //获取视频课程列表
    public static function getjgCourseVideo($page = '1', $name = '', $cg_id = '', $st_id = '', $type = '', $course_status = '', $mem_id = '')
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
        (isset($cg_id) && !empty($cg_id)) && $where['c.curriculum_cid'] = $cg_id;
        //能力大分类
//        (isset($st_id) && !empty($st_id)) && $where['cu.st_id'] = $st_id;
        //上下架状态
        (isset($type) && !empty($type)) && $where['c.type'] = $type;
        $table = request()->controller();
        $join = [
//            ['yx_study_type st', 'c.st_id = st.id', 'left'], //学习能力大分类
//            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'], //课目ID
            ['yx_category ca', 'c.curriculum_cid = ca.id', 'left'], //大分类
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //机构
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.cname,m.uid mem_id,c.id,c.cover_img,c.present_price,c.browse_num,ca.name caname,c.name,c.type,c.title,c.introduction,c.type,c.mem_id,c.password,c.sort,c.c_num,c.curriculum_cid,c.curriculum_csid', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['cover_img'] = handle_img_take($v['cover_img']);
                //课目一级分类
                if (!empty($v['curriculum_cid'])) {
                    $info[$k]['valkm'][0] = $v['curriculum_cid'];
                }
                if (!empty($v['curriculum_cid'])) {
                    $info[$k]['valkm'][1] = $v['curriculum_csid'];
                }
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
    public static function getjgCourseVideodetails($course_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $course_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['cover_img'] = handle_img_take($info['cover_img']);
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
            unset($info['curriculum_cid']);
            unset($info['curriculum_csid']);
            return jsonResponseSuccess($info);
        }
    }

    //添加视频课程
    public static function addjgCourseVideo()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] == 2) {
            $data['mem_id'] = $mem_data['mem_id'];
        }
        //封面
        if ($data['cover_img']) {
            $data['cover_img'] = handle_img_deposit($data['cover_img']);
        }

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

        $table = request()->controller();
        $course_id = Crud::setAdd($table, $data, 2);
        //机构添加分类
        if (!$course_id) {
            throw new NothingMissException();
        } else {
            //给机构加视频课程数
            $member_data = Crud::setIncs('member',['uid'=>$mem_data['mem_id']],'video_num');
//            $course_num=Crud::setIncsMemberId($mem_data['mem_id']);
//            //添加课程时，给机构加分类
//            Crud::setIncMemberCaid($data['curriculum_cid'],$mem_data['mem_id']);
            return jsonResponseSuccess($course_id);
        }
    }

    //修改课程
    public static function setjgCourseVideo()
    {
        $data = input();
        if ($data['cover_img']) {
            $data['cover_img'] = handle_img_deposit($data['cover_img']);
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
        unset($data['valkm']);

        $where = [
            'is_del' => 1,
            'id' => $data['course_id'],
        ];
        $course_id = $data['course_id'];
        unset($data['course_id']);
        $table = request()->controller();
        unset($data['caname']); //分类名称
        unset($data['cname']); //机构名称
        unset($data['id']); //ID
        $data['update_time'] = time();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除课程
    public static function deljgCourseVideo($course_id)
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
            $mem_data = Crud::getData($table,1, ['id'=>$course_id], 'mem_id');
            $member_data = Crud::setDecs('member',['uid'=>$mem_data['mem_id']],'video_num');
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


    //复制视频课程
    //mem_ids 其他机构ID 为数组
    //course_id 视频课程
    public static function copyjgCourseVideo($course_id, $mem_id)
    {
        if (!is_array($mem_id)) {
            return jsonResponse(2000, '数据有误');
        }
        $table = $table = request()->controller();
        $where = [
            'is_del' => 1,
            'id' => $course_id
        ];

        //获取视频课程
        $courseVideo_data = Crud::getData($table, 1, $where, $field = '*');
//        dump($courseVideo_data);EXIT;
        if ($courseVideo_data) {
            //视频
            $where1 = [
                'is_del' => 1,
                'course_video_id' => $courseVideo_data['id'],
            ];
            $table1 = 'video';
            $video_data = Crud::getData($table1, 2, $where1, $field = '*');
            foreach ($mem_id as $k => $v) {
                $data = [
                    'name' => $courseVideo_data['name'],
                    'mem_id' => $v,
                    'cover_img' => $courseVideo_data['cover_img'],
                    'password' => $courseVideo_data['password'],
                    'sort' => $courseVideo_data['sort'],
                    'c_num' => $courseVideo_data['c_num'],
                    'curriculum_cid' => $courseVideo_data['curriculum_cid'],
                    'curriculum_csid' => $courseVideo_data['curriculum_csid'],
                ];
                $Course_Video_id = Crud::setAdd($table,$data,2);
                if($Course_Video_id){
                    //给机构加视频课程数
                    $member_data = Crud::setIncs('member',['uid'=>$v],'video_num');
                    //判断是有视频
                    if($video_data){
                        foreach ($video_data as $kk=>$vv){
                            $data1 = [
                                'name'=>$vv['name'],
                                'mem_id'=>$v,
                                'course_video_id'=>$Course_Video_id,
                                'cover_img'=>$vv['cover_img'],
                                'introduction'=>$vv['introduction'],
                                'sort'=>$vv['sort'],
                                'video'=>$vv['video'],
                            ];
                            $Video_id = Crud::setAdd($table1,$data1,2);
                        }
                    }
                }else{
                    throw new AddMissException();
                }

            }
            if($Course_Video_id){
                return jsonResponseSuccess($Course_Video_id);
            }else{
                throw new AddMissException();
            }

        } else {
            throw new AddMissException();
        }

    }

}