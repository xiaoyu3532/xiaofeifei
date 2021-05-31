<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 15:13
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\CategoryMissException;

class StudyType
{
    /**
     *
     */
    public static function getStudyType()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name');
        if(!$info){
            throw new CategoryMissException();
        }else{
            $table1 = 'study_type_son';
            foreach ($info as $k=>$v){
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'st_id'=>$v['id']
                ];
                $data = Crud::getData($table1, $type = 2, $where, $field = 'id,name');
                $info[$k]['TypeSon'] = $data;
            }
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}