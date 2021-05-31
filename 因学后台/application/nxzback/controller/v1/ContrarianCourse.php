<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/9 0009
 * Time: 11:57
 */

namespace app\nxzback\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;

class ContrarianCourse extends Base
{
    //逆行者发布课程
    public static function setContrarianbackCourse()
    {
        $data = input();
        unset($data['token']);
        $where = [
            'status' => 1,
            'is_del' => 1,
            'uid' => $data['mem_id']
        ];
        $table = request()->controller();
        $member_info = Crud::getData('member', 1, $where, $field = 'uid,is_verification');
        if ($member_info['is_verification'] == 3) {
            return jsonResponse('1001', '审核中');
        } elseif ($member_info['is_verification'] != 1) {
            return jsonResponse('1002', '请完善资料');
        }
        if (isset($data['img']) && !empty($data['img'])) {
            $data['img'] = $data['img'][0]['url'];
        }

        if (!empty($data['details_img'])) {
            $details_img_array = [];
            foreach ($data['details_img'] as $k=>$v){
                $details_img_array[] = $v['url'];
            }
            $data['details_img'] = serialize($details_img_array);
        }
        $data['type'] = 2; //1上架，2下架
        $contraria_couser = Crud::setAdd($table, $data, $type = 1);
        if ($contraria_couser) {
            return jsonResponseSuccess($contraria_couser);
        } else {
            throw new AddMissException();
        }


    }

    //获取机构逆行者课程
    public static function getContrarianbackCourse()
    {
        $data = input();
        $where = [
            'co.mem_id' => $data['mem_id'],
            'co.is_del' => 1,
            //'co.type' => 1,//1正常，2禁用
            'c.is_del' => 1,
//            'co.course_status' => 1, //1未开始，2进行中，3已结束
        ];

//        $info = Crud::getData($table, 2, $where, 'id,img,name,title', $order = '', $page);
        $table = request()->controller();
        $join = [
            ['yx_contrarian_classification c', 'co.classification_id = c.id', 'left']
        ];
        $alias = 'co';
        $field = ['co.id,co.img,co.name,co.title,c.name classification_name,co.type'];
        $order = 'co.create_time';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page,1000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                if (!empty($v['logo'])) {
                    $logo = get_take_img($v['logo']);
                    if (!empty($logo)) {
                        $info[$k]['logo'] = $logo[0];
                    }
                }
            }
            return jsonResponseSuccess($info);
        }

    }

    //删除逆行者课程
    public static function delContrarianbackCourse()
    {
        $data = input();
        $where = [
            'id' => $data['id'],
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, ['is_del' => 2]);
        if (!$info) {
            throw new DelMissException();
        } else {

            return jsonResponseSuccess($info);
        }

    }


}