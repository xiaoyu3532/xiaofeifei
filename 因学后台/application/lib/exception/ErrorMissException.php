<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class ErrorMissException extends BaseException
{
    public $code = 200;
    public $msg = '信息有误';
    public $errorCode =10001;

}