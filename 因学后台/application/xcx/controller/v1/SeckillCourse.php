<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 14:07
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\SeckillCourseMissException;
use app\validate\SeckIDMustBePostiveInt;

class SeckillCourse
{
    //获取秒杀商品
    //$st_id 秒杀主题ID
    //$cid 小分类ID搜索
    //$cou_name 名称搜索
    public static function getSeckillCourse($st_id, $latitude, $longitude, $cid = '', $cou_name = '', $page = '1', $pageSize = '16')
    {
        (new SeckIDMustBePostiveInt())->goCheck();
        $where = [
            'sc.is_del' => 1,
            'sc.type' => 1,
            'sc.examine_type' => 2, //1待审核，2通过，3拒绝
            'sc.seckill_theme_id' => $st_id, //秒杀主题id
            'c.is_del' => 1,
            'c.type' => 1,
            'st.type' => 1,
            'st.is_del' => 1,
        ];
        (isset($cou_name) && !empty($cou_name)) && $where['c.name'] = ['like', '%' . $cou_name . '%'];
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
        (isset($cid) && !empty($cid)) && $where['c.cid'] = $cid;
        $table = request()->controller();
        $type = 2;
        $join = [
            ['yx_curriculum c', 'sc.curriculum_id = c.id', 'left'],
            ['yx_category_small cs', 'c.csid = cs.id', 'left'], //小分类
            ['yx_seckill_theme st', 'sc.seckill_theme_id = st.id', 'left'], //秒杀主题
            ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
        ];
        $alias = 'sc';
        $field = ['sc.img,sc.id,c.title,sc.c_num,c.name,sc.enroll_num,c.aid,sc.present_price,sc.surplus_num,sc.original_price,st.start_time,st.end_time,sc.start_age,sc.end_age,cs.name csname,cl.longitude,cl.latitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-cl.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(cl.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-cl.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance asp';
        $info = Crud::getRelationData($table, $type, $where, $join, $alias, $order, $field, $page, $pageSize);
        if (!$info) {
            throw new SeckillCourseMissException();
        } else {
//            $info = Crud::getage($info, 2);
            foreach ($info as $k => $v) {
                if ($v['present_price'] > 0) {
//                    $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'],2);
//                    $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'],2);
                }
                $info[$k]['status'] = 4;
                if (!empty($v['img'])) {
                    $info[$k]['img'] = unserialize($v['img']);
                }
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
            }
            return jsonResponse('1000', '获取秒杀列表成功', $info);
        }
    }
}