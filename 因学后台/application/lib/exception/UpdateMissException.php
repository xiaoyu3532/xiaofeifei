<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class UpdateMissException extends BaseException
{
    public $code = 200;
    public $msg = '修改失败';
    public $errorCode =10001;

}