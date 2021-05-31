<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 9:26
 */

namespace app\jg\controller\v1;

use app\common\controller\BaseController;
use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;
class Classroom extends BaseController
{
    //获取机构教室
    public static function getjgClassroom($name = '', $page = '1', $pageSize = '16', $mem_id = '')
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $where = [
                'c.is_del' => 1,
//                'ct.is_del' => 1,
//                'ct.type' => 1,
//                'c.mem_id' => $user_data['mem_id'],

            ];
        }
        $mem_ids = bindingMember::getbindingjgMemberId();
        if (isset($mem_id) && !empty($mem_id)) {
            //验证传过的机构ID
            $isbindingjgMember = bindingMember::isbindingjgMember($mem_id);
            if ($isbindingjgMember != 1000) {
                return $isbindingjgMember;
            }
            $where['c.mem_id'] = $mem_id;
        } else {
            $where['c.mem_id'] = ['in', $mem_ids];
        }
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //right
        ];
        $alias = 'c';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.name,c.province,c.city,c.area,c.address,c.brief,c.img,c.create_time,c.id cla_id,ct.name type_name,m.cname', $page, $pageSize);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) {
                if (!empty($v['img'])) {
                    $imgs = unserialize($v['img']);
                    $cname_data[$k]['img'] = $imgs[0];
                }
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*');
            $info_data = [
                'info' => $cname_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //添加机构教室
    public static function addjgClassroom()
    {
        $data = input();
        if (!empty($data['selectedOptions'])) {
            $data['province_num'] = $data['selectedOptions'][0];
            $data['city_num'] = $data['selectedOptions'][1];
            $data['area_num'] = $data['selectedOptions'][2];
        }
        unset($data['selectedOptions']);
        $user_data = self::isuserData();
        if ($user_data['type'] != 2) { //1用户，2机构
//            $data['mem_id'] = $user_data['mem_id'];
            throw new ISUserMissException();
        }
        if (isset($data['img']) && !empty($data['img'])) {
            $mlicense_array = [];
            foreach ($data['img'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['img'] = serialize($mlicense_array);
        }
        $data['classroom_relation'] = time() . rand(10, 99);
        $table = request()->controller();
        foreach($data['mem_id'] as $k=>$v){
            $data['mem_id'] = $v;
            $info = Crud::setAdd($table, $data);
        }
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //修改机构教室
    public static function setjgClassroom()
    {
        $data = input();
//        $where = [
//            'id' => $data['cla_id']
//        ];

        if (!empty($data['selectedOptions'])) {
            $data['province_num'] = $data['selectedOptions'][0];
            $data['city_num'] = $data['selectedOptions'][1];
            $data['area_num'] = $data['selectedOptions'][2];
        }
        unset($data['selectedOptions']);
        if (isset($data['img']) && !empty($data['img'])) {
            $mlicense_array = [];
            foreach ($data['img'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['img'] = serialize($mlicense_array);
        }
//        $table = request()->controller();
        $info = Crud::isClassroom($data,1);
        if ($info == 1000) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
//        $info = Crud::setUpdate($table, $where, $data);
//        if ($info) {
//            return jsonResponseSuccess($info);
//        } else {
//            throw new UpdateMissException();
//        }
    }

    //获取机构教室详情
    public static function getjgClassroomdetails($cla_id)
    {
        $where = [
            'c.type' => 1,
            'c.is_del' => 1,
            'c.id' => $cla_id,
//            'ct.is_del' => 1,
//            'ct.type' => 1,
        ];
        $join = [
            ['yx_classroom_type ct', 'c.type_id = ct.id', 'left'],
        ];
        $alias = 'c';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'c.name,c.mem_id,c.province,c.city,c.area,c.address,c.brief,c.img,c.longitude,c.latitude,c.type_id');
        if ($cname_data) {
            if (!empty($cname_data['img'])) {
                $cname_data['img'] = unserialize($cname_data['img']);
                $img_data = [];
                foreach ($cname_data['img'] as $k => $v) {
                    $img_data[] = [
                        'name' => 'food.jpg',
                        'url' => $v
                    ];
                }
                $cname_data['img'] = $img_data;
            } else {
                $cname_data['img'] = [];
            }
            $cname_data['selectedOptions'] = [];
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }


    }

    //删除机构教室
    public static function deljgClassroomr($cla_id)
    {
        $where = [
            'id' => $cla_id
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $res = Crud::setUpdate($table, $where, $upData);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new NothingMissException();
        }
    }

    //获取分类列表
    public static function getjgClassroomrTypelist()
    {
        $where1 = [
            'type' => 1,
            'is_del' => 1,
        ];
        $table1 = 'classroom_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id,name', $order = 'sort desc', $page = '1', $pageSize = '10000');
        if ($type_name_list) {
            return jsonResponseSuccess($type_name_list);
        } else {
            throw new NothingMissException();
        }
    }

    //获取分类教室名称列表
    public static function getjgClassroomrTypesearch()
    {
        $where1 = [
            'type' => 1,
            'is_del' => 1,
        ];
        $table1 = 'classroom_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id value,name label', $order = 'sort desc', $page = '1', $pageSize = '10000');
        if ($type_name_list) {
            $user_data = self::isuserData();
            if ($user_data['type'] == 2) { //1用户，2机构
                foreach ($type_name_list as $k => $v) {
                    $where = [
                        'is_del' => 1,
                        'mem_id' => $user_data['mem_id'],
                        'type_id' => $v['value'],
                    ];
                    $table = request()->controller();
                    $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
                    $type_name_list[$k]['children'] = $info;
                }
            }
            return jsonResponseSuccess($type_name_list);
        } else {
            throw new NothingMissException();
        }
    }


}