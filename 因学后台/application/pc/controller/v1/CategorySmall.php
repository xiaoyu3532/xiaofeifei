<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 16:16
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class CategorySmall extends BaseController
{
    //获取课程小分类
    public static function getpcCategorySmall($pid, $page = '1', $name = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'pid' => $pid,
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
    public static function getpcCategorySmalldetails($category_small_id)
    {
        $where = [
            'cs.is_del' => 1,
            'cs.type' => 1,
            'cs.id' => $category_small_id,
            'c.type' => 1,
            'c.is_del' => 1,
        ];
        $table = request()->controller();

        $join = [
            ['yx_category c', 'cs.pid = c.id', 'left'],
        ];
        $alias = 'cs';
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'cs.name,cs.sort,c.name one_name');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //添加课程小分类
    public static function addpcCategorySmall()
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
    public static function setpcCategorySmall()
    {
        $data = input();
        $where = [
            'id' => $data['category_small_id']
        ];
        unset($data['category_small_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除课程小分类
    public static function delpcCategorySmall()
    {
        $data = input();
        $where = [
            'id' => $data['category_small_id']
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