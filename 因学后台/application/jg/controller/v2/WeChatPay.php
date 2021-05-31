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
use think\Db;

class WeChatPay extends Collection
{
    //微信jsapi支付
    public static function wechatpay_wap($order_num, $openId)
    {
        //订单验证
        $order_info = Crud::getData('zht_order', 1, ['order_num' => $order_num, 'is_del' => 1, 'status' => 1], '*'); //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
        if (!$order_info) {
            return jsonResponse('3000', '数据有误，请重试');
        }
        $course_num = Crud::getData('zht_course_num', 1, ['id' => $order_info['course_num_id']], 'enroll_num,surplus_num');
        $stock = $course_num['surplus_num'] - $course_num['enroll_num'];
        if ($stock <= 0) {
            return jsonResponse('3000', '库存不足');
        }

        vendor('wxpayjsapi.lib.WxPayJsApiPay');
        //①、获取用户openid
        $tools = new \JsApiPay();

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        //订单名字
        $input->SetBody($order_info['course_name']);
        $input->SetAttach("因学教育");
        //订单ID
        $input->SetOut_trade_no($order_info['order_num']);
        //价格
        $input->SetTotal_fee($order_info['price'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 1800));
        $input->SetGoods_tag("");
        //通知地址
        $input->SetNotify_url("https://zht.insooner.com/jg/v2/wechat_notify");
        //订单类型
        $input->SetTrade_type("JSAPI");
        //客户openid
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);

        //得到需付款的数据
        $jsApiParameters = $tools->GetJsApiParameters($order);
        return $jsApiParameters;
    }

    //支付回调
    public static function wechat_notify() //out_trade_no
    {

        vendor('wxpayjsapi.lib.WxPayJsApiPay');
        $result = file_get_contents('php://input', 'r');
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA);
        $result = json_encode($result);
        $result = json_decode($result, true);
        if ($result['result_code'] === 'SUCCESS' && $result['mch_id'] === \WxPayConfig::MCHID && $result['appid'] === \WxPayConfig::APPID) {
            ksort($result);
            //拼接生成签名的字符串
            $sign_string = '';
            foreach ($result as $key => $value) {
                if ($key !== 'sign') {
                    $sign_string .= $key . '=' . $value . '&';
                }
            }
            $sign_string .= 'key=' . \WxPayConfig::KEY;
            $sign = strtoupper(md5($sign_string));
            if ($sign === $result['sign']) {
            }
            $order_num = $result['out_trade_no'];
            $total_fee = $result['total_fee'];

            //yx_zht_order 获取订单信息 file_put_contents('wx1.log', print_r($order_info, true) . PHP_EOL);
            $order_info = Crud::getData('zht_order', 1, ['order_num' => $order_num, 'is_del' => 1, 'status' => 1], 'price,student_member_id,course_id,course_num_id');
            if ($order_info) {
                $price = $order_info['price'] * 100;
                if ($total_fee != $price) {
                    //写入日志
                }
                Db::startTrans();
                //修改小订单
                $update_order = Crud::setUpdate('zht_order', ['order_num' => $order_num], ['status' => 2]);
                if (!$update_order) {
                    //写入日志
                    Db::rollback();
                }
                //修改大订单
                $update_order_num = Crud::setUpdate('zht_order_num', ['order_num' => $order_num], ['status' => 2]);
                if (!$update_order_num) {
                    //写入日志
                    Db::rollback();
                }
                $lmport_student_member = Crud::getData('lmport_student_member', 1, ['id' => $order_info['student_member_id']], 'student_status');
                if ($lmport_student_member && $lmport_student_member['student_status'] <> 3) {
                    //更改学生类型 yx_lmport_student_member 改为在读学生
                    $lmport_student_member_update = Crud::setUpdate('lmport_student_member', ['id' => $order_info['student_member_id']], ['student_status' => 3]);
                    if (!$lmport_student_member_update) {
                        //写入日志
                        Db::rollback();
                    }
                }
                //添加报名人数
                $course_inc = Crud::setIncs('zht_course', ['id' => $order_info['course_id']], 'enroll_num');
                if (!$course_inc) {
                    Db::rollback();
                }

                //加课包销量
                $course_num = Crud::setIncs('zht_course_num', ['id' => $order_info['course_num_id']], 'enroll_num');
                if (!$course_num) {
                    Db::rollback();
                }
                Db::commit();
            } else {
                //写入日志
            }
            $return = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>"; //返回成功给微信端 一定要带上不然微信会一直回调8次
            ob_clean();
            echo $return;
            exit;
        }
    }


}