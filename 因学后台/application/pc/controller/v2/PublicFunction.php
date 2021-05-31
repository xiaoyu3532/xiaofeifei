<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/18 0018
 * Time: 11:04
 */

namespace app\pc\controller\v2;


use app\pc\controller\v1\BaseController;

class PublicFunction extends BaseController
{

    //控制器公共接口
    public function pubZhtMarket()
    {
        $data = input();
        //前端数据解密
//        $data = unserialize(base64_decode($data));

        switch ($data['function']) {
            case 'getZhtMarket':
                return self::getZhtMarket($data);  //获取大活动
                break;
            case 'getZhtMarketField':
                return self::getZhtMarketField($data);  //获取大活动字段
                break;
            case 'addZhtMarket':
                return self::addZhtMarket($data); //添加大活动（目前中是添加小候鸟）
                break;
            case 'deitZhtMarket':
                return self::editZhtMarket($data); //修改大活动
                break;
            case 'delZhtMarket':
                return self::delZhtMarket($data); //删除大活动 传值加一个表名
                break;
            case 'getZhtMarketList':
                return self::getZhtMarketList($data); //删除大活动 传值加一个表名
                break;
            default:
//                表达式的值不等于 label1 及 label2 时执行的代码;
        }
    }


    //$a = base64_encode(serialize($info_data)); 加密
    //ase解密
    public static function aesDecrypt($data)
    {

        $return_data = openssl_decrypt($data, 'aes-128-cbc', '1234123412ABCDEF', 0, 'ABCDEF1234123412');
        if (!$return_data) {
            return false;
        }
        return $return_data;
    }

    //ase加密
    public static function aesEncryption($data)
    {
        $return_data = openssl_encrypt($data, 'aes-128-cbc', '1234123412ABCDEF', 0, 'ABCDEF1234123412');
        if (!$return_data) {
            return false;
        }
        return $return_data;
    }

}