<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 11:20
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class HelpDetails extends BaseController
{
    //获取帮助中心列表
    public static function getpcHelpList($help_one_id, $name = '', $help_tow_id, $page = 1)
    {
        $table = request()->controller();
        $where = [
            'hd.help_one_id' => $help_one_id,
            'hd.help_tow_id' => $help_tow_id,
            'hd.is_del' => 1,
        ];
        //名称搜索
        (isset($name) && !empty($name)) && $where['hd.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_help_one_type hot', 'hd.help_one_id =hot.id ', 'left'],
            ['yx_help_tow_type htt', 'hd.help_tow_id =htt.id ', 'left'],
        ];
        $alias = 'hd';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'hd.sort desc', $field = 'hd.id,hd.sort,hd.title,hd.details,hd.name,hot.name one_name,hot.platform_type,htt.name tow_name', $page);
        if ($info) {
            foreach ($info as $k=>$v){
                if($v['platform_type'] ==1){
                    $platform_name = '总平台';
                }elseif ($v['platform_type'] ==2){
                    $platform_name = '机构';
                }elseif ($v['platform_type'] ==3){
                    $platform_name = '成长中心';
                }
                $info[$k]['platform_name'] = $platform_name;
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'hd.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }

    //添加帮助中心
    public static function addpcHelpDetails()
    {
        $data = input();
        unset($data['platform_name']);
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }

    }

    //修改帮助中心
    public static function editpcHelpDetails()
    {
        $data = input();
        $id = $data['id'];
        unset($data['id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, ['id' => $id], $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除分类
    public static function delpcHelpDetails($id)
    {
        $table = request()->controller();
        $info = Crud::setUpdate($table, ['id' => $id], ['is_del' => 2]);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new DelMissException();
        }
    }
}