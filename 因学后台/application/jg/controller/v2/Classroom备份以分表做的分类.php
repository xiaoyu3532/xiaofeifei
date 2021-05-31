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
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Classroom extends BaseController
{
    //获取机构教室
    public static function getjgClassroom($name = '', $page = '1', $mem_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] != 2 && $account_data['type'] != 7) {
            throw new ISUserMissException();
        }
        $where = [
            'c.is_del' => 1,
            'm.is_del' => 1,
            'm.status' => 1,
        ];

        //如果机构总账号可以看到被绑定的子机构教室
        if ($account_data['type'] == 2) {
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
        } else {
            $where['c.mem_id'] = $mem_id;
        }

        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        $join = [
            //['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //right
        ];
        $alias = 'c';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.name,c.province,c.city,c.area,c.address,c.brief,c.img,c.create_time,c.contain_number,c.area_number,c.fit_age,m.cname,m.province,m.city,m.area,m.address', $page);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) {
                if (!empty($v['img'])) {
                    $imgs = unserialize($v['img']);
                    $cname_data[$k]['img'] = $imgs[0];
                }
                //查看关联分类 yx_classroom_category_small_relation

                $where_category_small = [
                    'csr.classroom_id' => $v['id'],
                    'csr.is_del' => 1,
                    'cs.is_del' => 1,
                ];
                $join = [
                    ['yx_category c', 'csr.category_small_id = c.id', 'left'], //关联分类名称
                ];
                $alias = 'csr';
                $category_small_relation = Crud::getRelationData('classroom_category_relation', $type = 2, $where_category_small, $join, $alias, '', 'c.name', 1, 1000);
                if ($category_small_relation) {
                    $cname_data[$k]['category'] = implode(",", $category_small_relation);
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
        if ($user_data['type'] == 2) { //1用户，2机构
            $data['mem_id'] = $user_data['mem_id'];
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
        $classroom_id = Crud::setAdd($table, $data, 2);
        if ($classroom_id) {
            //添加教室科目分类  $data['category']
            self::addCategory($data['category'], $classroom_id);
            //添加教室设备 $data['equipment']
            self::addEquipment($data['equipment'], $classroom_id);
            return jsonResponseSuccess($classroom_id);
        } else {
            throw new AddMissException();
        }
    }

    //修改机构教室
    public static function setjgClassroom()
    {
        $data = input();
        $where = [
            'id' => $data['cla_id']
        ];
        unset($data['cla_id']);
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
        //修改课程分类
        self::editCategory($data['category'], $data['cla_id']);
        //修改课程设备
        self::editEquipment($data['equipment'], $data['cla_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
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
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'c.name,c.province,c.city,c.area,c.address,c.brief,c.img,c.longitude,c.latitude,c.type_id');
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

    //获取教室设备表 yx_classroom_equipment
    public static function getjgClassroomEquipment()
    {
        $where = [
//            'type' => 1,
            'is_del' => 1,
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

    //添加教室分类
    public static function addCategory($category, $classroom_id)
    {
        if (!empty($category) && is_array($category)) {
            foreach ($category as $k => $v) {
                $add_category = [ //yx_classroom_category_relation
                    'classroom_id' => $classroom_id,
                    'category_id' => $v,
                    'create_time' => time(),
                ];
                $category_data = Crud::setAdd('classroom_category_relation', $add_category);
                if ($category_data) {
                    return $category_data;
                }
            }
        }
    }

    //修改教室分类
    public static function editCategory($category, $classroom_id)
    {
        //先查询设备关联表中是否存在，如存在直接删除
        if (!empty($category) && is_array($category)) {
            $where = [
                'classroom_id' => $classroom_id,
                'category_id' => ['in', $category],
                'is_del' => 1,
            ];
            $category_info = Crud::getData('classroom_category_relation', 2, $where, $field = 'id', '', 1, 10000000);
            if ($category_info) {
                //删除已有的值
                $equipment_del = Crud::setUpdate('classroom_category_relation', $where, ['is_del' => 2]);
                if ($equipment_del) {
                    $category_data = self::addCategory($category, $classroom_id);
                    return $category_data;
                }
            } else {
                $category_data = self::addCategory($category, $classroom_id);
                return $category_data;
            }
        }
    }

    //添加教室设备列
    public static function addEquipment($equipment, $classroom_id)
    {
        if (!empty($equipment) && is_array($equipment)) {
            foreach ($equipment as $k => $v) {
                $add_equipment = [ //yx_classroom_equipment_relation
                    'classroom_id' => $classroom_id,
                    'equipment_id' => $v,
                    'create_time' => time(),
                ];
                $equipment_data = Crud::setAdd('classroom_equipment_relation', $add_equipment);
                if ($equipment_data) {
                    return $equipment_data;
                }
            }
        }
    }

    //修改教室设备
    public static function editEquipment($equipment, $classroom_id)
    {
        //先查询设备关联表中是否存在，如存在直接删除
        if (!empty($equipment) && is_array($equipment)) {
            $where = [
                'classroom_id' => $classroom_id,
                'equipment_id' => ['in', $equipment],
                'is_del' => 1,
            ];
            $equipment_info = Crud::getData('classroom_equipment_relation', 2, $where, $field = 'id', '', 1, 10000000);
            if ($equipment_info) {
                //删除已有的值
                $equipment_del = Crud::setUpdate('classroom_equipment_relation', $where, ['is_del' => 2]);
                if ($equipment_del) {
                    $equipment_data = self::addEquipment($equipment, $classroom_id);
                    return $equipment_data;
                }
            } else {
                $equipment_data = self::addEquipment($equipment, $classroom_id);
                return $equipment_data;
            }
        }

    }

}