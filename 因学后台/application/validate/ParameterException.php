<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 19:34
 */

namespace app\validate;


use app\lib\exception\BaseException;

class ParameterException extends BaseException
{
    public $code = 200;
    public $msg = '参数错误';
    public $errorCode = 10000;
}