<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 19:16
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CommunityListMissException;

class SyntheticalCourse
{
    /**
     * 获取综合体课程列表
     * @throws \Exception
     */
    public function getSyntheticalCourse($page = '1', $latitude, $longitude,$cou_name ='',$syname='',$search_type =1)
    {
        $data = input();
        $where = [
            'sc.type' => 1,
            'sc.is_del' => 1,
            'ca.is_del' => 1,
            'ca.type' => 1,
//            'co.is_del' => 1,
        ];
        (isset($data['sy_id']) && !empty($data['sy_id'])) && $where['sc.syntheticalcn_id'] = $data['sy_id'];
        (isset($cou_name) && !empty($cou_name)) && $where['cu.name'] = ['like', '%' . str_replace(" ", '', $cou_name) . '%'];
        (isset($syname) && !empty($syname)) && $where['sy.name'] = ['like', '%' . str_replace(" ", '', $syname) . '%'];
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
        $table = request()->controller();
        $join = [
            ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
            ['yx_synthetical_name sy', 'sc.syntheticalcn_id = sy.id', 'left'],
            ['yx_category ca', 'sc.curriculum_cid = ca.id', 'left'],
            ['yx_classroom scm', 'sc.classroom_id = scm.id', 'left'],
        ];
        $field = ['sc.id cou_id,sc.c_num,sc.present_price,cu.name,sc.img,sc.title,sc.start_age,sc.end_age,sc.start_time,sc.end_time,sc.enroll_num,sc.original_price,ca.name caname,scm.longitude,scm.latitude,sy.name syname,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-scm.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(scm.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-scm.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $alias = 'sc';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'distance asp', $field, $page);

        if (!$info) {
            if ($search_type == 1) {
                $info = self::getSyntheticalCourse($page, $latitude, $longitude,'',$cou_name,2);
                return $info;
            }
            throw new CommunityListMissException();
        } else {
            //将年龄ID字符串变为数组
            foreach ($info as $k => $v) {
                if ($v['present_price'] > 0) {
                    $info[$k]['original_price'] = round($v['original_price'] / $v['c_num'],2);
                    $info[$k]['present_price'] = round($v['present_price'] / $v['c_num'],2);
                }
                $info[$k]['status'] = 5;
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
                $info[$k]['img'] = get_take_img($v['img']);
            }
            return jsonResponseSuccess($info);
        }
    }
}