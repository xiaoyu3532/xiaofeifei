<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7 0007
 * Time: 14:14
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use think\Cache;

class SignRegister
{
    //签到登记
    public static function addSignRegister()
    {
        $data = input();
        $code1 = str_replace(" ", '', $data['code']);
        $code = Cache::get($data['phone']);
        if ($code1 != 3536) { //测试验证码 3536
            if ($code != $code1) {
                return jsonResponse('2000', '验证码错误，请重试');
            }
        }
        $table = request()->controller();
        unset($data['code']);
        //添加要用户
        $cat_data = Crud::setAdd($table, $data);
        if ($cat_data) {
            return jsonResponseSuccess($cat_data);
        }
    }
}