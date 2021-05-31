<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 13:55
 */

namespace app\lib\exception;


class EditRecoMissException extends BaseException
{
    public $code = 200;
    public $msg = '无编辑推荐';
    public $errorCode =10000;

}