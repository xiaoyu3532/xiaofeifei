<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/25 0025
 * Time: 14:25
 */

namespace app\pc\controller\v1;
use app\common\controller\Del;
use app\common\model\Crud;
use app\lib\exception\BannerMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\TypeMissException;

class RecomImg extends BaseController
{
    /**
     * 获取推荐图列表
     */
    public static function getpcRecomImg($page = '1')
    {
        $table = request()->controller();
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData($table, $type = 2, $where, $field = '*', $order = '', $page);
        if (!$info) {
            throw new BannerMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['img'] = handle_img_take($v['img']);
            }
            $num = Crud::getCount($table, $where);
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }


    /**
     * 新增推荐图
     */
    public function addpcRecomImg()
    {
        $data = input();
        $table = request()->controller();
        if (!empty($data['img']) && isset($data['img'])) {
            $data['img'] = handle_img_deposit($data['img']);
        }
        $info = Crud::setAdd($table, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

    /**
     * 修改推荐图
     */
    public function setpcRecomImg()
    {
        $data = input();
        $table = request()->controller();
        if (!empty($data['img']) && isset($data['img'])) {
            $data['img'] = handle_img_deposit($data['img']);
        }
        $where = [
            'id'=>$data['recom_id']
        ];
        unset($data['recom_id']);
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }
    /**
     * 启用、禁用轮播图
     * @param $id
     */
    public static function setBannerType($banner_id, $type)
    {
        $table = request()->controller();
        $where = [
            'id' => $banner_id
        ];
        $upData = [
            'type' => $type
        ];
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new TypeMissException();
        }
        return jsonResponseSuccess($info);
    }

    /**
     *
     * @param $id
     * @throws DelMissException
     * @throws \Exception
     * @throws \app\validate\ParameterException
     */
    public static function delpcRecomImg($id)
    {
        $table = request()->controller();
        $info = Del::setDel($table, $id);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }
}