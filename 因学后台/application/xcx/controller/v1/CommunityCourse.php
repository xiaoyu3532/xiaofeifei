<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26 0026
 * Time: 14:59
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CommunityListMissException;

class CommunityCourse
{
    /**
     * 获取社区活动（首页与社区活动列表）
     * @throws \Exception
     * com_id 社区ID 用户活动列表页用
     */
    public function getCommunityCourse($page = '1', $latitude, $longitude,$com_id='',$cou_name='',$community_name='', $search_type=1)
    {
//        $data = input();
        $where = [
            'cc.type' => 1,
            'cc.is_del' => 1,
            'ca.is_del' => 1,
            'ca.type' => 1,
//            'co.is_del' => 1,
        ];
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
//        (isset($data['com_id']) && !empty($data['com_id'])) && $where['cc.community_id'] = $data['com_id'];
        (isset($com_id) && !empty($com_id)) && $where['cc.community_id'] = $com_id;
//        (isset($data['cou_name']) && !empty($data['cou_name'])) && $where['cu.name'] = ['like', '%' . str_replace(" ", '', $data['cou_name']) . '%'];
        (isset($cou_name) && !empty($cou_name)) && $where['cu.name'] = ['like', '%' . str_replace(" ", '', $cou_name) . '%'];

        (isset($community_name) && !empty($community_name)) && $where['co.name'] = ['like', '%' . str_replace(" ", '', $community_name) . '%'];

        $table = request()->controller();
        $join = [
            ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
            ['yx_community_name co', 'cc.community_id = co.id', 'left'], //
            ['yx_category ca', 'cc.curriculum_cid = ca.id', 'left'],
            ['yx_community_classroom ccm', 'cc.classroom_id = ccm.id', 'left'],
        ];
        $field = ['cc.id cou_id,cc.present_price,cc.c_num,cc.by_time,cu.name,cc.img,cc.title,cc.start_age,cc.end_age,cc.enroll_num,cc.original_price,ca.name caname,ccm.longitude,ccm.latitude,co.name syname,cc.start_time,cc.end_time,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-ccm.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(ccm.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-ccm.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $alias = 'cc';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'distance', $field, $page);
        if (!$info) {
            if($search_type == 1){
                $info=self::getCommunityCourse($page, $latitude, $longitude,$com_id,'',$cou_name,2);
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
                $info[$k]['status'] = 3;
                $info[$k]['age_name'] = $v['start_age'] . '~' . $v['end_age'];
                $info[$k]['img'] = get_take_img($v['img']);
            }

            return jsonResponse('1000', '成功获取活动图', $info);
        }
    }


}