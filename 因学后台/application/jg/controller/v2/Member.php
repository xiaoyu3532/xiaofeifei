<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/2 0002
 * Time: 10:20
 */

namespace app\jg\controller\v2;


use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class Member extends BaseController
{
    //获取机构信息
    public static function getjgMember()
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2) { //1用户，2机构
            throw new  ISUserMissException();
        }
        $where = [
            'is_del' => 1,
            'status' => 1,
            'uid' => $account_data['mem_id'],
        ];
        $member_data = Crud::getData('member', 2, $where, '*');
        if ($member_data) {
            foreach ($member_data as $k => $v) {
                $member_data[$k]['mlicense'] = handle_img_take($v['mlicense']);
                $member_data[$k]['wheel_img'] = handle_img_take($v['wheel_img']);
                $member_data[$k]['maddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                $member_data[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
                $member_data[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
            }
            return jsonResponseSuccess($member_data);
        } else {
            throw new NothingMissException();
        }


    }

    //获取机构地址
    public static function getjgMemberAddress($mem_id = '')
    {

        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        $where = [
            'uid' => $mem_id,
            'is_del' => 1,
        ];
        $mem_data = Crud::getData('Member', 1, $where, 'province,city,area,address,longitude,latitude,province_num,city_num,area_num');
        if ($mem_data) {
            $mem_data['address_code'] = [
                '0' => $mem_data['province_num'],
                '1' => $mem_data['city_num'],
                '2' => $mem_data['area_num'],
            ];
//            address_code
            return jsonResponseSuccess($mem_data);
        } else {
            throw new NothingMissException();
        }
    }


    //编辑机构信息
    public static function editjgMember()
    {
        $data = input();
        if (isset($data['wheel_img']) && !empty($data['wheel_img'])) { //机构轮波图
            $data['wheel_img'] = handle_img_deposit($data['wheel_img']);
        }

        if (isset($data['mlicense']) && !empty($data['mlicense'])) { //机构营业执照与其他证书
            $data['mlicense'] = handle_img_deposit($data['mlicense']);
        }
        if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
            $data['province_num'] = $data['address_code'][0];
            $data['city_num'] = $data['address_code'][1];
            $data['area_num'] = $data['address_code'][2];
        }
        $data['update_time'] = time();
        $Member_data = Crud::setUpdate('member', ['uid' => $data['mem_id']], $data);
        if ($Member_data) {
            return jsonResponseSuccess($Member_data);
        } else {
            throw new UpdateMissException();
        }
    }

    //修改机构密码
    public static function editjgPassword()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] != 2) { //1用户，2机构
            throw new  ISUserMissException();
        }
        //获取当前密码  1用户，2机构，4综合体，5社区，6总平台，7管理人员
        $password_data = Crud::getData('login_account', 1, ['mem_id' => $account_data['mem_id'], 'type' => 2], '*');
        if ($password_data) {
            $password = splice_password($data['password'], $password_data['salt']);
            if ($password != $password_data['password']) {
                return jsonResponse('3001', '输入旧密码有误');
            }
            if ($data['new_password'] != $data['confirm_password']) {
                return jsonResponse('3002', '密码不一致');
            }
            $new_password = splice_password($data['new_password'], $password_data['salt']);
            $data_password = Crud::setUpdate('login_account', ['mem_id' => $account_data['mem_id'], 'type' => 2], ['password' => $new_password]);
            if ($data_password) {
                return jsonResponseSuccess($data_password);
            } else {
                throw new  UpdateMissException();
            }
        } else {
            return jsonResponse('3000', '用户信息错误');
        }

    }

}