<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 15:47
 */

namespace app\validate;


use think\Validate;

class JGLoginupMustBePostiveInt extends BaseValidate
{
    protected $rule = [
        'cname'=>'require',
        'nickname'=>'require',
        'phone'=>'require',
        'username'=>'require',
        'password'=>'require',
//        'tow_password'=>'require',
//        'type'=>'require',
    ];

    protected function isPositiveInteger($value,$rule='',$data='',$field=''){
        if (is_numeric($value) && is_int($value+0) && ($value+0)>0 ){
            return true;
        }else{
            return $field.'必须是正整数';
        }
    }

}