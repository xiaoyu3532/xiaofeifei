<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/29 0029
 * Time: 15:35
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class ZhtActivityOrder extends BaseController
{
    //获取活动订单  yx_zht_activity_order
    public static function getZhtActivityOrder($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $data['mem_id'] = ['in', $mem_ids];
            }
            $where = [
                'zao.mem_id' => $data['mem_id'],
                'zao.is_del' => 1
            ];
        } else {
            throw new ISUserMissException();
        }


        isset($data['activity_order_num']) && !empty($data['activity_order_num']) && $where['zao.activity_order_num'] = $data['activity_order_num'];
        isset($data['status']) && !empty($data['status']) && $where['zao.status'] = $data['status'];
        isset($data['student_name']) && !empty($data['student_name']) && $where['s.student_name'] = ['like', '%' . $data['student_name'] . '%'];
        isset($data['phone']) && !empty($data['phone']) && $where['zao.phone'] = ['like', '%' . $data['phone'] . '%'];
        (isset($data['activity_type']) && !empty($data['activity_type'])) && $where['a.activity_type'] = $data['activity_type']; //活动类型
        (isset($data['iscourse_type']) && !empty($data['iscourse_type'])) && $where['zao.iscourse_type'] = $data['iscourse_type']; //活动类型
        if ((isset($time_data) && !empty($time_data))) {
            $start_time = $time_data[0] / 1000;
            $end_time = $time_data[1] / 1000;
            $where['zao.create_time'] = ['between', [$start_time, $end_time]];
        }
        $join = [
            ['yx_zht_activity a', 'zao.activity_id = a.id', 'left'],  //活动
            ['yx_member m', 'zao.mem_id = m.uid', 'left'],  //机构
            ['yx_lmport_student ls', 'zao.student_id = ls.id', 'left'],  //学生
            ['yx_zht_course zc', 'zao.course_id = zc.id', 'left'],  //课程
            ['yx_zht_course_num zcn', 'zao.course_num_id = zcn.id', 'left'],  //课时
        ];
        $alias = 'zao';
        $table = 'zht_activity_order';
        $order_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zao.create_time desc', $field = 'zao.*,m.cname,m.province,m.city,m.area,m.address,m.phone member_phone,ls.student_name,a.activity_img,a.activity_title,a.activity_type,a.activity_price,zao.iscourse_type,zc.course_name,zc.course_img,zao.settlement_price,zcn.course_section_price,zcn.course_section_num,a.activity_start_time,a.activity_end_time', $page, $pageSize);
        if ($order_data) {
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zao.id');
//            dump($order_data);exit;
            //获取当前活动价格
            $current_price = Crud::getData('zht_activity_order', 1, ['activity_id' => $order_data[0]['activity_id'], 'status' => ['in', [1, 3]]], 'price', 'id desc');
            foreach ($order_data as $k => $v) {
                $order_data[$k]['original_price'] = $v['price'];
                $order_data[$k]['settlement_price'] = $v['price'] - $v['return_price'];

                //求家长信息 yx_user_student
                $where_user = [
                    'us.user_id' => $v['user_id'],
                    'us.is_del' => 1,
                    'u.is_del' => 1
                ];
                $join = [
                    ['yx_user u', 'us.user_id = u.id', 'left'],  //用户
                ];
                $alias = 'us';
                $user_data = Crud::getRelationData('user_student', $type = 1, $where_user, $join, $alias, '', $field = 'us.relation,u.name,u.phone,u.user_identifier', $page, $pageSize);
                if ($user_data) {
                    $order_data[$k]['user_name'] = $user_data['name'];
                    $order_data[$k]['relation'] = $user_data['relation'];
                    $order_data[$k]['phone'] = $user_data['phone'];
                    $order_data[$k]['user_identifier'] = $user_data['user_identifier'];
                } else {
                    $order_data[$k]['user_name'] = '-';
                    $order_data[$k]['relation'] = '-';
                    $order_data[$k]['phone'] = '-';
                    $order_data[$k]['user_identifier'] = '-';
                }

                //赋值目前拼团价格
                if (!empty($current_price['price']) && $current_price['price'] > 0) {
                    $order_data[$k]['current_price'] = $current_price['price'];
                } else {
                    $order_data[$k]['current_price'] = $v['activity_price'];
                }

                $order_data[$k]['activity_time'] = date('Y-m-d H:i:s', $v['activity_start_time']) . '至' . date('Y-m-d H:i:s', $v['activity_end_time']);
                $order_data[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
                if (!empty($v['activity_end_time'])) {
                    $order_data[$k]['activity_end_time'] = date('Y-m-d H:i:s', $v['activity_end_time']);
                } else {
                    $order_data[$k]['activity_end_time'] = '';
                }
                if (!empty($v['activity_settlement_time'])) {
                    $order_data[$k]['activity_settlement_time'] = date('Y-m-d H:i:s', $v['activity_settlement_time']);
                } else {
                    $order_data[$k]['activity_settlement_time'] = '';
                }
                $order_data[$k]['maddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
                if ($v['activity_type'] == 1) { //1接龙工具，2砍价工具
                    $order_data[$k]['activity_type_name'] = '接龙工具';
                } elseif ($v['activity_type'] == 2) {
                    $order_data[$k]['activity_type_name'] = '砍价工具';
                }
                if ($v['iscourse_type'] == 1) { //1有课程绑定，2无课程绑定
                    $order_data[$k]['iscourse_type_name'] = '未关联课程';
                } elseif ($v['iscourse_type'] == 2) {
                    $order_data[$k]['iscourse_type_name'] = '关联课程';
                }

                if ($v['status'] == 1) { //1进行中，2待付款，3已完成
                    $order_data[$k]['status_name'] = '进行中';
                } elseif ($v['status'] == 2) {
                    $order_data[$k]['status_name'] = '待付款';
                } elseif ($v['status'] == 3) {
                    $order_data[$k]['status_name'] = '已完成';
                }
//                $order_data[$k]['return_price'] = $v['price'] - $v['settlement_price'];
            }

            $info_data = [
                'info' => $order_data,
                'num' => $num,
                'pageSzie' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }
}