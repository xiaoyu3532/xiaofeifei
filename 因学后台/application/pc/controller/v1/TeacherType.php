<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 13:59
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class TeacherType extends BaseController
{
    //获取老师分类列表
    public static function getpcTeacherType($page = '1',$name=''){
        $where = [
            'is_del'=>1,
            'type'=>1,
        ];
        (isset($name) && !empty($name)) && $where['name'] = ['like', '%' . $name . '%'];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page);
        if(!$info){
            throw new NothingMissException();
        }else{
            $num = Crud::getCounts($table, $where, $type = '1');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取老师分类详情
    public static function getpcTeacherTypedetails($tea_type_id){
        $where = [
            'is_del'=>1,
            'type'=>1,
            'id'=>$tea_type_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //添加老师分类
    public static function addpcTeacherType(){
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //修改老师分类
    public static function setpcTeacherType(){
        $data = input();
        $where = [
            'id'=>$data['tea_type_id']
        ];
        unset($data['tea_type_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //删除老师分类
    public static function delpcTeacherType(){
        $data = input();
        $where = [
            'id'=>$data['tea_type_id']
        ];
        $data = [
            'is_del'=>2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }
}