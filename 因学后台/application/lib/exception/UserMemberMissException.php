<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class UserMemberMissException extends BaseException
{
    public $code = 200;
    public $msg = '没有此用户,请进行注册';
    public $errorCode =10000;

}