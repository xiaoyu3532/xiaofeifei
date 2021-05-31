<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/18 0018
 * Time: 9:55
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class SyntheticalClassroom extends BaseController
{
    //获取综合体教室
    public static function getpcSyntheticalClassroom($page = '1', $name = '', $syntheticalcn_id = '')
    {
        $where = [
            'c.is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        //综合体名称搜索
        (isset($syntheticalcn_id) && !empty($syntheticalcn_id)) && $where['c.syntheticalcn_id'] = $syntheticalcn_id;
        $join = [
            ['yx_classroom_type ct', 'c.type_id = ct.id', 'left'], //right
            ['yx_synthetical_name sn', 'c.syntheticalcn_id = sn.id', 'left'], //综合体列表名称
        ];
        $alias = 'c';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.create_time desc', $field = 'c.name,c.province,c.city,c.area,c.address,c.brief,c.img,c.create_time,c.id cla_id,ct.name type_name,sn.name snname', $page);
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

    //添加综合体教室
    public static function addpcSyntheticalClassroom()
    {
        $data = input();
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
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //修改综合体教室
    public static function setpcSyntheticalClassroom()
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
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取综合体教室详情
    public static function getpcSyntheticalClassroomdetails($cla_id)
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
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'c.name,c.province,c.city,c.area,c.address,c.brief,c.img,c.longitude,c.latitude,c.type_id,c.syntheticalcn_id');
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

    //删除综合体教室
    public static function delpcSyntheticalClassroom($cla_id)
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
    public static function getpcSyntheticalTypelist()
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

    //获取分类面分类的教室
    public static function getpcSyntheticalClassroomType()
    {
        $table1 = 'classroom_type';
        $table2 = 'synthetical_classroom';
        $where1 = [
            'is_del' => 1,
            'type' => 1
        ];
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id value,name label', $order = 'sort desc', 1, 10000);
        foreach ($type_name_list as $kk => $vv) {
            $where2 = [
                'is_del' => 1,
                'type_id' => $vv['value'],
            ];
            $syntheticalName_data = Crud::getData($table2, $type = 2, $where2, $field = 'id value,name label', $order = '', 1, 10000);
            $type_name_list[$kk]['children'] = $syntheticalName_data;
        }
        if ($type_name_list) {
            return jsonResponseSuccess($type_name_list);
        } else {
            throw new NothingMissException();
        }


    }

    //教室分类
    public static function getpcSyntheticalTypesearch(){

    }


}