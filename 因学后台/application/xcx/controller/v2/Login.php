<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;


/**
 * 登录
 */
class Login extends Base
{
    protected $exceptTicket = ['accessToken', 'accessTokens'];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes:
     * @Author: asus
     * @Date: 2020/5/19
     * @Time: 17:39
     * @Interface accessToken
     * @return string
     * @throws NothingMissException
     */
    public function accessToken()
    {
        $code = input('post.code');
        if (empty($code)) {
            return returnResponse('1001', '缺少code参数');
        }
        $iv = input('post.iv');
        if (empty($iv)) {
            return returnResponse('1001', '缺少iv参数');
        }

        $encryptedData = input('post.encryptedData');
        if (empty($encryptedData)) {
            return returnResponse('1001', '缺少encryptedData参数');
        }

        $openId = $this->wx_third($code);
        if (empty($openId)) {
            return returnResponse('1002', '授权失败');
        }
        $unionid = $this->decryptData($openId['session_key'], $encryptedData, $iv);
        if (empty($unionid)) {
            return returnResponse('1002', '授权失败');
        }
        if (empty($unionid['openId'])) {
            return returnResponse('1002', '授权失败', $unionid);
        }
        $where = [
            'x_openid' => $unionid['openId'],
            'is_del' => 1
        ];

        $userInfo = Crud::getData("user", 1, $where, 'id');

        $accessToken = md5(Uuid::uuid4());
        $expireAt = time() + 30 * 24 * 60 * 60;
        $identi = time() . rand(999, 9999);
        Db::startTrans();
        try {
            if (empty($userInfo)) {
                //用户不存在 注册账号 与session
                $addUser = Crud::setAdd("user", ['user_identifier' => $identi, 'x_openid' => $unionid['openId'], 'unionid' => $unionid['unionId'], 'name' => $unionid['nickName'], 'img' => $unionid['avatarUrl'], 'sex' => $unionid['gender']], 2);
                if (empty($addUser)) {
                    throw new Exception("用户注册失败");
                }
                $addSession = Crud::setAdd('user_session', ['user_id' => $addUser, 'access_token' => $accessToken, 'expire_at' => $expireAt], 1);
                if (empty($addSession)) {
                    throw new Exception("生成Token失败");
                }
            } else {
                //更新token或者添加头肯
                $token = Crud::getData("user_session", 1, ['user_id' => $userInfo['id'], 'is_del' => 1], "id");
                if (empty($token)) {
                    $addToken = Crud::setAdd('user_session', ['access_token' => $accessToken, 'user_id' => $userInfo['id'], 'expire_at' => $expireAt], 1);
                    if (empty($addToken)) {
                        throw new Exception("更新Token失败");
                    }
                } else {
                    $updateSession = Crud::setUpdate('user_session', ['user_id' => $userInfo['id']], ['access_token' => $accessToken, 'expire_at' => $expireAt]);
                    if (empty($updateSession)) {
                        throw new Exception("更新Token失败");
                    }
                }

            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse("1002", $e->getMessage());
        }
        return returnResponse('1000', '登陆成功', [
            'access_token' => $accessToken
        ]);


    }


    /**
     * @Notes: 获取用户openid
     * @Author: asus
     * @Date: 2020/5/20
     * @Time: 11:35
     * @Interface wx_third
     * @param $code
     * @return bool|mixed|string
     */
    public function wx_third($code)
    {
        $config = config('wx');
        $result = \pinmeng\Http::request('GET', 'https://api.weixin.qq.com/sns/jscode2session', [
            'query' => [
                'appid' => $config['appid'],
                'secret' => $config['secret'],
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            ]
        ], 'json');
        return $result;
    }

    /**
     * @Notes: 获取unionid
     * @Author: asus
     * @Date: 2020/5/26
     * @Time: 14:13
     * @Interface decryptData
     * @param $sessionKey
     * @param $encryptedData
     * @param $iv
     * @return int|mixed
     */
    function decryptData($sessionKey, $encryptedData, $iv)
    {

        $config = config('wx');

        $IllegalAesKey = -41001;
        $IllegalIv = -41002;
        $IllegalBuffer = -41003;
        $DecodeBase64Error = -41004;

        if (strlen($sessionKey) != 24) {
            return $IllegalAesKey;
        }

        $aesKey = base64_decode(str_replace(" ", "+", $sessionKey));
        if (strlen($iv) != 24) {
            return $IllegalIv;
        }
        $aesIV = base64_decode(str_replace(" ", "+", $iv));
        $aesCipher = base64_decode(str_replace(" ", "+", $encryptedData));
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            return $IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $config['appid']) {
            return $DecodeBase64Error;
        }
        $data = json_decode($result, true);
        return $data;
    }

}