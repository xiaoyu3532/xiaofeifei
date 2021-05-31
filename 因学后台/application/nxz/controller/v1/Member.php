<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/9 0009
 * Time: 12:14
 */

namespace app\nxz\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberMissException;

class Member extends Base
{
    //获取逆行者机构列表
    public static function getContrarianMemberList()
    {
        $data = input();
        $where = [
            'm.status' => 1,
            'm.is_del' => 1,
            'co.is_del' => 1,
        ];

        isset($data['Classification_id']) && !empty($data['Classification_id']) && $where['c.id'] = $data['Classification_id'];
        if (isset($data['Classification_id']) && ($data['Classification_id'] == 1)) {
            unset($where['c.id']);
        }
        isset($data['name']) && !empty($data['name']) && $where['co.name'] = ['like', '%' . $data['name'] . '%'];
//        if (empty($latitude) || empty($longitude)) {
//            $latitude = '30.2741500000';
//            $longitude = '120.1551500000';
//        }
        $table = request()->controller();
        $join = [
            ['yx_contrarian_course co', 'm.uid = co.mem_id', 'right'],
            ['yx_contrarian_classification c', 'co.classification_id = c.id', 'left']
        ];
        $alias = 'm';
        $field = ['m.uid,c.name,co.name coname,m.cname,m.browse_num,m.logo,m.img,m.remarks,kf_phone,m.is_verification,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, '', 'm.uid');
        if (!$info) {
            throw new MemberMissException();
        } else {
            foreach ($info as $k => $v) {
                if (!empty($v['logo'])) {
                    $logo = get_take_img($v['logo']);
                    if (!empty($logo)) {
                        $info[$k]['logo'] = $logo[0];
                    }
                }
            }
            return jsonResponseSuccess($info);
        }

    }

    //获取逆行者机构详情
    public static function getContrarianMember()
    {
        $data = input();
        $where = [
            'm.status' => 1,
            'm.is_del' => 1,
            'm.uid' => $data['mem_id']
        ];
//        if (empty($latitude) || empty($longitude)) {
//            $latitude = '30.2741500000';
//            $longitude = '120.1551500000';
//        }
        $table = request()->controller();
        $join = [
            ['yx_contrarian_course co', 'm.uid = co.mem_id', 'right'],
            ['yx_contrarian_classification c', 'co.classification_id = c.id', 'left']
        ];
        $alias = 'm';
        $field = ['m.uid,m.label,c.name,co.name coname,m.cname,m.browse_num,m.logo,m.img,m.remarks,m.kf_phone phone,m.is_verification,m.address,m.latitude,m.longitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $order = 'distance';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order, $field, $page, 'm.uid');
        if (!$info) {
            throw new MemberMissException();
        } else {
            self::addBrowseNum($data['mem_id']);
            if (!empty($info['logo'])) {
                $logo = get_take_img($info['logo']);
                if (!empty($logo)) {
                    $info['logo'] = $logo[0];
                }
            }
            if (!empty($info['label']) && is_serialized($info['label'])) {
                $info['label'] = unserialize($info['label']);
            }
            if(empty($info['phone'])){
                $info['phone'] ='400-006-0996';
            }
            return jsonResponseSuccess($info);
        }

    }

    //添加浏览数
    public static function addBrowseNum($mem_id)
    {
        $info = Crud::setIncs('member', ['uid' => $mem_id], 'browse_num');
    }

}