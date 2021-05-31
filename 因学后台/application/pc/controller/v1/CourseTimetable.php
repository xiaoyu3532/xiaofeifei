<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 10:09
 */

namespace app\pc\controller\v1;

use app\common\controller\IsTime;
use app\lib\exception\NothingMissException;

class CourseTimetable extends BaseController
{
    //区间时间验证选择
    //classroom_type 1
    public static function isTimereturn()
    {
        $data = input();
        //教室
        if (!empty($data['valjs'][1])) {
            $data['classroom_id'] = $data['valjs'][1];
        }
        unset($data['valjs']);
        //老师
        if (!empty($data['valls'][1])) {
            $data['teacher_id'] = $data['valls'][1];
        }
        unset($data['valls']);
        $data['start_time'] = $data['start_time'] / 1000;
        $data['end_time'] = $data['end_time'] / 1000;
        $res = IsTime::isTimeReturn($data);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new NothingMissException();
        }
    }
}