<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 11:52
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class ShareBenefit extends BaseController
{
    //分润计算(计算机构收入)
    public static function getjgShareBenefit($data)
    {
        //求分润表内容
        $share_benefit = Crud::getData('share_benefit', 2, ['is_del' => 1], '*');
        if (!$share_benefit) {
            throw new NothingMissException();
        }
        foreach ($share_benefit as $k => $v) {
            if ($v['type'] == 1) {
                $course = $v['Proportion'];
            } elseif ($v['type'] == 2) {
                $experience = $v['Proportion'];
            } elseif ($v['type'] == 3) {
                $activity = $v['Proportion'];
            } elseif ($v['type'] == 4) {
                $seckill = $v['Proportion'];
            } elseif ($v['type'] == 5) {
                $synthesize = $v['Proportion'];
            }

        }
        $price = 0;
        foreach ($data as $k => $v) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
            if ($v['cou_status'] == 1) {
                $price += ($v['price'] - $v['price'] * $course);
            } elseif ($v['cou_status'] == 2) {
                $price += ($v['price'] - $v['price'] * $experience);
            } elseif ($v['cou_status'] == 3) {
                $price += ($v['price'] - $v['price'] * $activity);
            } elseif ($v['cou_status'] == 4) {
                $price += ($v['price'] - $v['price'] * $seckill);
            } elseif ($v['cou_status'] == 5) {
                $price += ($v['price'] - $v['price'] * $synthesize);
            }
        }
        return $price;

    }

    //分润后计算平台收入机构钱
    public static function getShareBenefit($data)
    {
        //求分润表内容
        $share_benefit = Crud::getData('share_benefit', 2, ['is_del' => 1], '*');
        if (!$share_benefit) {
            throw new NothingMissException();
        }
        foreach ($share_benefit as $k => $v) {
            if ($v['type'] == 1) {
                $course = $v['Proportion'];
            } elseif ($v['type'] == 2) {
                $experience = $v['Proportion'];
            } elseif ($v['type'] == 3) {
                $activity = $v['Proportion'];
            } elseif ($v['type'] == 4) {
                $seckill = $v['Proportion'];
            } elseif ($v['type'] == 5) {
                $synthesize = $v['Proportion'];
            }

        }
        $price = 0;
        foreach ($data as $k => $v) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
            if ($v['cou_status'] == 1) {
                $price += $v['price'] * $course;
            } elseif ($v['cou_status'] == 2) {
                $price += $v['price'] * $experience;
            } elseif ($v['cou_status'] == 3) {
                $price += $v['price'] * $activity;
            } elseif ($v['cou_status'] == 4) {
                $price += $v['price'] * $seckill;
            } elseif ($v['cou_status'] == 5) {
                $price += $v['price'] * $synthesize;
            }
        }
        return $price;

    }

}