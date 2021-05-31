<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26 0026
 * Time: 19:14
 */

namespace app\xcx\controller\v1;
use app\common\model\Crud;
use app\lib\exception\ExperienceListMissException;


class ExperienceList
{
    public function getExperienceList()
    {
        $data = input();
        $where = [
            'e.type' => 1,
            'e.is_del' => 1,
            'c.is_del' => 1,
            'c.type' => 1,
        ];
        (isset($data['name'] ) && !empty($data['name'] )) && $where['c.name'] = ['like', '%' . $data['name'] . '%']; //搜索课程名
        (isset($data['cid'] ) && !empty($data['cid'] )) && $where['c.cid'] =  $data['cid'] ; //传入大分类ID
        $table = request()->controller();
        $join = [
            ['yx_course c', 'e.ou = c.id', 'left'],
        ];
        $field = 'e.id,e.price,c.name,c.img,c.title,c.aid,c.enroll_num,c.original_price,c.id cou_id,c.cid';
        $alias = 'e';
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field, $page, $pageSize);
        if (!$info) {
            throw new ExperienceListMissException();
        } else {
            //将年龄ID字符串变为数组
            $info = Crud::getage($info, 2);
            return jsonResponse('1000','成功获取活动图',$info);
        }
    }

}