<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/12 0012
 * Time: 15:31
 */

namespace app\pc\controller\v1;
use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class StudyType extends BaseController
{
    //获取能力大分类
    public static function getpcStudyType($page = '1',$name=''){
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

    //获取能力大分类详情
    public static function getpcStudyTypedetails($studytype_id){
        $where = [
            'is_del'=>1,
            'type'=>1,
            'id'=>$studytype_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //添加能力大分类
    public static function addpcStudyType(){
        $data = input();
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //修改能力大分类
    public static function setpcStudyType(){
        $data = input();
        $where = [
            'id'=>$data['studytype_id']
        ];
        unset($data['studytype_id']);
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if(!$info){
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

    //删除能力大分类
    public static function delpcStudyType(){
        $data = input();
        $where = [
            'id'=>$data['studytype_id']
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


    //获取能力大分类搜索
    public static function getpcStudyTypesearch($page = '1')
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


    //获取能力大小分类
    public static function getpcgroupStudyType($page = '1')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
        if (!$info) {
            throw new NothingMissException();
        } else {
            $table = 'study_type_son';
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'st_id' => $v['value'],
                ];
                $children = Crud::getData($table, $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                }
            }
            return jsonResponseSuccess($info);
        }
    }

}