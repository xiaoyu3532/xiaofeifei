<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/6 0006
 * Time: 13:53
 */

namespace app\nxz\controller\v1;

use think\Db;
use app\common\model\Crud;

class Logon extends Base
{

    //获取微信信息
    public function setWeChatInfo()
    {
        $code = input('code');
        $appid = "wxf47f6fd95e94366e";
        $secret = "1b1b6e96026a679dd159de4408361fb1";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=" . $code . "&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $obj = json_decode($abs);
        if (isset($obj->errcode)) {
            return $this->jsonResponse('3000', $obj->errmsg);
        }
        $access_token = $obj->access_token;
        $openid = $obj->openid;
        $abs_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $abs_url_data = file_get_contents($abs_url);
        $obj_data = json_decode($abs_url_data);
        if (isset($obj_data->errcode)) {
            return jsonResponse('2000', $obj_data->errmsg);
        }
        $data['openid'] = $obj_data->openid;
        if ($data['openid'] == '') {
            return jsonResponse('2000', '添加失败');
        }
        $data['name'] = $obj_data->nickname;
        $data['sex'] = $obj_data->sex;
        $data['img'] = $obj_data->headimgurl;
        $data['province'] = $obj_data->province;
        $data['city'] = $obj_data->city;
        $data['unionid'] = $obj_data->unionid;
        $data['create_time'] = time();
        $data['user_status'] = 1;//逆行者用户进入
//        $info = Db::name('user')->where(['openid' => $data['openid']])->find();
        $info = Db::name('user')->where(['unionid' => $data['unionid']])->find();
        if (!empty($info)) {
            if (empty($info['openid'])) {
                Db::name('user')->where(['unionid' => $data['unionid']])->update(['openid' => $data['openid']]);
            }
            return jsonResponseSuccess(['id' => $info['id']]);
        } else {
            $id = Db::name('user')->insertGetId($data);
            if ($id) {
                return jsonResponseSuccess(['id' => $info['id']]);

            } else {
                return jsonResponse('2000', '添加失败');
            }
        }
    }
    //验证API ID
    public function verId()
    {
        $data = input();
        $where = [
            'id' => $data['id'],
            'is_del' => 1,
            'type' => 1,
        ];
        $data = Crud::getData('user', $type = 1, $where, 'id');
        if (!$data) {
            return jsonResponse('2000', '目前没有内容');
        } else {
            return jsonResponseSuccess($data);
        }
    }
    //分享时使用
    public function share()
    {
        $url = input('url');
        vendor('Classes.jssdk');
        $jssdk = new \JSSDK("wxf47f6fd95e94366e", "1b1b6e96026a679dd159de4408361fb1");
        $signPackage = $jssdk->GetSignPackage($url);
        return json($signPackage);
    }


}