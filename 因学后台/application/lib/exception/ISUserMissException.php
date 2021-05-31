<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class ISUserMissException extends BaseException
{
    public $code = 200;
    public $msg = '用户信息有误,请重新登录';
    public $errorCode =100051;

}