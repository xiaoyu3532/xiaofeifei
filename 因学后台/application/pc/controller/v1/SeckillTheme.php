<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 10:26
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class SeckillTheme extends BaseController
{
    //修改秒杀时间区间信息
    public static function updatepcSeckillTheme()
    {
        $data = input();
        $table = request()->controller();
        $where = [
            'id' => 1
        ];
        $info = Crud::getData($table, $type = 1, $where);
        if ($info) {
            if ($info['determine_type'] == 1) {
                return jsonResponse('10006', '秒杀时间开启中，不可以修改');
            } else {
                $end_time = $data['end_time']/1000;
                $updata = [
                    'end_time'=>$end_time,
                    'name'=>$data['name'],
                ];
                $res = Crud::setUpdate($table, $where, $updata);
                if ($res) {
                    return jsonResponseSuccess($res);
                } else {
                    throw new NothingMissException();
                }
            }
        } else {
            throw new NothingMissException();
        }
    }

    //获取秒杀时间区间信息
    public static function getpcSeckillTheme()
    {
        $where = [
            'id' => 1
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where);
        $info['end_time'] = $info['end_time'] * 1000;
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

    //确认开始
    public static function setpcdetermineType()
    {
        $where = [
            'id' => 1
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, ['determine_type' => 1]);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

}