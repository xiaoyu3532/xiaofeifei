<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 13:28
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class ClassroomType extends BaseController
{
    //获取学习分类
    public static function getpcClassroomType($page = '1',$name=''){
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

    //获取教室分类详情
    public static function getpcClassroomTypedetails($cla_type_id){
        $where = [
            'is_del'=>1,
            'type'=>1,
            'id'=>$cla_type_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //添加教室分类
    public static function addpcClassroomType(){
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //修改教室分类
    public static function setpcClassroomType(){
        $data = input();
        $where = [
            'id'=>$data['cla_type_id']
        ];
        unset($data['cla_type_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //删除教室分类
    public static function delpcClassroomType(){
        $data = input();
        $where = [
            'id'=>$data['cla_type_id']
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