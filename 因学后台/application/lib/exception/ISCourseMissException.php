<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class ISCourseMissException extends BaseException
{
    public $code = 404;
    public $msg = '目前课程有误';
    public $errorCode =10000;

}