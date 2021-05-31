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

    public function getempowera()
    {
        $code = input('code');
        $data['name'] = input('nickName'); //昵称
        $data['img'] = input('headImage'); //头像
        $data['province'] = input('province'); //省
        $data['city'] = input('city'); //市
        $data['sex'] = input('sex'); //性别
        $data['create_time'] = time();
//        file_put_contents('wxsq1.log', print_r($data,true).PHP_EOL);
        $appid = "wx53fb433aed08106f";
        $secret = "83a087e62c128c500dc74480328c40d8";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $obj = json_decode($abs);
        if (isset($obj->errcode)) {
            return jsonResponse('6001', $obj->errmsg);
        }
        $data['x_openid'] = $obj->openid;
        if ($data['x_openid'] == '') {
            return jsonResponse('6001', '获取信息失败');
        }
        $encryptedData = input('encryptedData');
        $iv = input('iv');
//        if($obj->unionid){
//            $data['unionid'] = $obj->unionid;
//        }else{
//            $datas=$this->decryptData($appid, $secret, $encryptedData, $iv);
//        }
        $datas = $this->decryptDatasa($appid, $secret, $encryptedData, $iv);

        file_put_contents('wxsq2.log', print_r($data, true) . PHP_EOL);
        $info = Db::name('user')->where(['unionid' => $data['unionid']])->find();
//        $Follow_controller = new Follow();
//        $Follow=$Follow_controller->getToken($data['openid']);
        if ($info) {
            if (empty($info['x_openid'])) {
                $update = [
                    'x_openid' => $data['x_openid']
                ];
                $aa = Db::name('user')->where(['unionid' => $data['unionid']])->update($update);
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

    //线上使用
    public function getempower()
    {
        $jscode = input('code'); //jscode
        $data['name'] = input('nickName'); //昵称
        $data['img'] = input('headImage'); //头像
        $data['province'] = input('province'); //省
        $data['city'] = input('city'); //市
        $data['sex'] = input('sex'); //性别
        $data['create_time'] = time();

        $appid = "wx53fb433aed08106f";
        $appsecret = "83a087e62c128c500dc74480328c40d8";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appsecret&js_code=$jscode&grant_type=authorization_code";
        $abs = file_get_contents($url);
        $arr = json_decode($abs);

        if (isset($arr->errcode)) {
            return jsonResponse('6001', $arr->errmsg);
        }
        $data['x_openid'] = $arr->openid;
        if ($data['x_openid'] == '') {
            return jsonResponse('6002', '获取信息失败');
        }

//        if (!$arr->unionid) {
        $sessionKey = $arr->session_key;
        $encryptedData = input('encryptedData');
        $iv = input('iv');
        $data_inof = $this->decryptData($appid, $sessionKey, $encryptedData, $iv);

        $unionId = $data_inof['unionId'];
        $data['x_openid'] = $data_inof['openId'];
//        } else {
//            $data['unionid'] = $arr->unionid;
//            $unionId = $arr->unionid;
//        }
        $info = Db::name('user')->where(['unionid' => $unionId])->find();
        if ($info) {
            if (empty($info['x_openid'])) {
                $update = [
                    'x_openid' => $data['x_openid']
                ];
                $aa = Db::name('user')->where(['unionid' => $unionId])->update($update);
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
            $data['unionid'] = $unionId;
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


    function decryptData($appid, $sessionKey, $encryptedData, $iv)
    {
//        dump($appid);
//        dump($sessionKey);
//        dump($encryptedData);
//        dump($iv);exit;
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


}