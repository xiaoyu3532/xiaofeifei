<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/23 0023
 * Time: 9:51
 */

namespace app\lib\exception;


class OrderMissExceptionStock extends BaseException
{
    public $code = 200;
    public $msg = '库存不足';
    public $errorCode =10007 ;
}