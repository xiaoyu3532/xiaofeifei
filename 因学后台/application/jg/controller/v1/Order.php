<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 15:10
 */

namespace app\jg\controller\v1;


use app\common\model\Crud;
use app\lib\exception\CourseMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Order extends BaseController
{
    //获取订单列表
    public static function getjgOrderList($page = 1, $name = '', $order_id = '', $sname = '', $status = '', $cou_status = '', $time = '', $mem_id = '')
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $mem_ids = bindingMember::getbindingjgMemberId();
            $where = [
                'o.is_del' => 1,
            ];
            if (isset($mem_id) && !empty($mem_id)) {//机构ID
                //验证传过的机构ID
                $isbindingjgMember = bindingMember::isbindingjgMember($mem_id);
                if ($isbindingjgMember != 1000) {
                    return $isbindingjgMember;
                }
                $where['o.mid'] = $mem_id;
            } else {
                $where['o.mid'] = ['in', $mem_ids];
            }
            (isset($mem_id) && !empty($mem_id)) && $where['o.mid'] = ['like', '%' . $mem_id . '%']; //机构ID

            if ((isset($time) && !empty($time))) {
                $start_time = strtotime($time[0]);
                $end_time = strtotime($time[1]);
                $where['o.create_time'] = ['between', [$start_time, $end_time]];
            }
            (isset($name) && !empty($name)) && $where['o.name'] = ['like', '%' . $name . '%']; //课程名查询
            (isset($sname) && !empty($sname)) && $where['s.name'] = ['like', '%' . $sname . '%']; //学生名查询
            (isset($order_id) && !empty($order_id)) && $where['o.order_id'] = ['like', '%' . $order_id . '%']; //订单号查询
            (isset($status) && !empty($status)) && $where['o.status'] = $status; //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            (isset($cou_status) && !empty($cou_status)) && $where['o.cou_status'] = $cou_status; //1普通课程，2体验课程，3活动课程，4秒杀课程
            $join = [
//            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
                ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
                ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
                ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //用户信息
                ['yx_member m', 'o.mid =m.uid ', 'left'],  //机构信息
            ];
            $alias = 'o';
            $table = request()->controller();
            $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.id,o.order_id,o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,o.create_time,o.start_time,o.c_num,t.name tname,o.classroom_id,o.see_type,m.cname,o.sname osname,o.sex osex,o.age oage,o.phone ophone', $page);
            if ($cname_data) {
                foreach ($cname_data as $k => $v) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
                    if ($v['cou_status'] == 1 || $v['cou_status'] == 2 || $v['cou_status'] == 4) {
                        $clname = Crud::getData('classroom', 1, ['id' => $v['classroom_id']], 'name');
                        $cname_data[$k]['clname'] = $clname['name'];
                    } elseif ($v['cou_status'] == 3) {
                        $clname = Crud::getData('community_classroom', 1, ['id' => $v['classroom_id']], 'name');
                        $cname_data[$k]['clname'] = $clname['name'];
                    } elseif ($v['cou_status'] == 5) {
                        $clname = Crud::getData('synthetical_classroom', 1, ['id' => $v['classroom_id']], 'name');
                        $cname_data[$k]['clname'] = $clname['name'];
                    }
                    if (empty($v['sname']) && empty($v['age'])) {
                        $cname_data[$k]['sname'] = $v['osname'];
                        $cname_data[$k]['sex'] = $v['osex'];
                        $cname_data[$k]['phone'] = $v['ophone'];
                        $cname_data[$k]['age'] = $v['oage'];
                    }
                    //验证体验课
                    $cname_data = self::verifySeeExperience($cname_data);
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
    }

    //订单详情
    public static function getjgOrderdetails($order_id)
    {
        $user_data = self::isuserData();
        if ($user_data['type'] != 2) { //1用户，2机构
            throw new ISUserMissException();
        }
        $where = [
            'o.is_del' => 1,
//                'o.mid' => $user_data['mem_id'],
            'o.id' => $order_id,
        ];
        $join = [
//            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
            ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
            ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //用户信息
        ];
        $alias = 'o';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = 'o.id,o.cid,o.order_id,o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,u.name uname,o.create_time,o.start_time,o.c_num,t.name tname,o.classroom_id');
        if ($cname_data) {
            $course_data = self::getCourseDetails($cname_data);
            $cname_data['age_name'] = $course_data['age_name'];
            $cname_data['enroll_num'] = $course_data['enroll_num']; //已报人数
            $cname_data['surplus_num'] = $course_data['surplus_num'];//库存
            $cname_data['start_time'] = $course_data['start_time'];//库存
            $cname_data['end_time'] = $course_data['end_time'];//库存
            $cname_data['province'] = $course_data['province'];//省
            $cname_data['city'] = $course_data['city'];//市
            $cname_data['area'] = $course_data['area'];//区
            $cname_data['address'] = $course_data['address'];//详细
            $cname_data['clname'] = $course_data['clname'];//教室名称

            if ($cname_data['cou_status'] == 2) {
                if ($cname_data['see_type'] == 2 && $cname_data['status'] == 8) {  //1可看，2不可看
//                $data[$k]['name'] = '*****';
                    $cname_data['sname'] = '*****';
                    $cname_data['sex'] = '**';
                    $cname_data['age'] = '**';
                    $cname_data['phone'] = '**';
                }
            }
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }
    }

    //修改小订单状态
    public static function setjgOrderStatus($order_id, $status)
    {
        $where = [
            'id' => $order_id,
        ];
        $upData = [
            'status' => $status
        ];
        $table = request()->controller();
        $cname_data = Crud::setUpdate($table, $where, $upData);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除小订单状态
    public static function deljgOrder($order_id)
    {
        $where = [
            'id' => $order_id,
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $cname_data = Crud::setUpdate($table, $where, $upData);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new UpdateMissException();
        }

    }

    //获取课程详情
    public static function getCourseDetails($data)
    {  //yx_seckill_course 秒杀课程  yx_experience_course 体验课程 yx_community_course 活动课程

        //判断是哪种类型的课进详情页
        if ($data['cou_status'] == 4) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'],
                ['yx_classroom cl', 'sc.classroom_id = cl.id', 'left'],
            ];
            $field = ['sc.id cou_id,cl.name clname, sc.start_age,sc.end_age,cu.wheel_img,sc.img,sc.mid,cu.name,cu.details,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,m.address,m.logo,cl.longitude,cl.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
            $alias = 'sc';
            $table = 'seckill_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('秒杀课程进入详情');
            //这是秒杀课程进入详情
        } elseif ($data['cou_status'] == 2) {
            $where1 = [
                'ec.is_del' => 1,
                'ec.type' => 1,
                'ec.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'ec.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'ec.mid = m.uid', 'left'],
                ['yx_classroom cl', 'ec.classroom_id = cl.id', 'left'],
            ];
            $field = ['ec.id cou_id,cl.name clname,ec.start_age,ec.end_age,cu.wheel_img,ec.img,ec.mid,cu.name,cu.details,ec.title,ec.present_price,ec.original_price,ec.enroll_num,ec.surplus_num,ec.start_time,ec.end_time,ec.c_num,m.cname,m.address,m.logo,cl.longitude,cl.latitude,m.course_num,m.logo,m.give_type,m.uid,m.ismember,m.balance,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
            $alias = 'ec';
            $table = 'experience_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('体验课程进入详情');
            //这是体验课程进入详情
        } elseif ($data['cou_status'] == 3) {
            $where1 = [
                'cc.is_del' => 1,
                'cc.type' => 1,
                'cc.id' => $data['cid'],
            ];
            $join = [
                ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],//社区课目
                ['yx_community_classroom cl', 'cc.classroom_id = cl.id', 'left'], //社区教室
            ];
            $field = 'cc.id cou_id,cl.name clname,cc.img,cu.wheel_img,cu.name,cu.details,cc.community_id,cc.title,cc.start_age,cc.end_age,cc.original_price,cc.enroll_num,cc.start_time,cc.end_time,cc.c_num,cc.present_price,cc.surplus_num,cc.by_time,cl.latitude,cl.longitude,cl.province,cl.city,cl.area,cl.address';
            $alias = 'cc';
            $table = 'community_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
//            dump('活动课程进入详情');
            //这是活动课程进入详情
        } elseif ($data['cou_status'] == 1) {

            $where1 = [
                'c.is_del' => 1,
                'c.type' => 1,
                'c.id' => $data['cid'],
            ];
            $join = [
                ['yx_member m', 'c.mid = m.uid', 'left'],
                ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                ['yx_classroom cl', 'c.classroom_id = cl.id', 'left'],
            ];
            $field = ['c.id cou_id,cl.name clname,cu.wheel_img,c.img,c.mid,cu.name,cu.details,c.title,c.aid,c.present_price,c.start_age,c.end_age,c.original_price,c.enroll_num,c.surplus_num,c.start_time,c.end_time,c.c_num,m.cname,m.address,m.logo,c.longitude,c.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,m.province,m.city,m.area'];
//            dump('普通课程');
            $alias = 'c';
            $table = 'course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);
        } elseif ($data['cou_status'] == 5) {
            $where1 = [
                'sc.is_del' => 1,
                'sc.type' => 1,
                'sc.id' => $data['cid'],
            ];
            $join = [
                ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],//课目
                ['yx_member m', 'sc.mid = m.uid', 'left'], //机构
                ['yx_synthetical_classroom scm', 'sc.classroom_id = scm.id', 'left'], //机构
            ];
            $field = ['sc.id cou_id,scm.name clname,sc.start_age,sc.end_age,sc.syntheticalcn_id,cu.wheel_img,cu.details,sc.img,sc.mid,cu.name,sc.title,sc.present_price,sc.original_price,sc.enroll_num,sc.surplus_num,sc.start_time,sc.end_time,sc.c_num,m.cname,scm.address,m.logo,scm.longitude,scm.latitude,m.course_num,m.logo,m.wheel_img mwheel_img,m.remarks,m.introduction,scm.province,scm.city,scm.area'];
            $alias = 'sc';
            $table = 'synthetical_course';
            $info_course = Crud::getRelationData($table, $type = 1, $where1, $join, $alias, $order = '', $field);

