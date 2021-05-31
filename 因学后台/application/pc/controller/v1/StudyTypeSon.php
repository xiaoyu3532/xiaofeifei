<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 19:54
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class StudyTypeSon extends BaseController
{
    //获取课程小分类
    public static function getpcStudyTypeSon($st_id, $page = '1', $name = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'st_id' => $st_id,
        ];

        (isset($name) && !empty($name)) && $where['name'] = ['like', '%' . $name . '%'];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCounts($table, $where, $type = '1');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取课程小分类详情
    public static function getpcStudyTypeSondetails($study_type_son_id)
    {
        $where = [
            'sts.is_del' => 1,
            'sts.type' => 1,
            'sts.id' => $study_type_son_id,
            'st.type' => 1,
            'st.is_del' => 1,
        ];
        $table = request()->controller();

        $join = [
            ['yx_study_type st', 'sts.st_id = st.id', 'left'],
        ];
        $alias = 'sts';
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'sts.name,sts.sort,st.name one_name');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //添加课程小分类
    public static function addpcStudyTypeSon()
    {
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //修改课程小分类
    public static function setpcStudyTypeSon()
    {
        $data = input();
        $where = [
            'id' => $data['study_type_son_id']
        ];
        unset($data['study_type_son_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除课程小分类
    public static function delpcStudyTypeSon()
    {
        $data = input();
        $where = [
            'id' => $data['study_type_son_id']
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