<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 9:47
 */

namespace app\jg\controller\v1;


use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class Teacher extends BaseController
{
    //获取机构老师
    public static function getjgTeacher($page = '1', $pageSize = '16',$name='')
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 4) { //1用户，2机构
            $where = [
                't.is_del' => 1,
//                'tt.is_del' => 1,
//                'tt.status' => 1,
                't.mem_id' => $user_data['mem_id'],
            ];
        }
        (isset($name) && !empty($name)) && $where['t.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_teacher_type tt', 't.type_id = tt.id', 'left'],
        ];
        $alias = 't';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 't.create_time desc', $field = 't.name,t.brief,t.img,t.create_time,t.id tea_id,tt.name typename', $page, $pageSize);
        if ($cname_data) {
            foreach ($cname_data as $k=>$v){
                if(!empty($v['img'])){
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

    //添加机构老师
    public static function addjgTeacher(){
        $data = input();
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $data['mem_id'] = $user_data['mem_id'];
        }
        if (isset($data['img']) && !empty($data['img'])) {
            $mlicense_array = [];
            foreach ($data['img'] as $k=>$v){
                if(isset($v['response'])){
                    $mlicense_array[]=  $v['response'];
                }else{
                    $mlicense_array[]=  $v['url'];
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

    //修改机构老师
    public static function setjgTeacher(){
        $data = input();
        $where = [
            'id'=>$data['tea_id']
        ];
        unset($data['tea_id']);
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

    //获取机构老师详情
    public static function getjgTeacherdetails($tea_id)
    {
        $where = [
            't.type' => 1,
            't.is_del' => 1,
            't.id' => $tea_id
        ];
        $join = [
            ['yx_member m', 't.mem_id = m.uid', 'left'],
        ];
        $alias = 't';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 't.name,t.brief,t.img,t.type_id');
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
            return jsonResponseSuccess($cname_data);
        }else{
            throw new NothingMissException();
        }


    }

    //删除老师
    public static function deljgTeacher($tea_id)
    {
        $where = [
            'id' => $tea_id
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
    public static function getjgTeacherTypelist(){
        $where1 = [
            'type'=>1,
            'is_del'=>1,
        ];
        $table1 = 'teacher_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id,name',$order = 'sort desc', $page = '1', $pageSize = '10000');
        if($type_name_list){
            return jsonResponseSuccess($type_name_list);
        }else{
            throw new NothingMissException();
        }
    }
    //老师分类列表及老师姓名
    public static function getjgTeacherTypesearch(){
        $where1 = [
            'type'=>1,
            'is_del'=>1,
        ];
        $table1 = 'teacher_type';
        $type_name_list = Crud::getData($table1, $type = 2, $where1, $field = 'id value,name label',$order = 'sort desc', $page = '1', $pageSize = '10000');
        if($type_name_list){
            $user_data = self::isuserData();
            if ($user_data['type'] == 2) { //1用户，2机构
                $table = request()->controller();
                foreach ($type_name_list as $k=>$v){
                    $where = [
                        'is_del' => 1,
                        'mem_id' => $user_data['mem_id'],
                        'type_id' => $v['value'],
                    ];
                    $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
                    $type_name_list[$k]['children'] =$info;
                }
            }
            return jsonResponseSuccess($type_name_list);
        }else{
            throw new NothingMissException();
        }
    }



}