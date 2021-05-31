<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 13:49
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\SeckillCourseMissException;
use app\lib\exception\SeckillThemeMissException;
use app\xcx\controller\v1\SeckillCourse;

class SeckillTheme
{
    //获取秒杀活动主题
    public function getSeckillTheme(){
        $where = [
            'is_del'=>1,
            'type'=>1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type=1, $where, $field = 'id,name,start_time,end_time');
        if(!$info){
            throw new SeckillThemeMissException();
        }else{
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}