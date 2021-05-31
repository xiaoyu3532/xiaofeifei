<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class CatCouresMissExceptionAdd extends BaseException
{
    public $code = 500;
    public $msg = '购物车添加失败';
    public $errorCode =10002;

}