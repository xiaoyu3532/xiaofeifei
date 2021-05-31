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
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Curriculum extends BaseController
{

    //获取课种列表(课目)
    public static function getjgCurriculum($page = '1', $name = '', $cg_id = '', $st_id = '', $mem_id = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
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


        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        //学科大分类
        (isset($cg_id) && !empty($cg_id)) && $where['c.cid'] = $cg_id;
        //能力大分类
        (isset($st_id) && !empty($st_id)) && $where['c.st_id'] = $st_id;

        $table = request()->controller();
        $join = [
            ['yx_category ca', 'c.cid = ca.id', 'left'], //大分类
            ['yx_study_type st', 'c.st_id = st.id', 'left'],//学习能力大分类
            ['yx_member m', 'c.mid = m.uid', 'left'],//机构
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'c.id,c.name,c.title,c.recom,ca.name caname,st.name stname,m.cname', $page);
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

    //获取课种详情
    public static function getjgCurriculumdetails($course_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $course_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'cid,csid,st_id,sts_id,name,title,recom,details,wheel_img,mid');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $info['mem_id'] = $info['mid'];
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

    //获取课种详情(备用)
    public static function getjgCurriculumdetailss($course_id)
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

    //添加课种
    public static function addjgCurriculum()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] != 2) {
//            $data['mid'] = $mem_data['mem_id'];
            throw  new ISUserMissException();
        }
        if ($data['wheel_img']) {
            $data['wheel_img'] = handle_img_deposit($data['wheel_img']);
        }
//        handle_type_deposit('st_id','sts_id',$data)
        if (!empty($data['valnl'])) {
            $data['st_id'] = $data['valnl'][0];
            if (!empty($data['valnl'][1])) {
                $data['sts_id'] = $data['valnl'][1];
            }
        }
        unset($data['valnl']);
        if (!empty($data['valkm'])) {
            $data['cid'] = $data['valkm'][0];
            if (!empty($data['valkm'][1])) {
                $data['csid'] = $data['valkm'][1];
            }
        }
        unset($data['valkm']);
        $table = request()->controller();
        //添加多个机构的课目
        $mem_ids = $data['mem_id'];
        unset($data['mem_id']);
        $data['curriculum_relation'] = time() . rand(10, 99);
        foreach ($mem_ids as $k => $v) {
            $data['mid'] = $v;
            $info = Crud::setAdd($table, $data);
        }
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //修改课种
    public static function setjgCurriculum()
    {
        $data = input();
        if (!empty($data['wheel_img'])) {
            $data['wheel_img'] = handle_img_deposit($data['wheel_img']);
        }
        //能力分类
        if (!empty($data['valnl'])) {
            $data['st_id'] = $data['valnl'][0];
            if (!empty($data['valnl'][1])) {
                $data['sts_id'] = $data['valnl'][1];
            }
        }
        unset($data['valnl']);
        //学科分类
        if (!empty($data['valkm'])) {
            $data['cid'] = $data['valkm'][0];
            if (!empty($data['valkm'][1])) {
                $data['csid'] = $data['valkm'][1];
            }
        }
        unset($data['valkm']);
        $info = Crud::isCurriculum($data,1);
        if ($info == 1000) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除课种
    public static function deljgCurriculum($course_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
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


}