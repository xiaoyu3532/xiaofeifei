<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 10:12
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class HotRecom extends BaseController
{
    //获取热门推荐列表
    public static function getpcHotRecom($page = '1', $name = '', $cname = '')
    {
        $table = request()->controller();
        $join = [
            ['yx_member m', 'hr.mem_id = m.uid', 'left'], //机构
            ['yx_course c', 'hr.cou_id = c.id', 'left'], //课程
            ['yx_curriculum cm', 'c.curriculum_id = cm.id', 'left'], //课目
        ];
        $alias = 'hr';
        $where = [
            'hr.is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['cm.name'] = ['like', '%' . $name . '%'];
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'hr.create_time desc', $field = 'hr.id,m.cname,cm.name,hr.sort,hr.create_time', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'hr.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取机构列表
    public static function getpcHotMemberNane()
    {
        $where = [
            'is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['cname'] = ['like', '%' . $name . '%'];
        $table = 'member';
        $info = Crud::getData($table, $type = 2, $where, $field = 'uid id,cname', $order = 'sort desc', 1, 100000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //获取当前机构课程
    public static function getpcHotCourseNane($mem_id)
    {
        $table = 'course';
        $join = [
            ['yx_curriculum cm', 'c.curriculum_id = cm.id', 'left'], //课目
        ];
        $alias = 'c';
        $where = [
            'c.is_del' => 1,
            'c.type' => 1,
            'c.mid' => $mem_id,
        ];
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.id,cm.name', 1, 10000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //热门推荐详情
    public static function getpcHotRecomdetails($hotrecom_id)
    {
        $table = request()->controller();
        $where = [
            'id' => $hotrecom_id
        ];
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponse($info);
        }
    }

    //添加热门推荐
    public static function addpcHotRecom()
    {
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if (!$info) {
            throw new AddMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //修改热门推荐
    public static function setpcHotRecom()
    {
        $data = input();
        $where = [
            'id' => $data['hotrecom_id']
        ];
        unset($data['hotrecom_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new UpdateMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除热门推荐
    public static function delpcHotRecom()
    {
        $data = input();
        $where = [
            'id' => $data['hotrecom_id']
        ];
        $data = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }


}