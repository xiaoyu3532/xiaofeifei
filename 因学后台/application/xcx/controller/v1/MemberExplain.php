<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 18:58
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberExplainMissException;

class MemberExplain
{
    //会员简介获取成功
    public static function getMemberExplain()
    {
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where = '1=1', $field = '*');
        if (!$info) {
            throw new MemberExplainMissException();
        } else {
//            dump($info);
//            if (is_serialized($info['title'])) {
                $info['title'] = unserialize($info['title']);
//            }
//            dump($info);
//            if (is_serialized($info['title'])) {
                $info['name'] = unserialize($info['name']);
//            }
//            dump($info);
            return jsonResponse('1000', '会员简介获取成功', $info);
        }
    }

}