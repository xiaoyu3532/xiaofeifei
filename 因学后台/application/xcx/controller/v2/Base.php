<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use app\lib\exception\ReturnMissException;
use think\Controller;
use app\common\model\Crud;
use think\Log;

/**
 * 接口基类
 */
class Base extends Controller
{
    protected $exceptTicket = []; // 不需要传递凭证、不需要登录也可以访问
    protected $allowTourist = []; // 允许临时凭证, 游客身份也可以访问
    protected $userId = null; // 用户ID
    protected $parentUserId = null; // 根级用户ID
    protected $userInfo = [];
    protected $sessionId = null;
    protected $sessionStorage = [];
    protected $science = null;
    protected $time = null;


    const CHANNEL_UNKNOW = 0; // 未知
    const CHANNEL_WECHAT_UNIONID = 1; // 微信unionid
    const CHANNEL_WECHAT_MP_OPENID = 2; // 微信服务号openid
    const CHANNEL_WECHAT_MINI_OPENID = 3; // 微信小程序openid
    const CHANNEL_TELEPHONE = 4; // 手机号码
    const CHANNEL_TEMPORARY = 5; // 临时凭证(浏览器/APP)
    const CHANNEL_QQ_OPENID = 6; // QQ渠道openid
    const CHANNEL_ACCOUNT = 7; // 账号
    const CHANNEL_ALIPAY_OPENID = 8; // 支付宝OPENID
    const CHANNEL_WECHAT_OP_OPENID = 9; // 微信开放平台OPENID
    const CHANNEL_SHANGCHENG = 10; //10 SHANGCHENG

    const SMSTYPE_UNKNOW = 0; // 未知
    const SMSTYPE_RISK = 1; // risk 手机号风险校验
    const SMSTYPE_MODIFY_TELEPHONE = 2; // modify_telephone 修改手机号
    const SMSTYPE_LOGIN = 3;//login 登录


    /**
     * 初始化
     * @author
     */
    protected function _initialize()
    {

        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');


        //halt(request());
        // 支持跨域请求
        // if ($origin = input('server.HTTP_ORIGIN')) {
        //     $uri = parse_url($origin);
        //     $origin = http_build_url([
        //         'scheme' => $uri['scheme'],
        //         'host' => $uri['host'],
        //         'port' => isset($uri['port']) ? $uri['port'] : null,
        //     ]);
        //     $origin = rtrim($origin, '/');
        //
        //     header('Access-Control-Allow-Origin: ' . $origin);
        //     header('Access-Control-Allow-Credentials: true');
        //
        //     if (request()->isOptions()) {
        //         header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        //         exit;
        //     }
        // }

        //如果是OPTIONS请求，不继续往下执行
        // if (request()->isOptions()) {
        //     exit;
        // }


        // // 如果调用的方法不存在, 直接拦截
        // if (!method_exists($this, request()->action())) {
        //     throw new NothingMissException([
        //         'code' => '404',
        //         'msg' => '方法不存在'
        //     ]);
        // }
        //var_dump(input('server.HTTP_X_TICKET'));
        // 解析登录凭证获取应用信息
        // $path = "log/" . date('Y-m-d');
        // if (!file_exists($path)) {
        //     mkdir(iconv("utf-8", "gbk", $path), 0777, true);
        // }
        // $datas = request()->post();
        // array_push($datas,request()->header());
        // file_put_contents($path . '/'.date('a') . '.txt', var_export($datas, true) . PHP_EOL, FILE_APPEND);
        $ticket = input('server.HTTP_X_TICKET');
        if (!empty($ticket)) {

            // if (strlen($ticket) !== 32) {
            //     throw new ReturnMissException();
            // }
            //
            // // 校验请求签名
            // $reqPostParams = input('post.');
            //
            // // 获取请求签名
            // if (empty($reqPostParams['sign'])) {
            //     throw new ReturnMissException();
            // }
            // // 校验签名
            //
            // ksort($reqPostParams);
            // $sign = $reqPostParams['sign'];
            // unset($reqPostParams['sign']);
            //
            // $signStr = urldecode(http_build_query($reqPostParams));
            //
            // if ($sign !== hash_hmac('md5', $signStr, $ticket)) {
            //     throw new ReturnMissException(['msg' => "校验失败"]);
            // }
            //if (!in_array(request()->action(true), $this->exceptTicket)) {
            // 读取session信息 校验过期时间

            $ticketInfo = Crud::getData('user_session', '1', ['access_token' => $ticket, 'is_del' => 1], 'expire_at,user_id');
            if (!in_array(request()->action(true), $this->exceptTicket)) {
                if (empty($ticketInfo) || $ticketInfo['expire_at'] < time()) {
                    throw new ReturnMissException(['msg' => '凭证已过期']);
                }

            }
            if (!empty($ticketInfo) && $ticketInfo['expire_at'] >= time()) {
                $this->userId = $ticketInfo['user_id'];
                $this->userInfo = Crud::getData('user', 1, ['id' => $ticketInfo['user_id'], 'is_del' => 1, 'type' => 1]);
                if (empty($this->userInfo)) {
                    throw new ReturnMissException(['msg' => '用户资料异常']);
                }
            }


            //}


            // 如果方法不允许游客身份登录
            // if (!in_array(request()->action(), $this->allowTourist)) {
            //
            //     $userChannel = $this->userInfo['channel'];
            //
            //     $allowChannel = [
            //         self::CHANNEL_TELEPHONE,
            //         // self::CHANNEL_ACCOUNT
            //         self::CHANNEL_WECHAT_MINI_OPENID,
            //     ];
            //     if (!in_array($userChannel, $allowChannel)) {
            //         return jsonResponse('10086', "失败");
            //     }
            // }

        } else if (!in_array(request()->action(true), $this->exceptTicket)) { // 缺少调用凭证
            throw new ReturnMissException(['msg' => '缺少调用凭证']);
        }

    }

    /**
     * 空方法
     */
    // public function _empty($name)
    // {
    //     exception('接口不存在', 404);
    // }

}
