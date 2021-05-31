<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26 0026
 * Time: 18:34
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;

class Address
{
    //获取省用于获取社区
    public function getAddress(){
        $where = [
            'type'=>1,
            'is_del'=>1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,province');
        if(!$info){
            throw new CommunityMissException();
        }else{
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}