<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 11:20
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class HelpDetails extends BaseController
{
    //获取帮助中心列表
    public static function getjgHelpList($help_tow_id, $name = '')
    {
        $table = request()->controller();
        $where = [
//            'hd.help_one_id' => $help_one_id,
            'hd.help_tow_id' => $help_tow_id,
            'hd.is_del' => 1,
            'htt.platform_type' => 2,
        ];
        //名称搜索
        (isset($name) && !empty($name)) && $where['name'] = ['like', '%' . $name . '%'];
        $join = [
//            ['yx_help_one_type hot', 'hd.help_one_id = hot.id', 'left'],
            ['yx_help_tow_type htt', 'hd.help_tow_id = htt.id', 'left'],
        ];
        $alias = 'hd';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'hd.sort desc', $field = 'hd.id,hd.name,hd.title', 1,1000);
        if ($info) {
            $length = count($info);
            if ($length > 2) {
                $length = 2;
            }
            $info_three = [];
            for ($i = 0; $i < $length; $i++) {
                $info_three [] = $info[$i];
                unset($info[$i]);
            }
            $list_array = [
                'three_array' => $info_three,
                'title_array' => $info,
            ];
            return jsonResponseSuccess($list_array);
        } else {
            throw new NothingMissException();
        }

    }

    //获取帮助中心详情
    public static function getjgHelpDetails($id)
    {
        $table = request()->controller();
        $where = [
            'id' => $id,
            'is_del' => 1,
        ];
        $info = Crud::getData($table, 1, $where, 'id,name,title,details', 'sort desc', 1, 10000);
        if ($info) {

            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }

    }
}