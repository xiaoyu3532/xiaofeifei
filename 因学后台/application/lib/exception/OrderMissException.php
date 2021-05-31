<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class OrderMissException extends BaseException
{
    public $code = 200;
    public $msg = '您没有课程';
    public $errorCode =10000;

}