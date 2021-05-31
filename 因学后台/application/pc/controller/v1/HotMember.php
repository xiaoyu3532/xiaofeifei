<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 20:36
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class HotMember extends BaseController
{
    //获取热门机构列表
    public static function getpcHotMember($page = '1', $name = '')
    {
        $table = request()->controller();
        $join = [
            ['yx_member m', 'hm.mem_id = m.uid', 'left'], //机构
        ];
        $alias = 'hm';
        $where = [
            'hm.is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['m.cname'] = ['like', '%' . $name . '%'];
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'hm.create_time desc', $field = 'hm.id,m.cname,hm.sort,hm.create_time', $page);
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

    //添加热门机构列表
    public static function addpcHotMember()
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

    //热门机构推荐详情
    public static function getpcHotMemberdetails($hotmember_id)
    {
        $table = request()->controller();
        $where = [
            'id' => $hotmember_id
        ];
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponse($info);
        }
    }

    //修改热门机构
    public static function setpcHotMember()
    {
        $data = input();
        $where = [
            'id' => $data['hotmember_id']
        ];
        unset($data['hotmember_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new UpdateMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //删除热门机构
    public static function delpcHotMember()
    {
        $data = input();
        $where = [
            'id' => $data['hotmember_id']
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