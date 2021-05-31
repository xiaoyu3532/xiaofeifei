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
use app\lib\exception\NothingMissException;


class ExperienceCourse
{
    //获取课程体验课
    //$cid 小分类ID搜索
    //$cou_name 名称搜索
    public static function getExperienceCourse($latitude,$longitude,$cid = '', $cou_name = '', $page = '1')
    {
        $where = [
            'ex.is_del' => 1,
            'ex.type' => 1,
            'c.is_del' => 1,
            'c.type' => 1,
            'cs.type' => 1,
            'cs.is_del' => 1,
        ];
        (isset($cou_name) && !empty($cou_name)) && $where['c.name'] = ['like', '%' . $cou_name . '%'];
        (isset($cid) && !empty($cid)) && $where['c.cid'] = $cid;
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
        $table = request()->controller();
        $type = 2;
        $join = [
            ['yx_curriculum c', 'ex.curriculum_id = c.id', 'left'],
            ['yx_category_small cs', 'c.csid = cs.id', 'left'], //小分类
            ['yx_classroom cl', 'ex.classroom_id = cl.id', 'left'],
        ];
        $alias = 'ex';
        $field = ['ex.img,ex.id,ex.title,c.name,ex.enroll_num,ex.present_price,ex.c_num,ex.surplus_num,ex.original_price,ex.start_age,ex.end_age,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance asp';
        $info = Crud::getRelationData($table, $type, $where, $join, $alias, $order, $field, $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                if ($v['present_price'] > 0) {
                    $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'],2);
                    $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'],2);
                }
                $info[$k]['status'] = 2;
                if (!empty($v['img'])) {
                    $info[$k]['img'] = unserialize($v['img']);
                }
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
            }
            return jsonResponse('1000', '获取秒杀列表成功', $info);
        }
    }

}