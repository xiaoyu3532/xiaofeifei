<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 11:31
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CourseMissException;
use app\validate\CourseIDSMustBePostiveInt;

class HotRecom
{
    /**
     * 获取热门推荐课程列表
     */
    public static function getHotRecom($cid = '', $cou_name = '', $latitude, $longitude, $cname = '', $hot_type = 1)
    {
        $where = [
            'hr.is_del' => 1,
            'c.type' => 1
        ];
        (isset($cid) && !empty($cid)) && $where['cu.cid'] = $cid;
        (isset($cou_name) && !empty($cou_name)) && $where['cu.name'] = ['like', '%' . $cou_name . '%'];
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
//        dump($where);exit;
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $table = request()->controller();
        $join = [
            ['yx_member m', 'hr.mem_id = m.uid', 'left'],
            ['yx_course c', 'hr.cou_id = c.id', 'left'],
            ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'], //课目
            ['yx_category ca', 'cu.cid = ca.id', 'left'],
            ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
        ];
        $alias = 'hr';
        $field = ['hr.id,m.cname,hr.cou_id,c.img,c.mid,c.title,c.present_price,c.c_num,c.start_age,c.end_age,c.original_price,c.enroll_num,cu.name,c.recruit,c.aid,m.cname,cu.cid,ca.name caname,c.sort,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, $pageSize);
        if (!$info) {
            if ($hot_type == 1) {
                $info = self::getHotRecom($cid, '', $latitude, $longitude, $cou_name, 2);
                return $info;
            }

            throw new CourseMissException();
        } else {
            $info = Crud::getage($info, 2);
            foreach ($info as $k => $v) {
                $info[$k]['status'] = 1;
                if (!empty($v['img'])) {
                    $info[$k]['img'] = unserialize($v['img']);
                }
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
                $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'], 2);
                $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'], 2);
            }
            return jsonResponse('1000', '成功推荐机构', $info);
        }

    }

}