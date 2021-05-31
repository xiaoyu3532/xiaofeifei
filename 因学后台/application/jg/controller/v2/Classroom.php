<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 9:26
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class Classroom extends BaseController
{
    //获取机构教室
    public static function getjgClassroomList($classroom_name = '', $page = '1', $mem_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'c.is_del' => 1,
                'm.is_del' => 1,
                'm.status' => 1,
            ];
            //如果机构总账号可以看到被绑定的子机构教室
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $where['c.mem_id'] = ['in', $mem_ids];
            }
            (isset($mem_id) && !empty($mem_id)) && $where['c.mem_id'] = $mem_id;
            (isset($classroom_name) && !empty($classroom_name)) && $where['c.classroom_name'] = ['like', '%' . $classroom_name . '%'];
            $join = [
                //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
                ['yx_member m', 'c.mem_id = m.uid', 'left'], //right
            ];
            $alias = 'c';
            $table = request()->controller();
            $classroom_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.id,c.classroom_type_id,c.img,c.indoor_img,c.classroom_name,c.province,c.city,c.area,c.address,c.brief,c.img,c.create_time,c.start_age,c.end_age,c.contain_number,c.area_number,c.fit_age,c.classroom_type_name,c.classroom_equipment_name,c.province_num,c.city_num,c.area_num,m.cname,m.phone,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone', $page, 8);
            if ($classroom_data) {
                foreach ($classroom_data as $k => $v) {
                    if (!empty($v['indoor_img'])) {
                        $classroom_data[$k]['indoor_img'] = unserialize($v['indoor_img']);
                    }
                    $classroom_data[$k]['classroom_type_id'] = unserialize($v['classroom_type_id']);
                    $classroom_data[$k]['classroom_equipment_name'] = explode(',', $v['classroom_equipment_name']);;
                    //如果为空时以'-'代替
                    $classroom_data[$k]['classroom_address'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                    $classroom_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                    $classroom_data[$k]['classroom_location'] = $v['province'] . $v['city'] . $v['area'];
                    $classroom_data[$k]['address_code'] = [$v['province_num'], $v['city_num'], $v['area_num']];
                    $classroom_data[$k]['fit_age'] = $v['start_age'] . '~' . $v['end_age'];
//                $cname_data[$k] = isempty($v);
                    //适合课程
                    if (!empty($v['classroom_type_name'])) {
                        $classroom_data[$k]['fit_curriculum'] = $v['classroom_type_name'];
                    } else {
                        $classroom_data[$k]['fit_curriculum'] = '-';
                    }
                    //容纳人数
                    if (empty($v['contain_number'])) {
                        $classroom_data[$k]['contain_number'] = '-';
                    }
                    //教室面积（m²)
                    if (empty($v['area_number'])) {
                        $classroom_data[$k]['area_number'] = '-';
                    }
                    //机构电话
                    if (empty($v['member_phone'])) {
                        $classroom_data[$k]['member_phone'] = '-';
                    }
                    if (isset($v['indoor_img']) && !empty($v['indoor_img'])) { //教室图片可多张
                        $classroom_data[$k]['indoor_img'] = handle_img_take($v['indoor_img']);
                    } else {
                        $classroom_data[$k]['indoor_img'] = [];
                    }
                }
                $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*');
                $info_data = [
                    'info' => $classroom_data,
                    'pageSize' => 8,
                    'num' => $num,
                ];
                return jsonResponseSuccess($info_data);
            } else {
                throw new NothingMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //获取机构教室列表字段
    public static function getjgClassroomListField()
    {
        $data = [
            ['prop' => 'classroom_name', 'name' => '教室名称', 'width' => '', 'state' => ''],
            ['prop' => 'classroom_address', 'name' => '教室地址', 'width' => '', 'state' => ''],
            ['prop' => 'fit_curriculum', 'name' => '适合课程', 'width' => '', 'state' => ''],
            ['prop' => 'contain_number', 'name' => '容纳人数', 'width' => '160', 'state' => '1'],
            ['prop' => 'area_number', 'name' => '教室面积（m²）', 'width' => '', 'state' => ''],
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '100', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '320', 'state' => ''],
            ['prop' => 'member_phone', 'name' => '机构电话', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //添加机构教室
    public static function addjgClassroom()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
            //教室分类ID
            if (isset($data['classroom_type_id']) && !empty($data['classroom_type_id'])) {
                $data['classroom_type_name'] = self::getCategorySmall($data['classroom_type_id']);
                $data['classroom_type_id'] = serialize($data['classroom_type_id']);
            }
            //地址标号
            if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
                $data['province_num'] = $data['address_code'][0];
                $data['city_num'] = $data['address_code'][1];
                $data['area_num'] = $data['address_code'][2];
            }
            unset($data['address_code']);
            //教室设备名称
            if (isset($data['classroom_equipment_name']) && !empty($data['classroom_equipment_name'])) {
                $data['classroom_equipment_name'] = implode(",", $data['classroom_equipment_name']);
            }
            if (!isset($data['img']) && empty($data['img'])) { //教室图片可多张
                unset($data['img']);
            }

            if (isset($data['indoor_img']) && !empty($data['indoor_img'])) { //教室图片可多张
                $data['indoor_img'] = handle_img_deposit($data['indoor_img']);
            }

            $table = request()->controller();
            $classroom_id = Crud::setAdd($table, $data, 2);
            if ($classroom_id) {
//            //添加教室科目分类  $data['category']
//            self::addCategory($data['category'], $classroom_id);
//            //添加教室设备 $data['equipment']
//            self::addEquipment($data['equipment'], $classroom_id);
                return jsonResponseSuccess($classroom_id);
            } else {
                throw new AddMissException();
            }
        } else {
            throw new ISUserMissException();
        }
    }

    //修改机构教室
    public static function setjgClassroom()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $classroom_id = $data['classroom_id'];
        unset($data['classroom_id']);
        $where = [
            'id' => $classroom_id
        ];
        if (isset($data['address_code']) && is_array($data['address_code']) && !empty($data['address_code'])) {
            $data['province_num'] = $data['address_code'][0];
            $data['city_num'] = $data['address_code'][1];
            $data['area_num'] = $data['address_code'][2];
        }
        unset($data['address_code']);
        //教室分类ID
        if (isset($data['classroom_type_id']) && !empty($data['classroom_type_id'])) {
            $data['classroom_type_name'] = self::getCategorySmall($data['classroom_type_id']);
            $data['classroom_type_id'] = serialize($data['classroom_type_id']);
        }

        //教室设备名称
        if (isset($data['classroom_equipment_name']) && !empty($data['classroom_equipment_name'])) {
            $data['classroom_equipment_name'] = implode(",", $data['classroom_equipment_name']);
        }
        if (!isset($data['img']) && empty($data['img'])) { //教室图片可多张
            unset($data['img']);
        }

        if (isset($data['indoor_img']) && !empty($data['indoor_img'])) { //教室图片可多张
            $data['indoor_img'] = handle_img_deposit($data['indoor_img']);
        }
        $data['update_time'] = time();
//        //修改课程分类
//        self::editCategory($data['category'], $data['cla_id']);
//        //修改课程设备
//        self::editEquipment($data['equipment'], $data['cla_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取机构教室详情
    public static function getjgClassroomdetails($classroom_id)
    {
        $where = [
            'c.type' => 1,
            'c.is_del' => 1,
            'c.id' => $classroom_id,
//            'ct.is_del' => 1,
//            'ct.type' => 1,  yx_classroom_type
        ];
        $join = [
            ['yx_member m', 'c.mem_id = m.uid', 'left'],
        ];
        $alias = 'c';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'c.name,c.province,c.city,c.area,c.address,c.img,c.classroom_type_name,c.classroom_equipment_name,c.indoor_img,m.cname,m.province,m.city,m.area,m.address,m.phone');
        if ($cname_data) {
            if (!empty($cname_data['indoor_img'])) {
                $cname_data['indoor_img'] = unserialize($cname_data['indoor_img']);
//                $img_data = [];
//                foreach ($cname_data['img'] as $k => $v) {
//                    $img_data[] = [
//                        'name' => 'food.jpg',
//                        'url' => $v
//                    ];
//                }
//                $cname_data['img'] = $img_data;
//            } else {
//                $cname_data['img'] = [];
            }
            if (!empty($cname_data['classroom_equipment_name'])) {
                $cname_data['classroom_equipment_name'] = explode(",", $cname_data['classroom_equipment_name']);
            } else {
                $cname_data['classroom_equipment_name'] = [];
            }
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }


    }

    //删除机构教室
    public static function deljgClassroomr($classroom_id)
    {
        $where = [
            'id' => ['in', $classroom_id]
        ];
        $upData = [
            'is_del' => 2,
            'update_time' => time(),
        ];
        $table = request()->controller();
        $res = Crud::setUpdate($table, $where, $upData);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取教室分类列表
    public static function getjgClassroomrTypelist($mem_id)
    {
        $where1 = [
            'type' => 1,
            'is_del' => 1,
            'mem_id' => $mem_id,
        ];
        $table1 = 'classroom_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id,name', $order = 'sort desc', $page = '1', $pageSize = '10000');
        if ($type_name_list) {
            return jsonResponseSuccess($type_name_list);
        } else {
            throw new NothingMissException();
        }
    }

    //获取教室设备表 yx_classroom_equipment
    public static function getjgClassroomEquipment($mem_id)
    {
        $where = [
//            'type' => 1,
            'is_del' => 1,
            'mem_id' => $mem_id,
        ];
        $table = 'classroom_equipment';
        $equipment_list = Crud::getData($table, $type = 2, $where, $field = 'id,equipment_name', $order = 'sort desc', $page = '1', $pageSize = '10000');
        if ($equipment_list) {
            return jsonResponseSuccess($equipment_list);
        } else {
            throw new NothingMissException();
        }
    }

    //获取分类教室名称列表
    public static function getjgClassroomrTypesearch($mem_id = '')
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
                    if (isset($mem_id) && !empty($mem_id)) {
                        $where = [
                            'is_del' => 1,
                            'mem_id' => $mem_id,
                            'type_id' => $v['value'],
                        ];
                    } else {
                        $where = [
                            'is_del' => 1,
                            'mem_id' => $user_data['mem_id'],
                            'type_id' => $v['value'],
                        ];
                    }
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

    //添加教室分类 classroom_type
    public static function addClassroomType()
    {
        $data = input();
        $category_data = Crud::setAdd('classroom_type', $data);
        if ($category_data) {
            return $category_data;
        }
    }

    //修改教室分类
    public static function editClassroomType()
    {
        //先查询设备关联表中是否存在，如存在直接删除
        $data = input();
        $where = [
            'id' => $data['classroom_type_id'],
        ];
        unset($data['classroom_type_id']);
        //删除已有的值
        $classroom_type_del = Crud::setUpdate('classroom_type', $where, $data);
        if ($classroom_type_del) {
            return jsonResponseSuccess($classroom_type_del);
        } else {
            throw new UpdateMissException();
        }

    }

    //获取二级分类名
    public static function getCategorySmall($classroom_type_id)
    {
        $classroom_type_name_data = [];
        foreach ($classroom_type_id as $k => $v) {
            $classroom_type_name = Crud::getData('category_small', '1', ['id' => $v[1]], 'category_small_name name');
            $classroom_type_name_data[] = $classroom_type_name['name'];
        }
        $classroom_type_name_info = implode(",", $classroom_type_name_data);
        return $classroom_type_name_info;

    }


    //组合课目分类及教室名称
    public static function getjgClassroomrTypesearchDrop($mem_id = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $mem_id = ['in', $mem_ids];
            }
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $mem_id,
                ];
                $children = Crud::getData('category_small', $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                    foreach ($children as $kk => $vv) {
                        $where = [
                            'is_del' => 1,
                            'type' => 1,
                            'mem_id' => $mem_id,
                            'classroom_type_name' => ['like', '%' . $vv['label'] . '%']
                        ];
                        $curriculum_info = Crud::getData('classroom', 2, $where, $field = 'id value,classroom_name label', $order = '', $page = '1', $pageSize = '100000000');
                        if ($curriculum_info) {
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                        } else {
                            $info[$k]['children'][$kk]['children'] = [];
                        }
                    }
                } else {
                    $info[$k]['children'] = [];
                }

            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }


}