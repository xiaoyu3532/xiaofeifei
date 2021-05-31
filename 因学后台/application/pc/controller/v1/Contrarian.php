<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/11 0011
 * Time: 11:13
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\MemberExplainMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\lib\exception\ISUserMissException;

class Contrarian extends BaseController
{
    //获取机构列表
    //is_verification 1 审核通过
    //is_verification 2 新注册机构
    //is_verification 3 待审核
    //is_verification 4 审核拒绝
    public static function getContrarianMemberlist($page = '1')
    {
        $data = input();
        $where = [];
        if (isset($data['is_verification']) && !empty($data['is_verification'])) {
            $table = 'member';
            if ($data['is_verification'] != 1) {
                $where['is_verification'] = $data['is_verification'];
                $where['is_del'] = 1;
                $where['status'] = 1;//1开启，2禁用
                $where['user_type'] = 3;//1客户添加机构，2为后台添加，3逆行者活动添加
                (isset($data['cname']) && !empty($data['cname'])) && $where['cname'] = ['like', '%' . $data['cname'] . '%'];
                if (isset($data['time']) && !empty($data['time'])) {
                    $start_time = $data['time'][0] / 1000;
                    $end_time = $data['time'][1] / 1000;
                    $where = [
                        'create_time' => ['between', [$start_time, $end_time]]
                    ];
                }
                $info = Crud::getData($table, $type = 2, $where, $field = 'uid,cname,nickname,create_time,phone,cumulative_price,cumulative_retreat_price,is_verification,organization', $order = 'uid desc', $page, $pageSize = '16');
                $num = Crud::getCounts($table, $where);
                if (!$info) {
                    throw new MemberExplainMissException();
                } else {
                    $info_data = [
                        'info' => $info,
                        'num' => $num,
                    ];
                    return jsonResponseSuccess($info_data);
                }
            } elseif ($data['is_verification'] == 1) {
                $where['m.is_verification'] = $data['is_verification'];
                $where['m.is_del'] = 1;
                $where['m.status'] = 1;//1开启，2禁用
                (isset($data['cname']) && !empty($data['cname'])) && $where['m.cname'] = ['like', '%' . $data['cname'] . '%'];
                if (isset($data['time']) && !empty($data['time'])) {
                    $start_time = $data['time'][0] / 1000;
                    $end_time = $data['time'][1] / 1000;
                    $where = [
                        'm.create_time' => ['between', [$start_time, $end_time]]
                    ];
                }
                $join = [
                    ['yx_contrarian_course co', 'm.uid = co.mem_id', 'right'],
                    ['yx_contrarian_classification c', 'co.classification_id = c.id', 'left']
                ];
                $alias = 'm';
                $field = ['m.uid,m.cname,m.nickname,m.create_time,m.phone,m.cumulative_price,m.cumulative_retreat_price,m.is_verification,m.organization'];
                $order = 'm.uid desc';
                $page = max(input('param.page/d', 1), 1);
                $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order, $field, $page, '1', 'm.uid');
                $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'uid', $group = 'm.uid');
                if (!$info) {
                    throw new MemberMissException();
                } else {
                    $info_data = [
                        'info' => $info,
                        'num' => $num,
                    ];
                    return jsonResponseSuccess($info_data);
                }

            }

        } else {
//            self::getContrarianMemberlist($is_verification=2);
        }


    }

    //获取机构详情
    public static function getContrarianMemberdetails($mem_id)
    {
        $where = [
            'uid' => $mem_id,
        ];
        $info = Crud::getData('member', $type = 1, $where, $field = 'username,phone,logo,last_login_time,nickname,province,city,area,address,cname,create_time,aclass,mlicense,remarks,introduction,balance,ismember,re_num,enroll_num,course_num,browse_num,is_verification,cumulative_price,cumulative_retreat_price,organization');
        if (!$info) {
            throw new NothingMissException();
        } else {
            if (!empty($info['mlicense'])) {
                $info['mlicense'] = unserialize($info['mlicense']);
            }
            if (!empty($info['logo'])) {
                $info['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
            }
            return jsonResponseSuccess($info);
        }
    }

    //获取机构课程列表
    public static function getContrarianCurriculum($page = '1', $name = '', $classification_id = '', $cname = '')
    {
        $where = [
            'c.is_del' => 1,
//            'c.type' => 1,
//            'c.mem_id' => $mem_id,
        ];
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];
        //分类
        (isset($classification_id) && !empty($classification_id)) && $where['c.classification_id'] = $classification_id;


        $join = [
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //机构名称
            ['yx_contrarian_classification cc', 'c.classification_id = cc.id', 'left'], //分类
        ];
        $alias = 'c';
        $info = Crud::getRelationData('contrarian_course', $type = 2, $where, $join, $alias, $order = 'id desc', $field = 'm.logo,m.create_time,m.phone,c.id,c.name,c.title,c.type,m.cname,cc.name ccname', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            foreach ($info as $k => $v) {
                if (!empty($v['logo']) && is_serialized($v['logo'])){
                    $logo = unserialize($v['logo']);
                    $info[$k]['logo'] = $logo[0];
                }
            }
            $num = Crud::getCountSelNun('contrarian_course', $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取机构课程列表
    public static function getContrarianCurriculums($page = '1', $name = '', $classification_id = '', $cname = '')
    {
        $where = [
            'c.is_del' => 1,
            'c.type' => 1,
//            'c.mem_id' => $mem_id,
        ];
        (isset($name) && !empty($name)) && $where['c.name'] = ['like', '%' . $name . '%'];
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%'];
        //分类
        (isset($classification_id) && !empty($classification_id)) && $where['c.$classification_id'] = $classification_id;


        $join = [
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //机构名称
            ['yx_contrarian_classification cc', 'c.classification_id = cc.id', 'left'], //分类
        ];
        $alias = 'c';
        $info = Crud::getRelationData('contrarian_course', $type = 2, $where, $join, $alias, $order = 'id desc', $field = 'c.id,c.name,c.title,c.type,m.cname,cc.name ccname', $page);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $num = Crud::getCountSelNun('contrarian_course', $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        }
    }

    //获取小订单列表
    public static function getContrarianOrderList($page = '1', $name = '', $order_id = '', $sname = '', $status = '', $cou_status = '', $time = '', $cname = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] != 6) { //6为总平台
            throw new ISUserMissException();
        }
        $where = [
            'o.is_del' => 1,
            'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            'o.cou_status' => 6, //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体，6逆行者课程
        ];
        if ((isset($time) && !empty($time))) {
            $start_time = strtotime($time[0]);
            $end_time = strtotime($time[1]);
            $where['o.create_time'] = ['between', [$start_time, $end_time]];
        }
        (isset($cname) && !empty($cname)) && $where['m.cname'] = ['like', '%' . $cname . '%']; //机构名查询
        (isset($name) && !empty($name)) && $where['o.name'] = ['like', '%' . $name . '%']; //课程名查询
        (isset($sname) && !empty($sname)) && $where['s.name'] = ['like', '%' . $sname . '%']; //学生名查询
        (isset($order_id) && !empty($order_id)) && $where['o.order_id'] = ['like', '%' . $order_id . '%']; //订单号查询
        (isset($status) && !empty($status)) && $where['o.status'] = $status; //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
        (isset($cou_status) && !empty($cou_status)) && $where['o.cou_status'] = $cou_status; //1普通课程，2体验课程，3活动课程，4秒杀课程
        $join = [
//            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
            ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
            ['yx_member m', 'o.mid =m.uid ', 'left'],  //机构
            ['yx_contrarian_course co', 'o.cid =co.id ', 'left'],  //课程
            ['yx_contrarian_classification cc', 'co.classification_id =cc.id ', 'left'],  //分类
        ];
        $alias = 'o';
        $cname_data = Crud::getRelationData('order', $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'u.img,u.name,s.phone,s.age,m.cname,o.name,o.create_time,cc.name ccname', $page, 8);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) {
                $cname_data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $num = Crud::getCountSel('order', $where, $join, $alias, $field = '*');
            $info_data = [
                'info' => $cname_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }


    //上下架操作
    public static function editContrarianType($course_id, $type)
    {
        $where = [
            'id' => $course_id,
        ];
        $data = [
            'type' => $type
        ];
        $info = Crud::setUpdate('contrarian_course', $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //课程删除
    public static function delContrarianCurriculum($course_id)
    {
        $where = [
            'id' => $course_id,
        ];
        $data = [
            'is_del' => 2
        ];
        $info = Crud::setUpdate('contrarian_course', $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

}