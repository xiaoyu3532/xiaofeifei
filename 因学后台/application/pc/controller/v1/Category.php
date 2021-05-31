<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 15:39
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class Category extends BaseController
{
    //获取课程大分类
    public static function getpcCategory($page = '1',$name=''){
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

    //获取课程大分类详情
    public static function getpcCategorydetails($category_id){
        $where = [
            'is_del'=>1,
            'type'=>1,
            'id'=>$category_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //添加课程大分类
    public static function addpcCategory(){
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //修改课程大分类
    public static function setpcCategory(){
        $data = input();
        $where = [
            'id'=>$data['category_id']
        ];
        unset($data['category_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //删除课程大分类
    public static function delpcCategory(){
        $data = input();
        $where = [
            'id'=>$data['category_id']
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

    //获取课程大分类
    public static function getpcCategorysearch($page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    //组合课程分类
    public static function getpcgroupCategory()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'pid' => $v['value'],
                ];
                $table = 'category_small';

                $children= Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if($children){
                    $info[$k]['children'] = $children;
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }

    //组合课目分类及课目名称
    public static function getpcgroupCategoryCurriculum()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            $mem_data = self::isuserData();
            if ($mem_data['type'] == 6) {
                $mem_id = $mem_data['mem_id'];
            }
            $table1 = 'category_small';
            $table2 = 'curriculum';
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'pid' => $v['value'],
                ];
                $children= Crud::getData($table1, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if($children){
                    $info[$k]['children'] = $children;
                    foreach ($children as  $kk=>$vv){
                        $where = [
                            'is_del' => 1,
                            'type' => 1,
                            'mid' =>$mem_id,
                            'csid' =>$vv['value'],
                        ];
                        $curriculum_info = Crud::getData($table2, $type = 2, $where, $field = 'id value,name label', $order = '', $page = '1', $pageSize = '1000');
                        $info[$k]['children'][$kk]['children'] =$curriculum_info;
                    }
                }
            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }


}