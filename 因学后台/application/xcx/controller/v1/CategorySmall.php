<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 11:07
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\CategoryMissException;

class CategorySmall
{
   //获取小分类
    public function getCategorySmall(){
        $where = [
            'is_del'=>1,
            'type'=>1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name', $order = 'sort asp');
        if(!$info){
            throw new CategoryMissException();
        }else{
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }
}