<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 18:27
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\AddressMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class HelpTowType extends BaseController
{
    //获取帮助中心分类
    public static function getpcHelpTowType($page = 1, $name = '',$help_one_id)
    {
        $table = request()->controller();
        $where = [
            'htt.is_del' => 1,
            'htt.help_one_id'=>$help_one_id
        ];
        (isset($name) && !empty($name)) && $where['htt.name'] = ['like', '%' . $name . '%'];


        $join = [
            ['yx_help_one_type hot', 'htt.help_one_id =hot.id ', 'left'],
        ];
        $alias = 'htt';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'htt.sort desc', $field = 'htt.id,htt.name,htt.platform_type,hot.name one_name', $page);

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
        if ($info) {
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'htt.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //添加帮助分类 传值name sort
    public static function addpcHelpTowType()
    {
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddressMissException();
        }
    }

    //修改帮助分类
    public static function editpcHelpTowType()
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
    public static function delpcHelpTowType($id)
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