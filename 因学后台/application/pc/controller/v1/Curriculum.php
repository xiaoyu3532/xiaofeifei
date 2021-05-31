<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 20:12
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class Curriculum extends BaseController
{
    //获取课种列表
    public static function getpcCurriculum($mem_id,$page = '1', $name = '', $cg_id = '', $st_id = '')
    {
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.mid' => $mem_id
            ];
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        //学科大分类
        (isset($cg_id) && !empty($cg_id)) && $where['c.cid'] = $cg_id;
        //能力大分类
        (isset($st_id) && !empty($st_id)) && $where['c.st_id'] = $st_id;

        $table = request()->controller();
        $join = [
            ['yx_category ca', 'c.cid = ca.id', 'left'], //大分类
            ['yx_study_type st', 'c.st_id = st.id', 'left'] //学习能力大分类
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'c.id,c.name,c.title,c.recom,ca.name caname,st.name stname', $page);
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
    public static function getpcCurriculumdetails($course_id)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $course_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'cid,csid,st_id,sts_id,name,title,recom,details,wheel_img');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $wheel_img = handle_img_take($info['wheel_img']);
            $info['wheel_img'] = $wheel_img;
            $info['wheel_imgs'] = $wheel_img;
            if(!empty($info['cid'])){
                $info['valkm'][0] = $info['cid'];
            }
            if(!empty($info['csid'])){
                $info['valkm'][1] = $info['csid'];
            }
            if(!empty($info['st_id'])){
                $info['valnl'][0] = $info['st_id'];
            }
            if(!empty($info['sts_id'])){
                $info['valnl'][1] = $info['sts_id'];
            }
            return jsonResponseSuccess($info);
        }
    }

    //添加课种 //$mem_id
    public static function addpcCurriculum()
    {
        $mem_data = self::isuserData();
        $data = input();
        $data['mid'] = $data['mem_id'];
        unset($data['mem_id']);
        if ($mem_data['type'] == 6) {
            $data['mid'] = $mem_data['mem_id'];
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
        unset($data['wheel_imgs']);
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //修改课种
    public static function setpcCurriculum()
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
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $data['course_id'],
        ];
        unset($data['course_id']);
        unset($data['wheel_imgs']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除课种
    public static function delpcCurriculum($course_id)
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