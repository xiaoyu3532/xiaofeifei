<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/22 0022
 * Time: 16:38
 */

namespace app\xcx\controller\v1;

use think\Db;

class Login
{
    //微信授权登录
    public function getVx()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name,img,status,content');
        if (!$info) {
            throw new ActivityMissException();
        } else {
//            dump($info);
        }
    }

    //线上使用
    public function getempower()
    {
        $code = input('code');
//        file_put_contents('wxsq.log', print_r($code,true).PHP_EOL);
//        $data['name'] = input('nickName'); //昵称
//        $data['img'] = input('headImage'); //头像
//        $data['province'] = input('province'); //省
//        $data['city'] = input('city'); //市
//        $data['sex'] = input('sex'); //性别
//        $data['create_time'] = time();
//        file_put_contents('wxsq1.log', print_r($data,true).PHP_EOL);
        $appid = "wx53fb433aed08106f";
        $secret = "83a087e62c128c500dc74480328c40d8";
//        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
//        $abs = file_get_contents($url);
//        $obj = json_decode($abs);
//        if (isset($obj->errcode)) {
//            return jsonResponse('6001', $obj->errmsg);
//        }
//        $data['x_openid'] = $obj->openid;
//        if ($data['x_openid'] == '') {
//            return jsonResponse('6001', '获取信息失败');
//        }
        $encryptedData = input('encryptedData');
        $iv = input('iv');
//        if($obj->unionid){
//            $data['unionid'] = $obj->unionid;
//        }else{
//            $datas=$this->decryptData($appid, $secret, $encryptedData, $iv);
//        }
        file_put_contents('wxsq8.log', print_r($encryptedData,true).PHP_EOL);
        file_put_contents('wxsq9.log', print_r($iv,true).PHP_EOL);
        $datas=$this->decryptData($appid, $secret, $encryptedData, $iv,$code);
        file_put_contents('wxsq6.log', print_r($datas,true).PHP_EOL);
dump($datas);exit;
        file_put_contents('wxsq2.log', print_r($data,true).PHP_EOL);
        $info = Db::name('user')->where(['unionid' => $data['unionid']])->find();
//        $Follow_controller = new Follow();
//        $Follow=$Follow_controller->getToken($data['openid']);
        if ($info) {
            if (empty($info['x_openid'])) {
                $update = [
                    'x_openid'=>$data['x_openid']
                ];
                $aa=Db::name('user')->where(['unionid' => $data['unionid']])->update($update);
            }
//            if($Follow ==1){
//                $datas = [
//                    'id'=>$info['id'],
//                    'type'=>1,
//                ];
//                return jsonResponse('1000','获取成功',$datas);
//            }else{
//                $datas = [
//                    'id'=>$info['id'],
//                    'type'=>2, //未关注
//                ];
//                return jsonResponse('1000','获取成功',$datas);
//            }
            $datas = [
                'id' => $info['id'],
                'type' => 2, //未关注
            ];
            return jsonResponse('1000', '获取成功', $datas);
        } else {
            $id = Db::name('user')->insertGetId($data);
            if ($id) {
//                if($Follow ==1){
//                    $datas = [
//                        'id'=>$id,
//                        'type'=>1,
//                    ];
//                    return jsonResponse('1000','获取成功',$datas);
//                }else{
//                    $datas = [
//                        'id'=>$id,
//                        'type'=>2, //未关注
//                    ];
//                    return jsonResponse('1000','获取成功',$datas);
//                }
                $datas = [
                    'id' => $id,
                    'type' => 2, //未关注
                ];
                return jsonResponse('1000', '获取成功', $datas);
            } else {
                return jsonResponse('6001', '获取信息失败');
            }
        }
    }

    //获取微信信息
    public function getempowers()
    {
        $code = input('code');
        $appid = "wx82d1c2e0da1134a2";
        $secret = "e96b33b76aeec3cbdaa4300f4d39fff7";
        //$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=" . $code . "&grant_type=authorization_code";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $obj = json_decode($abs);
        if (isset($obj->errcode)) {
            return jsonResponse('6001', $obj->errmsg);
        }

        $openid = $obj->openid;
        $abs_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $abs_url_data = file_get_contents($abs_url);
        $obj_data = json_decode($abs_url_data);
        if (isset($obj_data->errcode)) {
            return jsonResponse('6001', $obj_data->errmsg);
        }
        $data['openid'] = $obj_data->openid;
        if ($data['openid'] == '') {
            return jsonResponse('6001', '获取信息失败');
        }
        $data['name'] = $obj_data->nickname;
        $data['sex'] = $obj_data->sex;
        $data['img'] = $obj_data->headimgurl;
        $data['province'] = $obj_data->province;
        $data['city'] = $obj_data->city;
        $data['unionid'] = $obj_data->unionid;
        $data['create_time'] = time();
        $info = Db::name('user')->where(['openid' => $data['openid']])->find();
//        $Follow_controller = new Follow();
//        $Follow=$Follow_controller->getToken($data['openid']);
        if (!empty($info)) {
//            if($Follow ==1){
//                $datas = [
//                    'id'=>$info['id'],
//                    'type'=>1,
//                ];
//                return jsonResponse('1000','获取成功',$datas);
//            }else{
//                $datas = [
//                    'id'=>$info['id'],
//                    'type'=>2, //未关注
//                ];
//                return jsonResponse('1000','获取成功',$datas);
//            }
            $datas = [
                'id' => $info['id'],
                'type' => 2, //未关注
            ];
            return jsonResponse('1000', '获取成功', $datas);
        } else {
            $id = Db::name('user')->insertGetId($data);
            if ($id) {
//                if($Follow ==1){
//                    $datas = [
//                        'id'=>$id,
//                        'type'=>1,
//                    ];
//                    return jsonResponse('1000','获取成功',$datas);
//                }else{
//                    $datas = [
//                        'id'=>$id,
//                        'type'=>2, //未关注
//                    ];
//                    return jsonResponse('1000','获取成功',$datas);
//                }
                $datas = [
                    'id' => $id,
                    'type' => 2, //未关注
                ];
                return jsonResponse('1000', '获取成功', $datas);
            } else {
                return jsonResponse('6001', '获取信息失败');
            }
        }
    }

    public function getempowerss()
    {
        $jscode = input('code'); //jscode
//        $user['nickName'] = $request->param('nickName'); //昵称
//        $user['headImage'] = $request->param('headImage'); //头像
//        $user['addr'] = $request->param('addr'); //市
//        $user['sex'] = $request->param('sex'); //性别
//        $user['regtime'] = time();
        $appid = "wx82d1c2e0da1134a2";
        $appsecret = "e96b33b76aeec3cbdaa4300f4d39fff7";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appsecret&js_code=$jscode&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $arr = json_decode($abs);
        if (isset($arr->errcode)) {
            return jsonResponse('6001', $arr->errmsg);
        }
        if (!$arr->unionid) {
            $sessionKey = $arr->session_key;
            $encryptedData = input('encryptedData');
            $iv = input('iv');
            $data = self::decryptData($appid, $sessionKey, $encryptedData, $iv);
        }

        exit;
        $user['openId'] = $arr->openid; //用户openid
        $info = Db::name('user')->where(['unionid' => $user['unionid']])->find();
        if ($info) {
            if ($info['user_id'] == 0) {
//                Return_json(800, '授权成功', $info['id']);
                return jsonResponse('1000', '授权成功', $info['id']);
            } else {
//                $info = Db::name('user')->where(['id' => $info['user_id']])->find();
//                Return_json(200, '登陆成功', $info['token']);
                return jsonResponse('1000', '登陆成功', $info['id']);
            }
        } else {
//            $res = Db::name('user_wx')->insertGetId($user);
//            Return_json(800, '授权成功', $res);
            return jsonResponse('1000', '登陆成功', $info['id']);
        }
    }


    public function getXcxUserInfo($code, $appid, $appsecret)
    {
        if (!$code) return array();
        // 小程序专用信息

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$appsecret}&js_code={$code}&grant_type=authorization_code";
        $res = $this->http_request($url);
        return $res;
    }

    public function http_request($url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 以文件流形式返回
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!empty($data)) {
            // POST请求
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $output = curl_exec($ch);
        curl_close($ch);

        // 返回数组
        return json_decode($output, true);
    }


    function decryptDatas($appid, $sessionKey, $encryptedData, $iv)
    {
        $OK = 0;
        $IllegalAesKey = -41001;
        $IllegalIv = -41002;
        $IllegalBuffer = -41003;
        $DecodeBase64Error = -41004;

        if (strlen($sessionKey) != 24) {
            return $IllegalAesKey;
        }
        // $str = base64_decode(str_replace(" ","+",$_GET['str']));
        $aesKey = base64_decode(str_replace(" ", "+", $sessionKey));
        // var_dump($aesKey);exit;
        if (strlen($iv) != 24) {
            return $IllegalIv;
        }
        $aesIV = base64_decode(str_replace(" ", "+", $iv));
        $aesCipher = base64_decode(str_replace(" ", "+", $encryptedData));
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        // var_dump($dataObj);exit;
        if ($dataObj == NULL) {
            return $IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $appid) {
            return $DecodeBase64Error;
        }
        $data = json_decode($result, true);

        return $data;
    }

    //session_3rd();
    function session_3rd($length = 16)
    {
        //生成第三方3rd_session
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;

    }


    function define_str_replace($data)
    {
        return str_replace(' ', '+', $data);
    }


    public function getempowera()
    {
        $post = input();
        if (!empty($post)) {
            $appid = 'wx53fb433aed08106f';
            $secret = '83a087e62c128c500dc74480328c40d8';
            if(isset($post['code']))                $code        = $post['code'];
            if(isset($post['iv']))                  $iv          = $post['iv'];
            if(isset($post['rawData']))             $rawData     = $post['rawData'];
            if(isset($post['signature']))           $signature   = $post['signature'];
            if(isset($post['encryteData']))       $encryptedData = $post['encryteData'];
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $secret . "&js_code=" . $code . "&grant_type=authorization_code";
            $weixin = file_get_contents($url);
            $jsondecode = json_decode($weixin);
            $res = get_object_vars($jsondecode);
            $sessionKey = $res['session_key'];//取出json里对应的值
            // 验证签名
            $signature2 = sha1(htmlspecialchars_decode($rawData) . $sessionKey);
            if ($signature2 !== $signature) return json("signNotMatch");
            $data = [];
            $errCode = $this->decryptData($encryptedData, $iv, $sessionKey, $data);

            if ($errCode == 0) {
                return $data;
            } else {
                return json('获取失败');
            }
        }
    }

    public function decryptDatassss( $encryptedData, $iv,$sessionKey, &$data )
    {
        if (strlen($sessionKey) != 24) {
            return json('sessionKey错误');
        }
        $aesKey=base64_decode($sessionKey);


        if (strlen($iv) != 24) {
            return json('iv错误');
        }
        $aesIV=base64_decode($iv);
        $aesCipher=base64_decode($encryptedData);
        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return json('IllegalBuffer错误');
        }
        if( $dataObj->watermark->appid != $this->wxappid )
        {
            return json('IllegalBuffer错误');
        }
        $data = $result;
        file_put_contents('pay7.log', print_r($data,true).PHP_EOL);
        return  $data;
    }

    public function decryptData($appid,$secret,$encryptedData,$iv,$code){
//        $code = input('get.code');
//        $appid = 'wx8c9d056ead85efd7';
//        $secret = '849fbc7dff5c949c2a9707d9a20df7a8';
//        $encryptedData = input('encryptedData');
//        $iv = input('iv');

        if($code != ''){
            $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
            $html = file_get_contents($url);
            $obj = json_decode($html);

            if(isset($obj->errcode)){
                // 获取用户信息失败
                return $html;
            }else{

                $arrlist['openid'] = $obj->openid;
                $arrlist['session_key'] = $obj->session_key;
                /**
                 * 解密用户敏感数据
                 *
                 * @param encryptedData 明文,加密数据
                 * @param iv            加密算法的初始向量
                 * @param code          用户允许登录后，回调内容会带上 code（有效期五分钟），开发者需要将 code 发送到开发者服务器后台，使用code 换取 session_key api，将 code 换成 openid 和 session_key
                 * @return
                 */

//                include_once "wxBizDataCrypt.php";
                vendor('wxgrant.wxBizDataCrypt');

                $pc = new \WXBizDataCrypt( $appid, $arrlist['session_key']);

                $errCode = $pc->decryptData($encryptedData, $iv, $data='' );
                $data  = json_decode($data);//$data 包含用户所有基本信息
                file_put_contents('wxsq10.log', print_r($data,true).PHP_EOL);
                dump($data);exit;
                $arrlist['time'] = time();
                $arrlist['city'] = $data->city;//城市-市
                $arrlist['country'] = $data->country;//国家
                $arrlist['gender'] = $data->gender;//性别
                $arrlist['language'] = $data->language;//语言
                $arrlist['nickName'] = $data->nickName;//昵称
                $arrlist['avatarUrl'] = $data->avatarUrl;//头像
                $arrlist['province'] = $data->province;//城市-省份
                //判断获取信息是否成功
                if ($errCode != 0) {
                    return $errCode;
                }


                return $html;
            }
        }else{
            return json_encode('code为空');
        }
    }




}