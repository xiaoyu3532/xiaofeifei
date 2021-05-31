<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/6 0006
 * Time: 14:44
 */

namespace app\xcx\controller\v1;

use app\validate\PhoneMustBePostiveInt;
use think\Cache;
class PhoneCode
{    //获取验证码
    public static function getPhoneCode($phone) {
        (new PhoneMustBePostiveInt())->goCheck();
        $str = '1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr, 0, 4);//substr(string,start,length);返回字符串的一部分
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($phone, $content);
        if(!empty($response)){
            Cache::set($phone,$code,900);
            return jsonResponse('1000',$response,'验证码发送成功');
        }
    }



}