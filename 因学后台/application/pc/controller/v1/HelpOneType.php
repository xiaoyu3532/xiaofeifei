<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 11:58
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddressMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class HelpOneType extends BaseController
{

    //获取帮助中心分类
    public static function getpcHelpOneType($page = 1, $name = '')
    {
        $table = request()->controller();
        $where = [
            'is_del' => 1
        ];
        (isset($name) && !empty($name)) && $where['name'] = ['like', '%' . $name . '%'];
        $info = Crud::getData($table, 2, $where, 'id,name,platform_type', 'sort desc', $page);
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
            $num = Crud::getCount($table, $where);
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
    public static function addpchelonetype()
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
    public static function editpcHelpOneType()
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
    public static function delHelpOneType($id)
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