<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 19:13
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CommunityMissException;

class SyntheticalName
{
    /**
     * 获取综合列表
     * @throws CommunityMissException
     * @throws \Exception
     */
    public function getSyntheticalName()
    {
        $where = [
            'type' => 1,
            'is_del' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 2, $where, $field = 'id,name');
        if (!$info) {
            throw new CommunityMissException();
        } else {
            return jsonResponseSuccess($info);
        }
    }

}