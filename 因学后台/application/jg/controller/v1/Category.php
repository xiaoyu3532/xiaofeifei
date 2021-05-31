<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/13 0013
 * Time: 11:00
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class Category extends BaseController
{
    //获取课程大分类
    public static function getjgCategory($page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //获取课程小分类
    public static function getjgCategorySmall($pid, $page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'pid' => $pid,
        ];
        $table = 'category_small';
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //组合课程分类
    public static function getjggroupCategory()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'pid' => $v['value'],
                ];
                $table = 'category_small';

                $children = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }

    //组合课目分类及课目名称
    public static function getjggroupCategoryCurriculum($mem_id = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            $mem_data = self::isuserData();
            if ($mem_data['type'] == 2) {
                if (!isset($mem_id) || empty($mem_id)) {
                    $mem_id = $mem_data['mem_id'];
                }
            }
            $table1 = 'category_small';
            $table2 = 'curriculum';
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'pid' => $v['value'],
                ];
                $children = Crud::getData($table1, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                    foreach ($children as $kk => $vv) {
                        $where = [
                            'is_del' => 1,
                            'type' => 1,
                            'mid' => $mem_id,
                            'csid' => $vv['value'],
                        ];
                        $curriculum_info = Crud::getData($table2, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
                        $info[$k]['children'][$kk]['children'] = $curriculum_info;
                    }
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }


    //获取一级目录加机构二级机构目录 v2
    public static function getjggroupCategoryMember($mem_id = '')
    {
        if (empty($mem_id)) {
            $account_data = self::isuserData();
            $mem_id = $account_data['mem_id'];
        }
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $mem_id,
                ];
                $table1 = 'category_small';

                $children = Crud::getData($table1, $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                } else {
                    $info[$k]['children'] = [];
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }


}