<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 13:40
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\MemberMissException;
use app\validate\MemberIDSMustBePostiveInt;

class Member
{
    /**
     * 获取附近机构列表
     *
     */
    public static function getMember($longitude, $latitude, $cid = '0', $cname = '',$is_video='') //$csid='0'小分类id
    {
        if($is_video ==1){
            if ($cid == 0) {
                $where = [
//                'm.type' => 3,
                    'm.status' => 1,
//                    'c.type' => 1,
//                    'c.is_del' => 1,
                    'm.is_del' => 1,
                    'm.video_num' =>  array('gt',0)
                ];
            } else {
                $where = [
//                'm.type' => 3,
                    'm.status' => 1,
                    'm.is_del' => 1,
                    'm.caid' => $cid,
//                    'c.type' => 1,
//                    'c.is_del' => 1,
                    'm.video_num' =>  array('gt',0)
                ];
            }
        }else{
            if ($cid == 0) {
                $where = [
//                'm.type' => 3,
                    'm.status' => 1,
                    'c.type' => 1,
                    'c.is_del' => 1,
                    'm.is_del' => 1,
                ];
            } else {
                $where = [
//                'm.type' => 3,
                    'm.status' => 1,
                    'm.is_del' => 1,
                    'm.caid' => $cid,
                    'c.type' => 1,
                    'c.is_del' => 1,
                ];
            }
        }

        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];

//        dump($where);exit;
        if(empty($latitude)||empty($longitude)){
            $latitude ='30.2741500000';
            $longitude ='120.1551500000';
        }
        $table = request()->controller();
        $join = [
            ['yx_course co', 'm.uid = co.mid', 'left'],
            ['yx_category c', 'co.cid = c.id', 'left']
        ];
        $alias = 'm';
        $field = ['m.uid,c.name,co.name coname,m.cname,m.logo,m.img,m.remarks,service_student,found_time,kf_phone,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance';
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, $pageSize,'m.uid');

        if (!$info) {
            throw new MemberMissException();
        } else {
            $table1 = 'course';
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'mid' => $v['uid']
                ];
                $info[$k]['img'] = $v['logo'];
                $info[$k]['num'] = Crud::getCount($table1, $where);
                if (!empty($v['logo'])) {
                    $logo = get_take_img($v['logo']);
                    if(!empty($logo)){
                        $info[$k]['logo'] = $logo[0];
                        $info[$k]['img'] = $logo[0];
                    }
                }

            }
            return jsonResponse('1000', '获取附近机构列表', $info);
        }
    }

    /**
     * 获取首页推荐机构
     */
    public static function getMemberRecom()
    {
        $where = [
//            'type' => 3,
            'status' => 1,
            'recom' => 1,
        ];
        $table = request()->controller();
        $field = ['uid,cname,logo,cover_img,remarks,sort,address,course_num,browse_num'];
        $order = 'sort';
        $page = max(input('param.page/d', 1), 1);
        $pageSize = input('param.numPerPage/d', 16);
        $info = Crud::getData($table, $type = 2, $where, $field, $order, $page, $pageSize);
        if (!$info) {
            throw new MemberMissException();
        } else {
            $table1 = 'course';
            foreach ($info as $k => $v) {
                $where1 = [
                    'is_del' => 1,
                    'type' => 1,
                    'mid' => $v['uid']
                ];
                $field1 = 'id,fire,img,name,present_price,enroll_num,recruit,c_num,aid,mid';
                $course_data = Crud::getData($table1, $type = 2, $where1, $field1, $order, $page, $pageSize);
                foreach ($course_data as $kk => $vv) {
                    $course_data[$kk]['status'] = 1;
                }
                $course_data = Crud::getage($course_data, 2);//获取学习对象
                $info[$k]['course_data'] = $course_data;

            }
            return jsonResponse('1000', '获取首页推荐机构', $info);
        }
    }

    /**
     * 获取机构详情
     */
    public static function getMemberDetails()
    {
        $data = input();
        (new MemberIDSMustBePostiveInt())->goCheck();
        $where = [
            'is_del' => 1,
//            'type' => 3,
            'status' => 1,
            'uid' => $data['mem_id']
        ];
        Crud::IncMemberNum($data['mem_id']);
        $table = request()->controller();
        //img 机构展示图
        //wheel_img 机构轮播图
        $field = ['uid,cname,remarks,address,longitude,latitude,introduction,img,wheel_img,service_student,found_time,kf_phone,is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-longitude*PI()/180)/2),2)))*1000) AS distance'];
        $info = Crud::getData($table, $type = 1, $where, $field);
        if (!$info) {
            throw new MemberMissException();
        } else {
            //判读是否是序列化字符串
            if (is_serialized($info['wheel_img'])) {
                $info['wheel_img'] = unserialize($info['wheel_img']);
            }
            return jsonResponse('1000', '成功获取机构详情', $info);
        }
    }

}