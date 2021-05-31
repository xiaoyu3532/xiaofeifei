<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/18 0018
 * Time: 17:38
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\NothingMissException;

class CommunityTeacher extends BaseController
{
    //获取社区老师
    public static function getpcCommunityTeacher($page = '1', $pageSize = '16', $name = '')
    {

        $where = [
            't.is_del' => 1,
        ];
        (isset($name) && !empty($name)) && $where['t.name'] = ['like', '%' . $name . '%'];
        $join = [
            ['yx_teacher_type tt', 't.type_id = tt.id', 'left'],
            ['yx_community_name cn', 't.community_id = cn.id', 'left'],
        ];
        $alias = 't';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 't.create_time desc', $field = 't.id,t.name,t.brief,t.img,t.create_time,t.id tea_id,tt.name typename,cn.name cnname', $page, $pageSize);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) {
                if (!empty($v['img'])) {
                    $imgs = unserialize($v['img']);
                    $cname_data[$k]['img'] = $imgs[0];
                }
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*');
            $info_data = [
                'info' => $cname_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //添加社区老师
    public static function addjgCommunityTeacher()
    {
        $data = input();
        if (isset($data['img']) && !empty($data['img'])) {
            $mlicense_array = [];
            foreach ($data['img'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['img'] = serialize($mlicense_array);
        }
        $table = request()->controller();
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }
    }

    //修改社区老师
    public static function setjgCommunityTeacher()
    {
        $data = input();
        $where = [
            'id' => $data['tea_id']
        ];
        unset($data['tea_id']);
        if (isset($data['img']) && !empty($data['img'])) {
            $mlicense_array = [];
            foreach ($data['img'] as $k => $v) {
                if (isset($v['response'])) {
                    $mlicense_array[] = $v['response'];
                } else {
                    $mlicense_array[] = $v['url'];
                }
            }
            $data['img'] = serialize($mlicense_array);
        }
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //获取社区老师详情
    public static function getjgCommunityTeacherdetails($tea_id)
    {
        $where = [
            'is_del' => 1,
            'id' => $tea_id
        ];
        $table = request()->controller();
        $cname_data = Crud::getData($table, $type = 1, $where,  $field = 'name,brief,img,type_id,community_id');
        if ($cname_data) {
            if (!empty($cname_data['img'])) {
                $cname_data['img'] = unserialize($cname_data['img']);
                $img_data = [];
                foreach ($cname_data['img'] as $k => $v) {
                    $img_data[] = [
                        'name' => 'food.jpg',
                        'url' => $v
                    ];
                }
                $cname_data['img'] = $img_data;
            } else {
                $cname_data['img'] = [];
            }
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }


    }

    //删除社区老师
    public static function deljgCommunityTeacher($tea_id)
    {
        $where = [
            'id' => $tea_id
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $res = Crud::setUpdate($table, $where, $upData);
        if ($res) {
            return jsonResponseSuccess($res);
        } else {
            throw new NothingMissException();
        }
    }

}