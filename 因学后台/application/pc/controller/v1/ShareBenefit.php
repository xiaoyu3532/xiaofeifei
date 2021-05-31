<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 17:51
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use app\common\controller\BaseController;

class ShareBenefit extends BaseController
{
    //展示分润价格
    public static function getpcShareBenefit()
    {
        //求分润表内容
        $share_benefit = Crud::getData('share_benefit', 2, ['is_del' => 1], '*');
        if (!$share_benefit) {
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($share_benefit);
        }
    }

    //设置分润价格
    public static function setpcShareBenefit()
    {
        //求分润表内容
        $data = input();
        $where = [
            'type'=>$data['type']
        ];
        $updata = [
            'Proportion'=>$data['Proportion']
        ];
        $share_benefit = Crud::setUpdate('share_benefit',$where,$updata );
        if (!$share_benefit) {
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($share_benefit);
        }
    }


    //获取名额价格
    public static function getUserPrice(){
        //求分润表内容
        $user_price = Crud::getData('user_price', 1, ['is_del' => 1], 'price');
        if (!$user_price) {
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($user_price);
        }
    }

    //设置名额价格
    public static function setUserPrice($price){
        //求分润表内容
        $user_price = Crud::setUpdate('user_price','1=1',['price'=>$price] );;
        if (!$user_price) {
            throw new NothingMissException();
        }else{
            return jsonResponseSuccess($user_price);
        }
    }




}