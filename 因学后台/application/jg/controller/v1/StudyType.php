<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/13 0013
 * Time: 11:07
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class StudyType extends BaseController
{
    //获取能力大分类
    public static function getjgStudyType($page = '1')
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

    //获取能力小分类
    public static function getpcStudyTypeSon($st_id, $page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'st_id' => $st_id,
        ];
        $table = 'study_type_son';
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //获取能力大小分类
    public static function getjggroupStudyType($page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $table = 'study_type_son';
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'st_id' => $v['value'],
                ];
                $children = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                }
            }
            return jsonResponseSuccess($info);
        }
    }


}