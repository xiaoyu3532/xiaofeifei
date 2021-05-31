<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/18 0018
 * Time: 10:51
 */

namespace app\jg\controller\v2;


use app\lib\exception\NothingMissException;
use think\Collection;
use app\common\model\Crud;
use think\Db;

class AliPay extends Collection
{
    /**
     * 支付宝支付参数
     */
    //生成唯一订单号
    function build_order_no()
    {
        return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    //支付宝wap支付
    public static function alipay_wap($order_num)
    {
        //订单验证
        $order_info = Crud::getData('zht_order', 1, ['order_num' => $order_num, 'is_del' => 1, 'status' => 1], '*'); //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
        if (!$order_info) {
            return jsonResponse('3000', '数据有误，请重试');
        }
        $course_num = Crud::getData('zht_course_num',1, ['id' => $order_info['course_num_id']], 'enroll_num,surplus_num');
        $stock = $course_num['surplus_num']-$course_num['enroll_num'];
        if($stock<=0){
            return jsonResponse('3000','库存不足');
        }

        vendor('alipay-phone-h5.wappay.service.AlipayTradeService');
        vendor('alipay-phone-h5.wappay.buildermodel.AlipayTradeWapPayContentBuilder');
        vendor('alipay-phone-h5.config');
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order_info['order_num'];
        //订单名称，必填
        $subject = $order_info['course_name'];
        //付款金额，必填
//        $total_amount = $order_info['price'];
        $total_amount = 0.01;
        //商品描述，可空
        $body = $order_info['course_name'];
        $AlipayH5 = config('AlipayH5');
        //超时时间
        $timeout_express = "1m";

        $payRequestBuilder = new\ AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $payResponse = new\ AlipayTradeService($AlipayH5);
        $result = $payResponse->wapPay($payRequestBuilder, $AlipayH5['return_url'] . $order_info['order_num'], $AlipayH5['notify_url']);
        return jsonResponse('1000', '成功', $result);
//        return $result;
    }

    //支付宝回调
    public static function alipay_wap_callback()
    {
//        file_put_contents('a2.log', print_r($data,true).PHP_EOL);
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
        //yx_zht_order 获取订单信息  file_put_contents('a1.log', print_r($order_info, true) . PHP_EOL);
        $order_info = Crud::getData('zht_order', 1, ['order_num' => $order_num, 'is_del' => 1, 'status' => 1], 'price,student_member_id,course_id,course_num_id');
        if ($order_info) {
            if ($receipt_amount != $order_info['price']) {
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


    }

}