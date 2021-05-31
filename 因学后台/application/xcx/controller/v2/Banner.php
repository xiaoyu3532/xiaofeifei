<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;


/**
 * 轮播图
 */
class Banner extends Base
{
    protected $exceptTicket = ["getBanners"];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 获取banner
     * @Author: asus
     * @Date: 2020/5/21
     * @Time: 15:42
     * @Interface getBanners
     * @return string
     */
    public function getBanners()
    {
        //添加访问量
        Crud::setAdd("zht_record", ['record_type' => 2]);
        $result = Crud::getDataunpage('zht_banner', 2, ['is_del' => 1, 'is_disable' => 1], 'img,type,value', 'sort DESC');
        return returnResponse('1000', '', $result);
    }

}