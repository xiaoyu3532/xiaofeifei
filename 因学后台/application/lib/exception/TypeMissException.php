<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class TypeMissException extends BaseException
{
    public $code = 404;
    public $msg = '状态修改失败';
    public $errorCode =10001;

}