<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26 0026
 * Time: 17:21
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;
use app\lib\exception\CommunityMissException;

class CommunityName
{
    /**
     * 获取社区列表
     * @throws CommunityMissException
     * @throws \Exception
     */
    public function getCommunity(){
        $data = input();
        $where = [
            'type'=>1,
            'is_del'=>1,
        ];
        (isset($data['province_id']) && !empty($data['province_id'])) && $where['province_id'] = $data['province_id'];//是否有地区ID传入
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name');
        if(!$info){
            throw new CommunityMissException();
        }else{
            return jsonResponseSuccess($info);
        }
    }

}