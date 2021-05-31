<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 15:40
 */

namespace app\xcx\controller\v1;
use app\lib\exception\IndexMissException;
use app\validate\IDMustBePostiveInt;
use app\validate\IDValidate;
use app\xcx\model\Index as IndexModel;
use think\Collection;

class Index extends Collection
{
    public function getIndex($id)
    {
        (new IDMustBePostiveInt())->goCheck();
        $info = IndexModel::getIndex($id);
        if(!$info){
            throw new IndexMissException();
//            throw new Exception('不想告诉你');
        }

        dump($info);
//        $data = [
//            'id' => 1.2
//        ];


    }

}