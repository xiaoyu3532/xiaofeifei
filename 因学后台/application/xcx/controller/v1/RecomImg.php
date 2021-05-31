<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/25 0025
 * Time: 14:37
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\BannerMissException;

class RecomImg
{
    /**
     * 获取推荐图
     */
    public static function getRecomImg()
    {
        $where = [
            'is_del' => 1,
            'type' => 1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*', $order = 'create_time desc');
        if (!$info) {
            throw new BannerMissException();
        } else {
            $info['img'] = get_take_img($info['img']);
            return jsonResponse('1000', '成功获取活动图', $info);
        }
    }
}