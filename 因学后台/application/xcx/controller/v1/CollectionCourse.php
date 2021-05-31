<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 20:05
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;

class CollectionCourse
{
    //获取收藏课程
    public static function getCollectionCourse($user_id){
        $where = [
            'user_id'=>$user_id,
            'is_del'=>1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,name,img,status,content');
        if(!$info){
            throw new ActivityMissException();
        }else{
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}