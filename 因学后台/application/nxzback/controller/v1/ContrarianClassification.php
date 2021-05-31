<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/9 0009
 * Time: 11:57
 */

namespace app\nxzback\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CategoryMissException;

class ContrarianClassification extends Base
{
    //获取首页分类
    public static function getContrarianClassification()
    {
        $where = [
            'is_del' => 1,
            'id' =>['<>',1],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name');
        if (!$info) {
            throw new CategoryMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

}