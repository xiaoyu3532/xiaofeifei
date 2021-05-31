<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 17:17
 */

namespace app\lib\exception;


//统一描述错误信息
use think\Exception;
use Throwable;

class BaseException extends Exception
{
    //HTTP 状态码 400,200
    public $code = 400;
    //错误具体信息
    public $msg = '参数错误';
    //自定义的错误码
    public $errorCode = 10000;

    public function __construct($params = [])
    {
        //判断传过来的是否是数组
       if(!is_array($params)){
           return ;
       }
       //查看数组中是否有对应的值
       if(array_key_exists('code',$params)){
            $this->code = $params['code'];
       }
        if(array_key_exists('msg',$params)){
            $this->msg = $params['msg'];
        }
        if(array_key_exists('errorCode',$params)){
            $this->errorCode = $params['errorCode'];
        }
    }

}