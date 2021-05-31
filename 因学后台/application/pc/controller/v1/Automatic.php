<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 15:05
 */

namespace app\pc\controller\v1;

//服务器定时器
class Automatic
{
    public static function Testing_token(){
        $overdue_time = time()+43200;
        $where = [
            'is_del'=>1,
            'update_time'=>['>',$overdue_time]
        ];
    }

}