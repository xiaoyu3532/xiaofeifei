<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 17:20
 */

namespace app\lib\exception;


class IndexMissException extends BaseException
{
    public $code = 200;
    public $msg = '请求首页不存在';
    public $errorCode =10000;

}