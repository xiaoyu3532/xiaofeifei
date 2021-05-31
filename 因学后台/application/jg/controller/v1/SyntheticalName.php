<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/18 0018
 * Time: 10:40
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class SyntheticalName extends BaseController
{
    //获取综合体名称
    public static function getjgSyntheticalName()
    {
        $where = [
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name', $order = 'sort desc', $page = 1, 1000);
        if (!$info) {
            throw new NothingMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

}