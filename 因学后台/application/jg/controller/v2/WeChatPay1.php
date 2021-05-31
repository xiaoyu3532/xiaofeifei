<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/11 0011
 * Time: 14:00
 */

namespace app\jg\controller\v2;


use app\lib\exception\NothingMissException;
use think\Collection;
use app\common\model\Crud;

class WeChatPay extends Collection
{
    //微信H5支付
    public static function wechatpay_wap($order_num, $openId)
    {
//        $Wxparameter = config('wechat');
        $notify_url = 'https://zht.insooner.com/xcx/v1/wxpayNotify';
        $orderInfo = '';
        vendor('wxpayjsapi.lib.WxPayJsApiPay');
        //①、获取用户openid
        $tools = new \JsApiPay();
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetAppid('wxf47f6fd95e94366e');
        $input->SetMch_id('1493177642');
        //订单名字
        $input->SetBody("因学教育机构充值");
        $input->SetAttach("因学教育机构充值");
        //订单号
        $order_sn = 2315498584;
        $input->SetOut_trade_no($order_sn);
        //价格
        $order_num_data['price'] = 0.01;
        $input->SetTotal_fee($order_num_data['price'] * 100);
        $input->SetGoods_tag("");
        //通知地址
        $input->SetNotify_url($notify_url);
        //订单类型
        $input->SetTrade_type("JSAPI");
        //客户openid
        $input->SetOpenid($openId);
        file_put_contents('aa1.log', print_r($input, true) . PHP_EOL);
        $order = \WxPayApi::unifiedOrder($input);
        if ($order['return_code'] == "FAIL") {
            return jsonResponse('30001', $order['return_msg']);
        }
        if ($order["result_code"] == "FAIL") {
            return jsonResponse('30002', $order['err_code_des']);
        }
        //得到需付款的数据
        $jsApiParameters = $tools->GetJsApiParameters($order);

        $jsApiParameters['code'] = 1;
        return json($jsApiParameters);
    }

    public static function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function postXmlCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            echo "curl出错，错误码:$error" . "<br>";
        }
    }

    //获取用户IP
    public static function get_client_ip()
    {
        $ip = 'unknown';
        if ($_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        }
        return $ip;
    }

    //微信回调
    public static function wechatpay_wap_callback()
    {
        $data = input();
        file_put_contents('wxa1.log', print_r($data, true) . PHP_EOL);
        if (!array_key_exists('out_trade_no', $_POST)
            || !array_key_exists('trade_no', $_POST)
            || !array_key_exists('auth_app_id', $_POST)
            || !array_key_exists('seller_id', $_POST)
            || !array_key_exists('total_amount', $_POST)
            || !array_key_exists('sign', $_POST)
            || !array_key_exists('sign_type', $_POST)
            || !array_key_exists('trade_status', $_POST)) {
            exit;
        }
        $order_num = $_REQUEST['out_trade_no'];
        $receipt_amount = $_REQUEST['receipt_amount'];
        $alinum = $_REQUEST['trade_no'];
        $aliapp_id = $_REQUEST['auth_app_id'];
        $seller_id = $_REQUEST['seller_id'];
        $total_amount = $_REQUEST['total_amount'];
        $sign = $_REQUEST['sign'];
        $signType = $_REQUEST['sign_type'];
        $trade_status = $_REQUEST['trade_status'];
        if (!in_array($trade_status, array('TRADE_SUCCESS', 'TRADE_FINISHED'))) {
            exit;
        }
        //yx_zht_order 获取订单信息
        $order_info = Crud::getData('zht_order', 1, ['order_num' => $order_num, 'is_del' => 1, 'status' => 1], 'price,student_member_id');
        file_put_contents('a1.log', print_r($order_info, true) . PHP_EOL);
        if (!$order_info) {
            //写入日志
        }
        if ($receipt_amount != $order_info['price']) {
            //写入日志
        }
        Db::startTrans();
        //修改小订单
        $update_order = Crud::setUpdate('zht_order', ['order_num' => $order_num], ['status' => 2]);
        file_put_contents('a2.log', print_r($update_order, true) . PHP_EOL);
        if (!$update_order) {
            //写入日志
            Db::rollback();
        }
        //修改大订单
        $update_order_num = Crud::setUpdate('zht_order_num', ['order_num' => $order_num], ['status' => 2]);
        file_put_contents('a3.log', print_r($update_order_num, true) . PHP_EOL);
        if (!$update_order_num) {
            //写入日志
            Db::rollback();
        }
        $lmport_student_member = Crud::getData('lmport_student_member', 1, ['id' => $order_info['student_member_id']], 'student_status');
        file_put_contents('a4.log', print_r($lmport_student_member, true) . PHP_EOL);
        if ($lmport_student_member && $lmport_student_member['student_status'] <> 3) {
            //更改学生类型 yx_lmport_student_member 改为在读学生
            $lmport_student_member_update = Crud::setUpdate('lmport_student_member', ['id' => $order_info['student_member_id']], ['student_status' => 3]);
            file_put_contents('a5.log', print_r($lmport_student_member_update, true) . PHP_EOL);
            if (!$lmport_student_member_update) {
                //写入日志
                Db::rollback();
            }
        }
        Db::commit();
    }


}