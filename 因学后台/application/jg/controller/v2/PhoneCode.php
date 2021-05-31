<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/11 0011
 * Time: 9:26
 */

namespace app\jg\controller\v2;


use app\validate\PhoneMustBePostiveInt;
use app\validate\JGLoginupMustBePostiveInt;
use think\Controller;
use think\Cache;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class PhoneCode extends Controller
{
    public static function getPhoneCode($phone)
    {
        (new PhoneMustBePostiveInt())->goCheck();
//        (new JGLoginupMustBePostiveInt())->goCheck();
        $str = '1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr, 0, 4);//substr(string,start,length);返回字符串的一部分
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($phone, $content);
        if (!empty($response)) {
            Cache::set($phone, $code, 900);
            return jsonResponse('1000', $response, '验证码发送成功');
        }
    }

}