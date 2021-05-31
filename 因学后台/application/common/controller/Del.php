<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 18:04
 */

namespace app\common\controller;


use app\common\model\Crud;
use app\lib\exception\DelMissException;
use app\validate\IDMustBePostiveInt;

class Del
{
    public static function setDel($table, $id)
    {
        (new IDMustBePostiveInt())->goCheck();
        $where = [
            'id' => $id
        ];
        $upData = [
            'is_del' => 2
        ];
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new DelMissException();
        } else {
            return $info;
        }
    }

}