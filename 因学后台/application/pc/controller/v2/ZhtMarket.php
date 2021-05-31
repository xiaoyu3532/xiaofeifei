<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/17 0017
 * Time: 17:24
 */

namespace app\pc\controller\v2;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;
use app\lib\exception\UpdateMissException;

class ZhtMarket extends PublicFunction
{
    const table_zht_market = 'zht_market';
    const table_zht_market_list = 'zht_market_list';
    const table_zht_market_list_detail = 'zht_market_list_detail';
    const table_zht_market_order = 'zht_market_order';

    //获取大活动
    public static function getZhtMarket($mem_id = '', $market_name = '', $page = 1, $pageSize = 8)
    {
        $where = [
            'is_del' => 1
        ];
        if (empty($mem_id)) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $where['mem_id'] = ['in', $mem_ids];
        } else {
            $where['mem_id'] = $mem_id;
        }
        (isset($market_name) && !empty($market_name)) && $where['name'] = ['like', '%' . $market_name . '%']; //课程名
        $table = self::table_zht_market;
        $info = Crud::getData($table, 2, $where, '*', 'id desc', $page, $pageSize);
        if ($info) {
            $num = Crud::getCount($table, $where);
            foreach ($info as $k => $v) {
                $info[$k]['create_time_Exhibition'] = date('Y-m-d', $v['create_time']);
                if (isset($v['detail']) && !empty($v['detail'])) { //教室图片可多张
                    $info[$k]['detail'] = handle_img_take($v['detail']);
                } else {
                    $info[$k]['detail'] = [];
                }
                if (!empty($v['start_time']) && !empty($v['end_time'])) {
                    $info[$k]['market_time'] = [
                        '0' => $v['start_time'] * 1000,
                        '1' => $v['end_time'] * 1000,
                    ];
                    $info[$k]['market_time_name'] = date('Y-m-d H:i:s', $v['start_time']) . '-' . date('Y-m-d H:i:s', $v['end_time']);
                } else {
                    $info[$k]['market_time'] = '-';
                }
            }
            $info_data = [
                'info' => $info,
                'pageSize' => (int)$pageSize,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }

    //获取大活动字段
    public function getZhtMarketField()
    {
        $arrye = [
            ['name', '主活动名称'],
            ['image', '主活动图片', '220'],
            ['market_time_name', '主活时间'],
            ['small_name', '小活动主标题'],
            ['create_time_Exhibition', '主活动创建时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加大活动（目前中是添加小候鸟）
    public static function addZhtMarket()
    {
        $data = input();
        if (empty($data['mem_id'])) {
            $account_data = self::isuserData();
            $data['mem_id'] = $account_data['mem_id'];
        }
        if (isset($data['market_time']) && !empty($data['market_time'])) {
            $data['start_time'] = $data['market_time'][0] / 1000;
            $data['end_time'] = $data['market_time'][1] / 1000;
        }
        if (isset($data['detail']) && !empty($data['detail'])) {
            $data['detail'] = handle_img_deposit($data['detail']);
        }
        $table = self::table_zht_market;
        $market_info = Crud::setAdd($table, $data);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new AddMissException();
        }
    }

    //修改大活动
    public static function editZhtMarket()
    {
        $data = input();
        unset($data['id']);
        if (isset($data['market_time']) && !empty($data['market_time'])) {
            $data['start_time'] = $data['market_time'][0] / 1000;
            $data['end_time'] = $data['market_time'][1] / 1000;
        }
        if (isset($data['detail']) && !empty($data['detail'])) {
            $data['detail'] = handle_img_deposit($data['detail']);
        }
        $data['update_time'] = time();
        $table = self::table_zht_market;
        $market_info = Crud::setUpdate($table, ['id' => $data['market_id']], $data);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new AddMissException();
        }
    }

    //大活动上下架
    public static function typeZhtMarket()
    {
        $data = input();
        $table = self::table_zht_market;
        if ($data['market_type'] == 1) {
            $market_type = 2;
        } elseif ($data['market_type'] == 2) {
            $market_type = 1;
        }
        $market_info = Crud::setUpdate($table, ['id' => ['in', $data['market_id']]], ['market_type' => $market_type, 'update_time' => time()]);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new UpdateMissException();
        }
    }

    //删除大活动 传值加一个表名
    public static function delZhtMarket($data)
    {
        $del_data = Crud::setUpdate($data['table'], ['id' => $data['id'], ['is_del' => 2, 'update_time' => time()]]);
        if ($del_data) {
            return jsonResponseSuccess($del_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取活动列表
    public static function getZhtMarketList($market_list_name = '', $market_id, $page = 1, $pageSize = 8)
    {
        $where = [
            'zml.is_del' => 1,
            'zml.market_id' => $market_id,
        ];
        (isset($market_list_name) && !empty($market_list_name)) && $where['zml.name'] = ['like', '%' . $market_list_name . '%']; //活动列表搜索
        $join = [
            ['yx_zht_market zm', 'zml.market_id = zm.id', 'left'],  //大活动表
        ];
        $alias = 'zml';
        $table = self::table_zht_market_list;
        $market_list_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zml.create_time desc', $field = 'zml.*,zm.name market_name', $page, $pageSize);
        if ($market_list_data) {
            foreach ($market_list_data as $k => $v) {
                $market_list_data[$k]['create_time_Exhibition'] = date('Y-m-d', $v['create_time']);
                if (isset($v['banner']) && !empty($v['banner'])) {
                    $market_list_data[$k]['banner'] = handle_img_take($v['banner']);
                } else {
                    $market_list_data[$k]['banner'] = [];
                }
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zml.id');
            $info_data = [
                'info' => $market_list_data,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取活动列表字段
    public function getZhtMarketListField()
    {
        $arrye = [
            ['name', '活动名称'],
            ['market_name', '大活动名称'],
            ['img', '封面图', '220'],
            ['create_time_Exhibition', '时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加活动列表 market_id   小活动详情时间market_list_detail_time
    public static function addZhtMarketList()
    {
        $data = input();
        if (isset($data['banner']) && !empty($data['banner'])) {
            $data['banner'] = handle_img_deposit($data['banner']);
        }
        $table = self::table_zht_market_list;
        $market_list_id = Crud::setAdd($table, $data, 2);
        if (!$market_list_id) {
            throw new AddMissException();
        }
        $table_zht_market_list_detail = self::table_zht_market_list_detail;
        $update_market_list_id = Crud::getData($table_zht_market_list_detail, 2, ['id' => ['in', $data['market_list_detail_ids']]], 'id');
        if (!empty($update_market_list_id)) {
            $update_market_list_detail = Crud::setUpdate($table_zht_market_list_detail, ['id' => ['in', $data['market_list_detail_ids']]], ['market_list_id' => $market_list_id, 'update_time' => time()]);
            if (!$update_market_list_detail) {
                throw new UpdateMissException();
            }
        }
        return jsonResponseSuccess($market_list_id);
    }

    //修改活动列表
    public static function editZhtMarketList()
    {
        $data = input();
        unset($data['id']);
        if (isset($data['banner']) && !empty($data['banner'])) {
            $data['banner'] = handle_img_deposit($data['banner']);
        }
        $table = self::table_zht_market_list;
        $data['update_time'] = time();
        $market_info = Crud::setUpdate($table, ['id' => $data['market_list_id']], $data);
        if (!$market_info) {
            throw new AddMissException();
        }
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new AddMissException();
        }
    }

    //活动列表上下架
    public static function typeZhtMarketList()
    {
        $data = input();
        $table = self::table_zht_market_list;
        if ($data['market_list_type'] == 1) {
            $market_list_type = 2;
        } elseif ($data['market_list_type'] == 2) {
            $market_list_type = 1;
        }
        $market_info = Crud::setUpdate($table, ['id' => ['in', $data['market_list_id']]], ['market_list_type' => $market_list_type, 'update_time' => time()]);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new AddMissException();
        }
    }

    //删除活动列表
    public static function delZhtMarketList()
    {
        $data = input();
        $table = self::table_zht_market_list;
        $market_info = Crud::setUpdate($table, ['id' => $data['market_list_id']], ['is_del' => 2]);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new AddMissException();
        }
    }

    //获取小小活动详情列表 yx_zht_market_list_detail
    public static function getZhtMarketListDetailList($market_list_id, $page = 1, $pageSize = 10000)
    {
        $where = [
            'zml.is_del' => 1,
            'zmld.market_list_id' => $market_list_id
        ];
//        (isset($name) && !empty($name)) && $where['zmld.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_zht_market_list zml', 'zmld.market_list_id = zml.id', 'left'],  //活动列表
        ];
        $alias = 'zmld';
        $table = self::table_zht_market_list_detail;
        $market_list_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zmld.create_time desc', $field = 'zmld.id market_list_detail_id,zmld.market_list_id,zmld.name,zmld.brief,zmld.quota,zmld.start_time,zmld.end_time,zmld.province,zmld.city,zmld.area,zmld.address,zmld.address_code,zmld.longitude,zmld.latitude,zmld.create_time,zmld.name market_list_name', $page, $pageSize);
        if ($market_list_data) {
            foreach ($market_list_data as $k => $v) {
                $market_list_data[$k]['create_time_Exhibition'] = date('Y-m-d', $v['create_time']);
                $market_list_data[$k]['address_code'] = unserialize($v['address_code']);
                $market_list_data[$k]['market_detail_time'] = [
                    '0' => $v['start_time'] * 1000,
                    '1' => $v['end_time'] * 1000,
                ];
                $market_list_data[$k]['market_list_detail_time'] = date('Y-m-d', $v['start_time']) . '-' . date('Y-m-d', $v['end_time']);
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zmld.id');
            $info_data = [
                'info' => $market_list_data,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }

    //获取小小活动列表字段
    public function getZhtMarketListDetailField()
    {
        $arrye = [
            ['name', '活动名称'],
            ['market_list_name', '活动列表名称'],
            ['brief', '简介'],
            ['quota', '名额'],
            ['used_quota', '已报名额'],
            ['market_time', '活动时间'],
            ['create_time_Exhibition', '时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加小小活动
    public static function addZhtMarketListDetail()
    {
        $data = input();
        if (isset($data['address_code']) && !empty($data['address_code'])) {
            $data['address_code'] = serialize($data['address_code']);
        }
        if (isset($data['market_detail_time']) && !empty($data['market_detail_time'])) {
            $data['start_time'] = $data['market_detail_time'][0] / 1000;
            $data['end_time'] = $data['market_detail_time'][1] / 1000;
        }
        $table = self::table_zht_market_list_detail;
        $market_list_detail_id = Crud::setAdd($table, $data, 2);
        if ($market_list_detail_id) {
            $data['address_code'] = unserialize($data['address_code']);
            $data['market_list_detail_id'] = $market_list_detail_id;
            $data['market_list_detail_time'] = date('Y-m-d', $data['start_time']) . '-' . date('Y-m-d', $data['end_time']);
            return jsonResponseSuccess($data);
        } else {
            throw new AddMissException();
        }
    }

    //修改小小活动
    public static function editZhtMarketListDetail()
    {
        $data = input();
//        if (isset($data['address_array']) && !empty($data['address_array'])) {
//            $data['province'] = $data['address_array'][0];
//            $data['city'] = $data['address_array'][1];
//            $data['area'] = $data['address_array'][2];
//        }
        if (isset($data['market_detail_time']) && !empty($data['market_detail_time'])) {
            $data['start_time'] = $data['market_detail_time'][0] / 1000;
            $data['end_time'] = $data['market_detail_time'][1] / 1000;
        }
        if (isset($data['address_code']) && !empty($data['address_code'])) {
            $data['address_code'] = serialize($data['address_code']);
        }
        $table = self::table_zht_market_list_detail;
        $info = Crud::setUpdate($table, ['id' => $data['market_list_detail_id']], $data);
        if ($info) {
            $data['address_code'] = unserialize($data['address_code']);
            $data['market_list_detail_time'] = date('Y-m-d', $data['start_time']) . '-' . date('Y-m-d', $data['end_time']);
            return jsonResponseSuccess($data);
        } else {
            throw new AddMissException();
        }
    }

    //删除小小活动
    public static function delZhtMarketListDetail()
    {
        $data = input();
        $table = self::table_zht_market_list_detail;
        $info = Crud::setUpdate($table, ['id' => ['in', $data['market_list_detail_id']]], ['is_del' => 2]);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //获取小候鸟等活动订单
    public static function getZhtMarketOrder($market_order_status = '', $phone = '', $contacts = '', $user_name = '', $student_name = '', $market_name = '', $market_list_name = '', $market_list_detail_name = '', $page = 1, $pageSize = 16)
    {
        $where = [
            'zml.is_del' => 1
        ];

        if (isset($market_order_status) && !empty($market_order_status)) {
            if ($market_order_status == 1) {
                $where['start_time'] = ['>', time()];
            } elseif ($market_order_status == 2) {//v['start_time'] < time() && $v['end_time'] > time()
                $where['start_time'] = ['>', time()];
            } elseif ($market_order_status == 3) {
                $where['end_time'] = ['<', time()];
            }
        }

        (isset($phone) && !empty($phone)) && $where['o.contacts'] = ['like', '%' . $phone . '%']; //联系人电话
        (isset($contacts) && !empty($contacts)) && $where['o.contacts'] = ['like', '%' . $contacts . '%']; //联系人
        (isset($user_name) && !empty($user_name)) && $where['u.name'] = ['like', '%' . $user_name . '%']; //用户名
        (isset($student_name) && !empty($student_name)) && $where['ls.student_name'] = ['like', '%' . $student_name . '%']; //学生名
        (isset($market_name) && !empty($market_name)) && $where['zm.name'] = ['like', '%' . $market_name . '%']; //大活动名
        (isset($market_list_name) && !empty($market_list_name)) && $where['zml.name'] = ['like', '%' . $market_list_name . '%']; //活动列表名
        (isset($market_list_detail_name) && !empty($market_list_detail_name)) && $where['zmld.name'] = ['like', '%' . $market_list_detail_name . '%']; //活动详情名
        $join = [
            ['yx_user u', 'zmo.user_id = u.id', 'left'],  //用户表
            ['yx_lmport_student ls', 'zmo.student_id = ls.id', 'left'],  //学生表
            ['yx_zht_market zm', 'zmo.market_id = zm.id', 'left'],  //大活动表
            ['yx_zht_market_list zml', 'zmo.market_list_id = zml.id', 'left'],  //活动列表
            ['yx_zht_market_list_detail zmld', 'zmo.market_list_detail_id = zmld.id', 'left'],  //小小活动
        ];
        $alias = 'zmo';
        $table = self::table_zht_market_order;
        $market_order_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zmo.create_time desc', $field = 'zmo.*,u.name user_name,ls.student_name,zm.name market_name,zml.name market_list_name,zml.img,zmld.name market_list_detail_name,zmld.province,zmld.city,zmld.area,zmld.address,zmld.start_time,zmld.end_time', $page, $pageSize);
        if ($market_order_data) {
            foreach ($market_order_data as $k => $v) {
                if ($v['start_time'] > time()) {
                    $market_order_data[$k]['market_order_status_name'] = '未开始'; //yx_zht_market_order
                    $market_order_data[$k]['market_order_status'] = 1;
                } elseif ($v['start_time'] < time() && $v['end_time'] > time()) {
                    $market_order_data[$k]['market_order_status_name'] = '进行中';
                    $market_order_data[$k]['market_order_status'] = 2;
                } elseif ($v['end_time'] < time()) {
                    $market_order_data[$k]['market_order_status_name'] = '已结束';
                    $market_order_data[$k]['market_order_status'] = 3;
                }
                $market_order_data[$k]['create_time_Exhibition'] = date('Y-m-d', $v['create_time']);
                $market_order_data[$k]['zaddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $market_order_data[$k]['market_time'] = date('Y-m-d H:i:s', $v['start_time']) . '-' . date('Y-m-d H:i:s', $v['end_time']);
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zmo.id');
            $info_data = [
                'info' => $market_order_data,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }


    }

    //获取小候鸟等活动订单字段
    public function getZhtMarketOrderField()
    {  //zmo.*,u.name user_name,ls.,zm.name ,zml.name ,zmld.name
        $arrye = [
            ['user_name', '用户名称'],
            ['student_name', '学生名称'],
            ['img', '图片', '220'],
            ['phone', '联系电话'],
            ['contacts', '联系人'],
            ['market_name', '大活动名称'],
            ['market_list_name', '活动列表名称'],
            ['market_list_detail_name', '小活动名称'],
            ['market_order_status_name', '活动状态'],
            ['market_time', '活动时间'],
            ['zaddress', '活动地址'],
            ['create_time_Exhibition', '时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

}