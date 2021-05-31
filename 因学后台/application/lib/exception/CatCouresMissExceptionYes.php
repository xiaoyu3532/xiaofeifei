<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class CatCouresMissExceptionYes extends BaseException
{
    public $code = 200;
    public $msg = '你已加入购物车';
    public $errorCode =10004;
}