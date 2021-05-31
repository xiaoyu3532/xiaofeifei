<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/9 0009
 * Time: 11:57
 */

namespace app\nxz\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;
use app\lib\exception\MemberMissException;

class ContrarianCourse extends Base
{
    //逆行者课程
    public static function getContrarianCourse()
    {
        $data = input();
        $where = [
            'mem_id' => $data['mem_id'],
            'is_del' => 1,
            'type' => 1,//1正常，2禁用
            'course_status' => 1, //1未开始，2进行中，3已结束
        ];
        $table = request()->controller();
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getData($table, 2, $where, 'id,img,name,title', $order = '', $page);
        if (!$info) {
            throw new MemberMissException();
        } else {
            return jsonResponseSuccess($info);
        }

    }

    //课程详情
    public static function getContrarianCoursedetails()
    {
        $data = input();
        $where = [
            'co.id' => $data['id'],
            'co.is_del' => 1,
            'co.type' => 1,//1正常，2禁用
            'co.course_status' => 1, //1未开始，2进行中，3已结束
        ];
        $table = request()->controller();
        $join = [
            ['yx_member m', 'co.mem_id = m.uid', 'right'],
        ];
        $alias = 'co';
        $field = ['co.id,m.uid mem_id,co.img,co.name,m.province,m.city,m.area,m.address,m.phone,co.title,co.details,co.details_img,m.latitude,m.longitude,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $data['latitude'] . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $data['latitude'] . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $data['longitude'] . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance'];
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, '', $field, $page);
        if ($info) {
            if (!empty($info['details_img']) && is_serialized($info['details_img'])) {
                $info['details_img'] = unserialize($info['details_img']);
            }
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }

    }

}