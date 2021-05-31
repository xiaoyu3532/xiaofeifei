<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/11 0011
 * Time: 16:54
 */

namespace app\jg\controller\v2;


use think\Collection;

class Obtain extends Collection
{
    //获取用户Openid(公众号)
    public static function getOpenID()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        $code = input('code');
        $appid = "wxf47f6fd95e94366e";
        $secret = "1b1b6e96026a679dd159de4408361fb1";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=" . $code . "&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $obj = json_decode($abs);
        if (isset($obj->errcode)) {
            return jsonResponse('3000', $obj->errmsg);
        }
        $access_token = $obj->access_token;
        $openid = $obj->openid;
        $abs_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $abs_url_data = file_get_contents($abs_url);
        $obj_data = json_decode($abs_url_data);
        if (isset($obj_data->errcode)) {
            return jsonResponse('3000', $obj_data->errmsg);
        }
        $inof = ['openId'=>$obj_data->openid];
        return jsonResponseSuccess($inof);
    }

}