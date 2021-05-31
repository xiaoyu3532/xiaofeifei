<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 20:52
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CourseMissException;
use app\lib\exception\NothingMissException;

class HotMember
{
    /**
     * 获取热门推荐机构列表
     */

    public static function getMemberRecom($page = 1)
    {
        $where = [
            'm.status' => 1,
            'm.is_del' => 1,
            'hm.is_del' => 1,
        ];

        $join = [
            ['yx_member m', 'hm.mem_id = m.uid', 'left'],
        ];
        $table = request()->controller();
        $alias = 'hm';
        $field = 'm.uid,m.cname,m.logo,m.cover_img,m.remarks,m.address,m.course_num,m.browse_num,hm.sort,m.province,m.city,m.area';
        $order = 'hm.sort';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $table1 = 'course';
            foreach ($info as $k => $v) {
                if (!empty($v['logo'])) {
                    $info[$k]['logo'] = get_take_img($v['logo']);
                }
                $where1 = [
                    'c.is_del' => 1,
                    'c.type' => 1,
                    'c.mid' => $v['uid'],
                ];
                $join = [
                    ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                ];
                $field = 'c.id,c.fire,c.img,cu.name,c.present_price,c.original_price,c.enroll_num,c.recruit,c.c_num,c.start_age,c.end_age,c.mid';
                $alias = 'c';
                $course_data = Crud::getRelationData($table1, $type = 2, $where1, $join, $alias, $order = '', $field);

                foreach ($course_data as $kk => $vv) {
                    $course_data[$kk]['status'] = 1;
                    if (!empty($vv['img'])) {
                        $course_data[$kk]['img'] = get_take_img($vv['img']);
                    }
                    $course_data[$kk]['age_name'] = $vv['start_age'] . '~' . $vv['end_age'];
                    if ($vv['present_price'] > 0) {
                        $course_data[$kk]['present_price'] = round($vv['present_price'] / $vv['c_num'], 2);
                        $course_data[$kk]['original_price'] = round($vv['original_price'] / $vv['c_num'], 2);
                    }

                }

                $info[$k]['course_data'] = $course_data;

            }
            return jsonResponse('1000', '获取首页推荐机构', $info);
        }
    }

}