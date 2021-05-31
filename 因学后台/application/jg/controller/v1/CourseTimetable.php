<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/14 0014
 * Time: 11:33
 */

namespace app\jg\controller\v1;

use app\common\controller\IsTime;
use app\lib\exception\NothingMissException;

class CourseTimetable extends BaseController
{
    //区间时间验证选择
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