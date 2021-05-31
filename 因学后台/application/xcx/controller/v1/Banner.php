<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 10:16
 */

namespace app\xcx\controller\v1;

use app\lib\exception\BannerMissException;
use app\common\model\Crud;

class Banner
{
    /**
     * 获取Banner图
     */
    public static function getBanner()
    {
        $where = [
            'is_del' => 1,
            'type' => 1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = '*');
        if (!$info) {
            throw new BannerMissException();
        } else {
            foreach ($info as $k=>$v){
                $info[$k]['img'] = get_take_img($v['img']);
            }
            return jsonResponse('1000', '成功获取活动图', $info);
        }
    }


}