//            dump('活动课程进入详情');
            //这是活动课程进入详情
        }
        if (!$info_course) {
            throw new CourseMissException();
        } else {

            //将年龄ID字符串变为数组
            $info_course['age_name'] = $info_course['start_age'] . '~' . $info_course['end_age'];
            $info_course['status'] = $data['status'];

            //判读是否是序列化字符串
            if (!empty($info_course['wheel_img'])) {
                $info_course['wheel_img'] = get_take_img($info_course['wheel_img']);
            }
            if (!empty($info_course['mwheel_img'])) {
                $mwheel_img = get_take_img($info_course['mwheel_img']);
                $info_course['mwheel_img'] = $mwheel_img[0];
            }
            if (!empty($info_course['logo'])) {
                $info_course['logo'] = get_take_img($info_course['logo']);
            }
            if (!empty($info_course['img'])) {
                $info_course['img'] = get_take_img($info_course['img']);
            }
            return $info_course;
        }


    }

    //验证是否能查看体验课
    public static function verifySeeExperience($data)
    {
        foreach ($data as $k => $v) {
            if ($v['cou_status'] == 2) {
                if ($v['see_type'] == 2 && $v['status'] == 8) {  //1可看，2不可看
//                $data[$k]['name'] = '*****';
                    $data[$k]['sname'] = '*****';
                    $data[$k]['sex'] = '**';
                    $data[$k]['age'] = '**';
                    $data[$k]['phone'] = '**';
                }
            }
        }
        return $data;

    }

    //机构查看体验课名额
    public static function getjgSeeExperience($order_id)
    {
        //计算名额单价
        $user_price = Crud::getData('user_price', 1, ['is_del' => 1], 'price');
        //计算优惠
        $discount = Crud::getData('discount', 1, ['is_del' => 1], 'discount');
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $member_data = Crud::getData('member', 1, ['uid' => $user_data['mem_id'], 'is_del' => 1, 'status' => 1], 'balance,give_type');
            if (!$member_data) {
                return jsonResponse('1001', '机构信息有误');
            }
            //判断是否有赠送名称
            if ($member_data['give_type'] == 1) { //1有赠送名额
                //查询赠送名额
                $give_num = Crud::getData('give_num', 1, ['mid' => $user_data['mem_id'], 'is_del' => 1], 'num');
                if (!$give_num) {
                    return jsonResponse('1002', '赠送名额有误');
                }
                if ($give_num['num'] > 0) {
                    $nums = Crud::setDecs('give_num', ['mid' => $user_data['mem_id'], 'is_del' => 1], 'num');
                    if (!$nums) {
                        return jsonResponse('1003', '减名称失败');
                    }
                    //查询赠送名额是否为0
                    $numt = Crud::getData('give_num', 1, ['mid' => $user_data['mem_id'], 'is_del' => 1], 'num');
                    if (!$numt) {
                        return jsonResponse('1004', '赠送名额有误');
                    }
                    if ($numt['num'] <= 0) {//如果用户赠送名额为0时，修改机构赠送状态
                        $member_update = Crud::setUpdate('member', ['uid' => $user_data['mem_id'], 'is_del' => 1], ['give_type' => 2]);
                        if (!$member_update) {
                            return jsonResponse('1005', '修改机构增送状态失败');
                        }
                    }
                } elseif ($give_num['num'] <= 0) {
                    $member_update = Crud::setUpdate('member', ['uid' => $user_data['mem_id'], 'is_del' => 1], ['give_type' => 2]);
                    if (!$member_update) {
                        return jsonResponse('1005', '修改机构增送状态失败');
                    }
                    $data_info = self::getjgSeeExperience($order_id);
                    return $data_info;
                }
                //验证订单
                $order_data = Crud::getData('order', 1, ['id' => $order_id, 'status' => 8, 'cou_status' => 2, 'is_del' => 1], 'mid');
                if (!$order_data) {
                    return jsonResponse('1010', '订单有误');
                } elseif ($order_data['mid'] != $user_data['mem_id']) {
                    return jsonResponse('1011', '订单与机构信息不匹配');
                }
                //更改订单状态
                $order_update = Crud::setUpdate('order', ['id' => $order_id, 'is_del' => 1], ['see_type' => 1]);
                if (!$order_update) {
                    return jsonResponse('1012', '订单修改状态失败');
                } else {
                    return jsonResponseSuccess($order_update);
                }
            } elseif ($member_data['give_type'] == 2) {
                if ($member_data['balance'] < 0) {
                    return jsonResponse('1006', '机构名额不足，请充值名额');
                }
                //查询机构是否是会员 1是会员，2不是会员
                $member = Crud::getData('member', 1, ['uid' => $user_data['mem_id'], 'is_del' => 1, 'status' => 1], 'ismember');
                if (!$member) {
                    return jsonResponse('1007', '机构信息有误');
                }
                if ($member['ismember'] == 1) {
                    $Dec_price = $user_price['price'] * $discount['discount'];
                } elseif ($member['ismember'] == 2) {
                    $Dec_price = $user_price['price'];
                }
                //判读余额是否大于名额金额
                //查询会员余额是否满足
                $price = $member_data['balance'] - $Dec_price;
                if ($price < 0) {
                    return jsonResponse('1008', '机构名额不足，请充值名额');
                }
                //减会员余额
                $member_data = Crud::setDecs('member', ['uid' => $user_data['mem_id'], 'is_del' => 1, 'status' => 1], 'balance', $Dec_price);
                if (!$member_data) {
                    return jsonResponse('1009', '购买名称失败');
                }
                //验证订单
                $order_data = Crud::getData('order', 1, ['id' => $order_id, 'status' => 8, 'cou_status' => 2, 'is_del' => 1], 'mid');
                if (!$order_data) {
                    return jsonResponse('1010', '订单有误');
                } elseif ($order_data['mid'] != $user_data['mem_id']) {
                    return jsonResponse('1011', '订单与机构信息不匹配');
                }
                //更改订单状态
                $order_update = Crud::setUpdate('order', ['id' => $order_id, 'is_del' => 1], ['see_type' => 1]);
                if (!$order_update) {
                    return jsonResponse('1012', '订单修改状态失败');
                } else {
                    return jsonResponseSuccess($order_update);
                }
            }

        }
    }

    //机构端推送消息
    public static function getjgPushcourseNotice()
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $where = [
                'is_del' => 1,
                'mid' => $user_data['mem_id'],
                'notice_type' => 2, //1已通知，2未通知
                'status' => 2, //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];
            $table = request()->controller();
            $cname_data = Crud::getData($table, 2, $where, 'name,id', '', 1, 100000);
            if ($cname_data) {
                foreach ($cname_data as $k => $v) {
                    $cname_data[$k]['notice_name'] = '有用户报名';
                    $cname_data[$k]['notice_type'] = 1;
                    self::setjgPushcourseNotice($v['id']); //提示完更改状态
                }
                return jsonResponseSuccess($cname_data);
            } else {
                throw new NothingMissException();
            }
        }
    }

    //修改机构端推送消息状态
    public static function setjgPushcourseNotice($id)
    {
        $where = [
            'id' => $id,
        ];
        $table = request()->controller();
        $cname_data = Crud::setUpdate($table, $where, ['notice_type' => 1]);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取学生订单详情
    public static function getjgUserOrderdetails($user_id, $page = 1)
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 2) { //1用户，2机构
            $where = [
                'o.is_del' => 1,
                'o.mid' => $user_data['mem_id'],
                'o.uid' => $user_id,
                'o.status' => ['in', [2, 5, 6, 8]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];
        }
        $join = [
            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
            ['yx_classroom cl', 'o.classroom_id =cl.id ', 'left'],  //教室信息
//            ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
//            ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //用户信息
        ];
        $alias = 'o';
        $table = 'order';
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'o.name,o.status,o.c_num,o.already_num,o.price,c.teacher_name,o.cou_status,o.cid', $page);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) {
                $course_data = self::getCourseDetails($v);
                $cname_data[$k]['address'] = $course_data['province'] . $course_data['city'] . $course_data['area'] . $course_data['address'];
                $cname_data[$k]['clname'] = $course_data['clname'];//教室名称
                if ($v['cou_status'] == 2) {
                    if ($v['see_type'] == 2 && $v['status'] == 8) {  //1可看，2不可看
                        $cname_data[$k]['sname'] = '*****';
                        $cname_data[$k]['sex'] = '**';
                        $cname_data[$k]['age'] = '**';
                        $cname_data[$k]['phone'] = '**';
                    }
                }
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $cname_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

}