<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 14:17
 */

namespace app\pc\controller\v1;

use app\common\controller\Del;
use app\common\model\Crud;
use app\lib\exception\BannerMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\TypeMissException;

class Banner extends BaseController
{
    /**
     * 获取轮播图名称与图片缩略图
     */
    public static function getpcBanner($page = '1')
    {
        $table = request()->controller();
        $where = [
            'b.is_del' => 1,
            'b.type' => 1,
        ];
        $join = [
            ['yx_member m', 'b.member_id = m.uid', 'left'],
            ['yx_course c', 'b.course_id = c.id', 'left'],
            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
        ];
        $alias = 'b';
        $field = 'b.img,b.id,b.name,m.cname,cu.name couname,b.sort,b.bann_type,b.member_id,b.course_id,b.member_id,b.course_id,b.color';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'b.sort desc', $field, $page);
        if (!$info) {
            throw new BannerMissException();
        } else {
            foreach ($info as $k => $v) {
                $info[$k]['img'] = handle_img_take($v['img']);
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'b.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    /**
     * 获取轮播图详情
     */
    public static function getBannerdetails($banner_id)
    {
        $where = [
            'type' => 1,
            'is_del' => 1,
            'id' => $banner_id,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = '*');
        if ($info) {
            $info['img'] = handle_img_take($info['img']);
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    /**
     * 新增轮播图
     */
    public function addpcBannerImg()
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
     * 修改轮播图
     */
    public function setpcBannerImg()
    {
        $data = input();
        $table = request()->controller();
        if (!empty($data['img']) && isset($data['img'])) {
            $data['img'] = handle_img_deposit($data['img']);
        }
        $where = [
            'id'=>$data['banner_id']
        ];
        unset($data['banner_id']);
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
    public static function delpcBanner($id)
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