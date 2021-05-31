<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/28 0028
 * Time: 16:41
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;
use app\lib\exception\EditRecoMissException;

class EditReco
{
    public function getEditReco($page =1){
        $where = [
            'is_del'=>1,
            'type'=>1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'img,status,title,price', $order = '', $page );
        if(!$info){
            throw new EditRecoMissException();
        }else{
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}