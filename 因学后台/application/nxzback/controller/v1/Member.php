<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/9 0009
 * Time: 12:14
 */

namespace app\nxzback\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberMissException;
use app\lib\exception\UpdateMissException;

class Member extends Base
{

    //获取逆行者机构
    public static function getContrarianMember()
    {
        $data = input();
        $where = [
            'status' => 1,
            'is_del' => 1,
            'uid' => $data['mem_id']
        ];

        $table = request()->controller();

        $info = Crud::getData($table, 1, $where, $field = 'uid mem_id,phone,logo,nickname,province,city,area,address,cname,mlicense,remarks,img,is_verification,label');
        if (!$info) {
            throw new MemberMissException();
        } else {
            if (!empty($info['label'])) {
                $info['label'] = unserialize($info['label']);
            } else {
                $info['label'] = [];
            }
            if (!empty($info['img'])) {
                $info['img'] = [0 => ['url' => $info['img']]];
            } else {
                $info['img'] = [];
            }
            if (!empty($info['logo'])) {
                $info['logo'] = [0 => ['url' => $info['logo']]];
            } else {
                $info['logo'] = [];
            }
            if (!empty($info['mlicense']) && is_serialized($info['mlicense'])) {
                $mlicense = unserialize($info['mlicense']);
                $info['mlicense'] = [0=>['url' => $mlicense[0]]];
            } else {
                $info['mlicense'] = [];
            }


//                if (!empty($info['logo'])) {
//                    $logo = get_take_img($info['logo']);
//                    if (!empty($logo)) {
//                        $info['logo'] = $logo[0];
//                    }
//                }
            return jsonResponseSuccess($info);
        }

    }

    //完善机构资料
    public static function perfectMember()
    {
        $data = input();
        unset($data['token']);
        $where = [
            'status' => 1,
            'is_del' => 1,
            'uid' => $data['mem_id']
        ];
        unset($data['mem_id']);
        $table = request()->controller();
        $info = Crud::getData($table, 1, $where, $field = 'uid');
        if (!$info) {
            throw new MemberMissException();
        }
        //完善用户信息
        if(isset($data['label']) && is_array($data['label']) && ($data['label'][0] !='')){
              $data['label'] = serialize($data['label']);
        }else{
            if(isset($data['label'])){
                $data['label'] = '';
            }
        }

        //营业执照
        if (isset($data['mlicense']) && !empty($data['mlicense'])) {
            $data['mlicense'] = serialize([$data['mlicense'][0]['url']]);
        }
        //机构封面图
        if (isset($data['img']) && !empty($data['img'])) {
            $data['img'] = $data['img'][0]['url'];
        }
        //机构logo图
        if (isset($data['logo']) && !empty($data['logo'])) {
            $data['logo'] = $data['logo'][0]['url'];
        }
        $data['update_time'] = time();
        $data['is_verification'] = 1; //1验证通过，2新注册用户，3待审核，4审核拒绝
        $mem_info = Crud::setUpdate($table, $where, $data);
        if (!$mem_info) {
            throw new UpdateMissException();
        } else {
            return jsonResponseSuccess($mem_info);
        }

    }

}