<?php

namespace app\xcx\controller\v3;

use app\lib\exception\NothingMissException;
use EasyWeChat\Factory;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use app\xcx\controller\v2\Base;


/**
 * 点读
 */
class ClickRead extends Base
{
    protected $exceptTicket = [""];

    public function getVip()
    {
        // $where['is_del'] = ['=', 1];
        // $where['user_id'] = ['=', $this->userId];
        // $where['expiration_time'] = ['>=', time()];
        //
        // $vip = Crud::getDataunpage('zht_vip', 2, $where, 'vip_type,expiration_time', 'vip_type ASC');
        // if (count($vip) > 0) {
        //     foreach ($vip as &$item) {
        //         $item['expiration_time'] = round(($item['expiration_time'] - time()) / 86400);
        //     }
        // }

        return returnResponse('1000', '', []);
    }

    public function getVipRecord()
    {
        return returnResponse("1000", '');
        if (!$type = input('post.type/d')) {
            return returnResponse("1001", '请选择类型');
        }
        $result = Crud::getDataunpage("zht_vip_record", 2, ['vip_type' => $type, 'is_del' => 1], 'id,vip_type,name,original_price,present_price,days');
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $item['original_price'] = ceil($item['original_price']) == $item['original_price'] ? intval($item['original_price']) : $item['original_price'];
                $item['present_price'] = ceil($item['present_price']) == $item['present_price'] ? intval($item['present_price']) : $item['present_price'];

            }
        }
        return returnResponse("1000", '', $result);
    }

    public function isPermission()
    {
        return returnResponse("1000", '', ['status' => 1]);
        if (!$type = input('post.type/d')) {
            return returnResponse("1001", '请选择类型');
        }
        $where['is_del'] = ['=', 1];
        $where['user_id'] = ['=', $this->userId];
        $where['expiration_time'] = ['>=', time()];
        $where['vip_type'] = ['=', $type];

        $vip = Crud::getData('zht_vip', 1, $where, 'id');
        if (empty($vip)) {
            return returnResponse("1000", '', ['status' => 2]);
        }
        return returnResponse("1000", '', ['status' => 1]);
    }


    public function createVipOrder()
    {
        if (!$id = input('post.id/d')) {
            return returnResponse("1000", '请选择购买vip类型');
        }
        $result = Crud::getDataunpage("zht_vip_record", 1, ['id' => $id, 'is_del' => 1], 'present_price,days,vip_type');
        if (empty($result)) {
            return returnResponse("1000", '选择错误');
        }
        Db::startTrans();
        try {
            $orderNo = time() . rand(999, 9999);

            $createOrder = Crud::setAdd("zht_vip_order", ['user_id' => $this->userId, 'order_no' => $orderNo, 'record_id' => $id, 'days' => $result['days'], 'money' => $result['present_price'], 'vip_type' => $result['vip_type']]);
            if (empty($createOrder)) {
                throw new Exception('创建订单失败');
            }
            $config = config('wxpayConfig');
            $app = Factory::payment($config);
            $openid = $this->userInfo['x_openid'];
            $result = $app->order->unify([
                'body' => '购买vip',
                'out_trade_no' => $orderNo,
                'total_fee' => $result['present_price'] * 100,
                'notify_url' => "https://zht.insooner.com/xcx/v3/vipBack",
                'trade_type' => 'JSAPI',
                'openid' => $openid
            ]);

            if ($result['return_code'] !== 'SUCCESS') {
                throw new Exception('获取支付参数失败');
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse("1002", $e->getMessage());
        }

        //获取支付配置信息
        $jssdk = $app->jssdk;
        $wxPayParam = $jssdk->sdkConfig($result['prepay_id'], false);
        return returnResponse("1000", '', $wxPayParam);
    }

    public function book()
    {
        $data = $datas = [
            'appId' => 'fz5f041466dd8b2f48',
            'user' => $this->userId,
            'timestamp' => time(),

        ];
        ksort($data);
        $data = http_build_query($data);
        $data = $data . "&key=2cc457c2607f0dbdb372e778c61715d0";
        $sign = strtoupper(sha1($data));
        $result = \pinmeng\Http::request('GET', "https://open.kingsunedu.com/api/book/getBookVerList", [
            'query' => [
                "appId" => $datas['appId'],
                "user" => $datas['user'],
                "timestamp" => $datas['timestamp'],
                "sign" => $sign,
            ],
            'headers' => [
                'Accept-Encoding' => 'gzip'
            ]
        ], 'json');
        if ($result['retcode'] != 0) {
            return returnResponse("1001", $result['msg']);
        }
        return returnResponse("1000", '', $result['data']['verList']);
    }

    public function generateSign()
    {
        $data = $datas = [
            'appId' => 'fz5f041466dd8b2f48',
            'appUserId' => $this->userId,
            'timestamp' => time(),
        ];
        ksort($data);
        $data = http_build_query($data);
        $data = $data . "&key=2cc457c2607f0dbdb372e778c61715d0";
        $sign = strtoupper(sha1($data));
        $datas['sign'] = $sign;
        return returnResponse('1000', '', $datas);
    }
}