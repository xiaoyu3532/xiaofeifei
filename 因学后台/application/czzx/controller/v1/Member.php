<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 18:36
 */

namespace app\czzx\controller\v1;

use app\lib\exception\ISUserMissException;
use app\lib\exception\MemberExplainMissException;
use app\common\model\Crud;
use app\lib\exception\UpdateMissException;

class Member extends BaseController
{
    //获取机构材料(详情)
    public static function getczzxMemberMaterial()
    {
        //获取机构ID
        $user_data = self::isuserData();
        if ($user_data['type'] == 4) { //1用户，2机构
            $where = [
                'uid' => $user_data['mem_id'],
                'is_del' => 1,
                'status' => 1,
            ];
            $table = request()->controller();
            $info = Crud::getData($table, $type = 1, $where, $field = 'uid,cname,province,city,area,address,nickname,phone,organization,mlicense,remarks,longitude,latitude,is_verification');
            if ($info) {
                if (!empty($info['mlicense'])) {
                    $info['mlicense'] = unserialize($info['mlicense']);
                    $mlicens = [];
                    foreach ($info['mlicense'] as $k => $v) {
                        $mlicens[] = [
                            'name' => 'food.jpg',
                            'url' => $v
                        ];
                    }
                    $info['mlicense'] = $mlicens;
                    $info['mlicenses'] = $mlicens;
                } else {
                    $info['mlicense'] = [];
                    $info['mlicenses'] = [];
                }
                return jsonResponseSuccess($info);
            } else {
                throw new MemberExplainMissException();
            }
        }


    }

    //上传机构材料
    public static function updateMaterial()
    {
        $data = input();
        $user_data = self::isuserData();
        if ($user_data['type'] == 4) { //1用户，2机构
            $where = [
                'uid' => $user_data['mem_id'],
                'is_del' => 1,
                'status' => 1,
            ];
        }
        $data['is_verification'] = 3;
        if (isset($data['mlicense']) && !empty($data['mlicense'])) {
            $mlicense_array = [];
            foreach ($data['mlicense'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['mlicense'] = serialize($mlicense_array);
        }

        unset($data['uid']);
        unset($data['mlicenses']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取机构信息（LGOG 名称）
    public static function getczzxMemberinformation()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 4) {
            $where = [
                'uid' => $mem_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];
            $table = request()->controller();
            $info = Crud::getData($table, $type = 1, $where, $field = 'logo,cname,uid mem_id,is_verification');
            $where = [
                'b.mem_id' => $mem_data['mem_id'],
                'b.is_del' => 1,
                'b.status' => 1,
//                'm.is_del'=>1,
            ];
            $join = [
                ['yx_member m', 'b.binding_mem_id = m.uid', 'left'],  //机构 获取机构名称
                //['yx_login_account la', 'b.mem_id = la.mem_id', 'left'],  //机构 获取机构名称
            ];
            $alias = 'b';
            $table = 'member_member_binding';
            $binding_member = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.cname,m.uid mem_id', 1, 100);
            if ($binding_member) {
                $own_member[] = ['cname' => $info['cname'], 'mem_id' => $info['mem_id']];
                $list_binding_member = array_merge($binding_member, $own_member);
                $info['binding_member'] = $list_binding_member;
            } else {
                $info['binding_member'] = [];
            }
            $logo = $info['logo'];
            if ($info) {
                if($info['is_verification']!=1){
                    throw new ISUserMissException();
                }else{
                    $info['logo'] = get_take_img($info['logo']);
                    if (empty($info['logo'])) {
                        $info['logo'][] = $logo;
                    } else {
                        $info['logo'][] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
                    }
                    if (empty($info['logo'][0])) {
                        $info['logo'][0] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
                    }
                    return jsonResponseSuccess($info);
                }
            } else {
                throw new MemberExplainMissException();
            }

        }
    }

    //修改机构信息（备用）
    public static function setMemberinformation()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] == 4) {
            $where = [
                'uid' => $mem_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];
            $table = request()->controller();
            $upData = [
                'cname' => $data['cname'],
                'logo' => $data['logo'],
                'update_time' => time(),
            ];
            $info = Crud::setUpdate($table, $where, $upData);
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                throw new MemberExplainMissException();
            }
        }
    }

    //获取机构信息
    public static function getczzxMemberinfo()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 4) {
            $where = [
                'uid' => $mem_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];
            $table = request()->controller();
            $info = Crud::getData($table, $type = 1, $where, $field = 'uid,logo,remarks,wheel_img,introduction,service_student,found_time,kf_phone,mlicense');
            if (!empty($info['logo'])) {
                if (is_serialized($info['logo'])) {
                    $info['logo'] = handle_img_take($info['logo']);
                } else {
                    $info['logo'] = [];
                }
            } else {
                $info['logo'] = [];
            }
            if (!empty($info['mlicense'])) {
                if (is_serialized($info['mlicense'])) {
                    $info['mlicense'] = handle_img_take($info['mlicense']);
                } else {
                    $info['mlicense'] = [];
                }
            }
            if (!empty($info['wheel_img'])) {
                if (is_serialized($info['wheel_img'])) {
                    $wheel_img = handle_img_take($info['wheel_img']);
                    $info['wheel_img'] = $wheel_img;
                    $info['wheel_imgs'] = $wheel_img;
                } else {
                    $info['wheel_img'] = [];
                }
            } else {
                $info['wheel_img'] = [];
            }
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                throw new MemberExplainMissException();
            }

        }
    }

    //修改机构信息（详情与简介机构轮播图）
    public static function setczzxMemberinfo()
    {
        $mem_data = self::isuserData();
        $data = input();
        if ($mem_data['type'] == 4) {
            $where = [
                'uid' => $mem_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];
            $table = request()->controller();
            if (!empty($data['logo'])) {
                $logo = handle_img_deposit($data['logo']);
            }
            if (!empty($data['wheel_img'])) {
                $wheel_img = handle_img_deposit($data['wheel_img']);
            }
            $upData = [
                'logo' => $logo,
                'wheel_img' => $wheel_img,
                'remarks' => $data['remarks'],
                'introduction' => $data['introduction'],
                'service_student' => $data['service_student'],
                'found_time' => $data['found_time'],
                'kf_phone' => $data['kf_phone'],
                'update_time' => time(),
            ];
            unset($data['wheel_imgs']);
            $info = Crud::setUpdate($table, $where, $upData);
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                throw new UpdateMissException();
            }
        }
    }


}