<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/27 0027
 * Time: 15:26
 */

namespace app\nxz\controller\v1;


use app\lib\exception\CatCouresMissException;
use app\lib\exception\ISCourseMissException;
use app\common\model\Crud;
use app\validate\UserIDMustBePostiveInt;
use think\Db;

class CatCourse extends Base
{
    /**
     * 添加购物车
     */
    public static function setCatCourse()
    {
        $data = input();
        //判断课程是否正确
        $isCourse = self::isCourse($data);
        if (!$isCourse) {
            throw new ISCourseMissException();
        }
        $payExperienceCourse = self::payExperienceCourse($data);
        if ($payExperienceCourse != 1000) {
            return jsonResponse('3000', '你已购买此课程', 2013);
        }
        //获取用户购物车是否有此课程
        $isCat = self::isCatData($data);
        $table = request()->controller();
        if ($isCat == 1) {
            //用户购物车无此课做添加购物车
            $info = Crud::setAdd($table, $data);
        } elseif ($isCat == 2) {
            return jsonResponse('1001', '你已加入购物');
        }
        if (!$info) {
            throw new CatCouresMissException();
        } else {
            return jsonResponse('1000', '加入购物成功', $info);
        }
    }

    /**
     * 验证课程正确性
     * @param $data
     */
    public static function isCourse($data)
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $data['cou_id']
        ];
        $info = Db::name('contrarian_course')->where($where)->field('id')->find();
        return $info;

    }


    /**
     * 查看购物车是否有此课程
     */
    public static function isCatData($data)
    {
        $where = [
            'is_del' => 1,
            'user_id' => $data['user_id'],
            'cou_id' => $data['cou_id'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id');
        if ($info) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * 购物车展示
     */
    public static function getCatCourse()
    {
        $data = input();
        (new UserIDMustBePostiveInt())->goCheck();
        $where = [
            'ca.user_id' => $data['user_id'],
            'ca.is_del' => 1,
            'ca.status' => 6, //1普通课程，2体验课程，3社区活动课程，4秒杀课程，5综合体课，6逆行者课程
        ];
        $table = request()->controller();
        $join = [
            ['yx_contrarian_course co', 'ca.cou_id = co.id', 'left'],
            ['yx_contrarian_classification cc', 'co.classification_id = cc.id', 'left'],
        ];
        $alias = 'ca';
        $field = ['ca.id,co.img,co.name,co.title,cc.name ccname'];
        $order = 'ca.id desc';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, 'm.uid');
        if (!$info) {
            throw new CatCouresMissException();
        } else {
            return jsonResponse('1000', '成功获取购物车', $info);
        }
    }

    /**
     * 删除购物车
     */
    public static function delCatCourse($cat_id)
    {
        $where = [
            'id' => ['in',$cat_id]
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new ActivityMissException();
        } else {
            return jsonResponse('1000', '删除成功', $info);
        }
    }

    /**
     * 验证用户是否购买过此课程
     * @param $data
     * @return int|string
     * @throws \Exception
     */
    public static function payExperienceCourse($data)
    {
        $where = [
            'is_del' => 1,
            'status' => 8,//1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            'uid' => $data['user_id'],
            'cid' => $data['cou_id'],
            'cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
        ];
        $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
        if ($ExperienceCourse) {
            return jsonResponse('3000', '你已购买此课程');
        } else {
            return 1000;
        }
    }


}