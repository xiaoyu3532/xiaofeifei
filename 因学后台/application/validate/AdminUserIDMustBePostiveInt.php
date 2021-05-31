<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5 0005
 * Time: 15:47
 */

namespace app\validate;


use think\Validate;

class AdminUserIDMustBePostiveInt extends BaseValidate
{
    protected $rule = [
        'admin_user_id'=>'require',
    ];
}