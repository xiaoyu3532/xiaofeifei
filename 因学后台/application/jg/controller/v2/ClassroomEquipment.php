<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/26 0026
 * Time: 15:11
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\NothingMissException;

class ClassroomEquipment extends BaseController
{
//获取机构二级分类列表
    public static function getClassroomEquipment()
    {
        //1用户，2机构
        $where = [
            'is_del' => 1,

        ];
        $table = request()->controller();
        $classroom_equipment_data = Crud::getData($table, 2, $where, 'id,name', 1, 1000);
        if ($classroom_equipment_data) {
            return jsonResponseSuccess($classroom_equipment_data);
        } else {
            throw new NothingMissException();
        }
    }
}