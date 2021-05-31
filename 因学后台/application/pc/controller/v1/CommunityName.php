<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/18 0018
 * Time: 13:34
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class CommunityName extends BaseController
{
    //获取社区列表
    public static function getCommunityName($page=1){
        $where = [
            'is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['name'] = ['like', '%' . $name . '%'];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $page);
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

    //添加社区列表
    public static function addpcCommunityName()
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

    //修改社区列表
    public static function setpcCommunityName()
    {
        $data = input();
        $where = [
            'id' => $data['communityName_id']
        ];
        unset($data['communityName_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除社区列表
    public static function delpcCommunityName()
    {
        $data = input();
        $where = [
            'id' => $data['communityName_id']
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

    //获取社区列表
    public static function getpcCommunityNameType()
    {
        $where = [
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name', $order = '', 1, 1000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }


}