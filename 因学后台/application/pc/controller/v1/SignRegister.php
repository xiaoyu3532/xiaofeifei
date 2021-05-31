<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7 0007
 * Time: 15:21
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class SignRegister extends BaseController
{
    //获取签到登记记录
    public static function getpcSignRegister($page = 1)
    {
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, '1=1', $field = '*', $order = '', $page);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }


}