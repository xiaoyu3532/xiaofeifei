<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 15:47
 */

namespace app\validate;


use think\Validate;

class CatCourseMustBePostiveInt extends BaseValidate
{
    protected $rule = [
        'user_id'=>'require|isPositiveInteger',
        'mem_id'=>'isPositiveInteger',
//        'cou_id'=>'require',
        'status'=>'isPositiveInteger',
    ];

    protected function isPositiveInteger($value,$rule='',$data='',$field=''){
        if (is_numeric($value) && is_int($value+0) && ($value+0)>0 ){
            return true;
        }else{
            return $field.'必须是正整数';
        }
    }

}