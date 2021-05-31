<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 17:13
 */

namespace app\lib\exception;


use Exception;
use think\exception\Handle;
use think\Log;
use think\Request;

/**
 * 全局异常处理
 * Class ExceptionHandler
 * @package app\lib\exception
 */
class ExceptionHandler extends Handle
{
    protected $code;
    protected $msg;
    protected $errorCode;

    //需要返回客户端当前请求的URL路径
    public function render(Exception $e)
    {
        if ($e instanceof BaseException) { //判断是否继承BaseException,此类型是返回客户端
            //将$e传过来的 code msg errorCode 赋给上面私有量
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        } else {
            //判断是否是生产模式还是开发模式
            //读取Config中的配置信息 Config::get('app_debug');
            if (config('app_debug')) {
                return parent::render($e);
            } else {
                //不让用户知道详情信息
                $this->code = 500;
                $this->msg = '服务器内部错误，不想告诉你';
                $this->errorCode = 999;
                //错误日志记录
                $this->recordErrorLog($e);
            }
        }
        //获取当前的ULR
        //获取当前请求的实体对像
        $request = Request::instance();
        $result = [
            'msg' => $this->msg,
            'error_code' => $this->errorCode,
            'request_url' => $request->url()
        ];
        return json($result, $this->code);
    }

    //记录日志
    private function recordErrorLog(Exception $e)
    {
        //初始化日志
        Log::init([
            'type' => 'File',
            'path' => LOG_PATH,
            'level' => ['error'], //错误级别，只有高于这个级别的才能进行记录
        ]);
        Log::record($e->getMessage(), 'error');
    }

}