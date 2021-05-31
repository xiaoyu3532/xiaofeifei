<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 16:07
 */

namespace app\validate;


use think\Exception;
use think\Request;
use think\Validate;

class BaseValidate extends Validate
{
    public function goCheck(){
        //获取所有参数
        $request = Request::instance();
        $params = $request->param();
        $result = $this->batch()->check($params);
//        $result = $this->check($params);
        if(!$result){
            $e = new ParameterException([
                'msg'=>$this->error
            ]);
            throw  $e;

        }else{
            return true;
        }
    }

}