<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 17:34
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberExplainMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\validate\MemberIDSMustBePostiveInt;
use app\pc\controller\v1\ImgHandle as ImgHandles;

class Member extends BaseController
{
    //获取机构列表
    public static function getzhtMemberlist($page = '1')
    {
        $where = [
            'is_del' => 1,
            'status' => 1,//1开启，2禁用
//            'user_type' => 1,//1客户添加机构，2为后台添加
        ];
        $data = input();
        (isset($data['cname']) && !empty($data['cname'])) && $where['cname'] = ['like', '%' . $data['cname'] . '%'];
        if (isset($data['is_verification']) && !empty($data['is_verification'])) {
            $where['is_verification'] = $data['is_verification'];
        }
        if (isset($data['time']) && !empty($data['time'])) {
            $start_time = $data['time'][0] / 1000;
            $end_time = $data['time'][1] / 1000;
            $where = [
                'create_time' => ['between', [$start_time, $end_time]]
            ];
        }
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'uid,cname,nickname,create_time,phone,cumulative_price,cumulative_retreat_price,is_verification,organization', $order = 'uid desc', $page, $pageSize = '16');
        $num = Crud::getCounts($table, $where);
        if (!$info) {
            throw new MemberExplainMissException();
        } else {
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess( $info_data);
        }
    }

    //修改机构审核状态 is_verification
    public static function updateMemberVerification()
    {
        $data = input();
        (new MemberIDSMustBePostiveInt())->goCheck();
        $where = [
            'uid' => $data['mem_id']
        ];
        $upData = [
            'is_verification' => $data['is_verification'],
            'update_time' => time(),
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new UpdateMissException();
        } else {
            return jsonResponse('1000', '修改成功', $info);
        }

    }

    //获取机构详情
    public static function getzhtMemberdetails($mem_id)
    {
        $where = [
            'uid' => $mem_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'username,phone,logo,last_login_time,nickname,province,city,area,address,cname,create_time,aclass,mlicense,remarks,introduction,balance,ismember,re_num,enroll_num,course_num,browse_num,is_verification,cumulative_price,cumulative_retreat_price,organization');
        if (!$info) {
            throw new NothingMissException();
        } else {
            if (!empty($info['mlicense']) && is_serialized($info['mlicense'])) {
                $info['mlicense'] = unserialize($info['mlicense']);
            }else{
                if(empty($info['mlicense'])){
                    $info['mlicense']= ['https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png'];
                }else{
                    $info['mlicense']= [$info['mlicense']];
                }
                
            }
            // if (!empty($info['mlicense'])) {
            //     $info['mlicense'] = unserialize($info['mlicense']);
            // }
             if (empty($info['logo'])) {
                $info['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
            } elseif (is_serialized($info['logo'])) {
                $logo = unserialize($info['logo']);
                if (!empty($logo)) {
                    $info['logo'] = $logo[0];
                } else {
                    $info['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
                }
            }
            // if ($info['logo']) {
            //     $info['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
            // }
            return jsonResponseSuccess($info);
        }
    }

    //获取机构经纬度
    public static function getpcLongitudeLatitude(){
        $where = [
            'is_del' => 1,
            'status' => 1,//1开启，2禁用
            'type' => ['neq',4]
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'uid,cname,longitude,latitude,logo', '', 1, 100000);
        if($info){
            foreach ($info as $k=>$v){
                if($v['logo']){
                    $logo = get_take_img($v['logo']);
                    if($logo){
                        $info[$k]['logo'] = $logo;
                    }
                }else{
                    $info[$k]['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
                }
            }
            return jsonResponseSuccess($info);
        }else{
            throw new NothingMissException();
        }

    }
}