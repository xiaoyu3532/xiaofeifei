<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/25 0025
 * Time: 15:57
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class ZhtActivity extends BaseController
{
    //获取活动课程
    //$activity_course_category 1线上课程，2线下课程，3其他
    public static function getActivityCourse($activity_course_category = 2)
    {
        $data = input();
//        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $data['mem_id'] = ['in', $mem_ids];
        }
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $data['mem_id'],
                ];
                $children = Crud::getData('category_small', $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    // 1线上课程，2线下课程，3其他
                    $info[$k]['children'] = $children;
                    if ($activity_course_category == 2) {
                        foreach ($children as $kk => $vv) {
                            $where = [
                                'is_del' => 1,
                                'mem_id' => $data['mem_id'],
                                'category_small_id' => $vv['value'],
                                'activity_type' => 2, //1,活动壳课程，2普通课程
                                'course_type' => 3, //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                            ];
                            $curriculum_info = Crud::getData('zht_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                            if ($curriculum_info) {
                                $info[$k]['children'][$kk]['children'] = $curriculum_info;
                                foreach ($curriculum_info as $kkk => $vvv) {
                                    $where = [
                                        'mem_id' => $data['mem_id'],
                                        'course_id' => $vvv['value'],
                                        'is_del' => 1,
                                    ];
                                    $course_num_info = Crud::getData('zht_course_num', $type = 2, $where, $field = 'id value,course_section_num label', $order = '', $page = '1', $pageSize = '1000');
                                    if ($course_num_info) {
                                        $info[$k]['children'][$kk]['children'][$kkk]['children'] = $course_num_info;
                                    } else {
                                        $info[$k]['children'][$kk]['children'][$kkk]['children'] = [];
                                    }
                                }

                            } else {
                                $info[$k]['children'][$kk]['children'] = [];
                            }

                        }
                    } elseif ($activity_course_category == 1) {
                        foreach ($children as $kk => $vv) {
                            $where = [
                                'is_del' => 1,
                                'mem_id' => $data['mem_id'],
                                'category_small_id' => $vv['value'],
                                'activity_type' => 2, //1,活动壳课程，2普通课程
                            ];
                            $curriculum_info = Crud::getData('zht_online_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                            if ($curriculum_info) {
                                $info[$k]['children'][$kk]['children'] = $curriculum_info;
                            } else {
                                $info[$k]['children'][$kk]['children'] = [];
                            }

                        }
                    }


                } else {
                    $info[$k]['children'] = [];
                }

            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }


    }

    //获取活动选添字段  yx_zht_activity_field
    public static function getActivityField()
    {
        $activity_field = Crud::getData('zht_activity_field', 2, ['is_del' => 1], 'id value,name label');
        if ($activity_field) {
            return jsonResponseSuccess($activity_field);
        } else {
            throw new NothingMissException();
        }
    }

    //添加活动

    /**传值
     * activity_title  活动标题
     * activity_time_array 活动开始结束时间
     * activity_rule 活动规则
     * activity_img 活动封面图
     * activity_rotation_chart 活动轮播图
     * activity_details 活动详情
     * activity_num 活动参于数量
     * activity_price 活动价格
     * activity_limit  活动人个是否限量 0为不限量
     * activity_iscourse 1有课程活动，2无课程活动
     * course_id 课程ID
     * activity_isdistribution 1,开启分销，2禁用分销
     * activity_distribution  佣金比例
     * activity_isfictitious_user 1展示虚拟人员，2隐藏
     * activity_fictitious_user_num 初始虚拟人员数量
     * activity_fictitious_user_time 虚拟人员每几小时递增
     * activity_fictitious_user_time_num  每小时递增人员数
     * activity_ismusic  1开启音乐，2禁用
     * activity_music 背景音乐
     * activity_ladder_array 活动阶梯数组
     * activity_field_ids 选择字段ID
     * activity_course_category 1线上课程，2线下课程，3其他
     */
    public static function addZhtActivity()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if ($data['activity_iscourse'] == true) {
            //$activity_course_category 1线上课程，2线下课程，3其他
            if ($data['activity_course_category'] == 1) {
                $course_array = self::copyActivityOnlineCourse($data);
            } elseif ($data['activity_course_category'] == 2) {
                //复制课程
                $course_array = self::copyActivityCourse($data);
                $data['course_num_id'] = $course_array['course_num_id'];//课包ID
            }
            $data['course_ids'] = serialize($data['course_id']);
            $data['course_id'] = $course_array['course_id'];//课程ID
            $data['activity_iscourse'] = 1;
        } else {
            $data['activity_iscourse'] = 2;
        }
        if ($data['activity_isdistribution'] == true) {
            $data['activity_isdistribution'] = 1;
        } else {
            $data['activity_isdistribution'] = 2;
        }
        if ($data['activity_isfictitious_user'] == true) {
            $data['activity_isfictitious_user'] = 1;
        } else {
            $data['activity_isfictitious_user'] = 2;
        }
        if ($data['activity_ismusic'] == true) {
            $data['activity_ismusic'] = 1;
        } else {
            $data['activity_ismusic'] = 2;
        }

        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $data['activity_start_time'] = $data['activity_time_array'][0] / 1000;
        $data['activity_end_time'] = $data['activity_time_array'][1] / 1000;
        if ($data['activity_start_time'] > time()) {
            $data['status'] = 1;
        } elseif ($data['activity_start_time'] < time() && $data['activity_end_time'] > time()) {
            $data['status'] = 2;
        } elseif ($data['activity_end_time'] < time()) {
            $data['status'] = 3;
        }
        $data['activity_field_ids'] = serialize($data['activity_field_ids']);
        $activity_ladder_array = $data['activity_ladder_array'];
        $data['activity_ladder_array'] = serialize($data['activity_ladder_array']);

        if (isset($data['activity_rotation_chart']) && !empty($data['activity_rotation_chart'])) { //轮播图
            $data['activity_rotation_chart'] = handle_img_deposit($data['activity_rotation_chart']);
        }

        if (isset($data['activity_fictitious_user_num']) && !empty($data['activity_fictitious_user_num'])) {
            $data['activity_enroll_num'] = $data['activity_fictitious_user_num'];
        }

        $data['activity_type'] = 1; //1接龙工具，2砍价工具
        $activity_id = Crud::setAdd('zht_activity', $data, 2);
        if ($activity_id) {
            foreach ($activity_ladder_array as $k => $v) {
                $add_ladder = [
                    'activity_id' => $activity_id,
                    'ladder_num' => $v['ladder_num'],
                    'ladder_price' => $v['ladder_price'],
                ];
                $activity_ladder_data = Crud::setAdd('zht_ladder_price', $add_ladder);
                if (!$activity_ladder_data) {
                    throw new AddMissException();
                }
            }
            return jsonResponseSuccess($activity_ladder_data);
        } else {
            throw new NothingMissException();
        }


    }

    //复制活动课程
    public static function copyActivityCourse($data)
    {
        $where_course = [
            'id' => $data['course_id'][2],
            'is_del' => 1
        ];
        //获取课程详情
        $course_data = Crud::getData('zht_course', 1, $where_course, '*');
        if (!$course_data) {
            throw new NothingMissException();
        }
        $course_data['activity_type'] = 1;
        unset($course_data['id']);
        $new_course_id = Crud::setAdd('zht_course', $course_data, 2);
        //获取课包
        $where_course_num = [
            'id' => $data['course_id'][3],
            'is_del' => 1
        ];
        $course_num_data = Crud::getData('zht_course_num', 1, $where_course_num, '*');
        if (!$course_num_data) {
            throw new NothingMissException();
        }
        unset($course_num_data['id']);
        $course_num_data['mem_id'] = $data['mem_id'];
        $course_num_data['course_id'] = $new_course_id;
        $add_course_num = [
            'mem_id' => $data['mem_id'],
            'course_id' => $new_course_id,
            'course_section_num' => $course_num_data['course_section_num'],
        ];
        $course_num_id = Crud::setAdd('zht_course_num', $add_course_num, 2);
        $course_array = [
            'course_id' => $new_course_id,
            'course_num_id' => $course_num_id,
        ];
        return $course_array;
    }

    //复制线上课程 yx_zht_online_course
    public static function copyActivityOnlineCourse($data)
    {
        $where_course = [
            'id' => $data['course_id'][2],
            'is_del' => 1
        ];
        //获取课程详情
        $course_data = Crud::getData('zht_online_course', 1, $where_course, '*');
        if (!$course_data) {
            throw new NothingMissException();
        }
        $course_data['activity_type'] = 1;
        unset($course_data['id']);
        $new_course_id = Crud::setAdd('zht_online_course', $course_data, 2);
        $course_array = [
            'course_id' => $new_course_id
        ];
        return $course_array;
    }

    //获取活动列表

    /**
     * activity_title 活动名称
     * mem_id 机构ID
     * activity_start_time 开始时间
     * activity_end_time结束时间
     */
    public static function getActivityList($page = 1, $pageSize = 8)
    {
        $data = input();
//        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $mem_ids = bindingMember::getbindingjgMemberId();
            $data['mem_id'] = ['in', $mem_ids];
        }
        $where_activity = [
//            'za.id' => $data['activity_id'],
            'za.mem_id' => $data['mem_id'],
            'za.is_del' => 1
        ];
        //类型查询  // 1 未开始  2进行中 3已结束
        if (isset($data['activity_type']) && !empty($data['activity_type'])) {
            if ($data['activity_type'] == 1) {
//                $where_activity['za.activity_start_time'] = ['>', time()];
                $where_activity['za.status'] = 1;
            } elseif ($data['activity_type'] == 2) {
//                $where_activity['za.activity_start_time'] = ['<', time()];
//                $where_activity['za.activity_end_time'] = ['>', time()];
                $where_activity['za.status'] = 2;
            } elseif ($data['activity_type'] == 3) {
                $where_activity['za.status'] = 3;
//                $where_activity['za.activity_end_time'] = ['<', time()];
            }
        }

        //活动标题
        (isset($data['activity_title']) && !empty($data['activity_title'])) && $where_activity['za.activity_title'] = ['like', '%' . $data['activity_title'] . '%'];
        //机构 ID
        (isset($data['mem_id']) && !empty($data['mem_id'])) && $where_activity['za.mem_id'] = $data['mem_id'];
        if (isset($data['activity_start_time ']) && !empty($data['activity_start_time '])) {
            $where_activity['activity_start_time'] = ['<=', $data['activity_start_time ']];
        }
        if (isset($data['activity_end_time ']) && !empty($data['activity_end_time '])) {
            $where_activity['activity_end_time'] = ['>=', $data['activity_end_time ']];
        }
        $join = [
            ['yx_member m', 'za.mem_id = m.uid', 'left'], //机构表
            ['yx_zht_course zc', 'za.course_id = zc.id', 'left'], //课程
        ];
        $alias = 'za';
        $info = Crud::getRelationData('zht_activity', $type = 2, $where_activity, $join, $alias, $order = 'za.id desc', $field = 'm.cname,za.*,zc.course_name', $page, $pageSize);
        if ($info) {
            foreach ($info as $k => $v) {
                if ($v['activity_start_time'] > time()) {
                    $info[$k]['activity_type'] = '未开始';
                } elseif ($v['activity_start_time'] < time() && $v['activity_end_time'] > time()) {
                    $info[$k]['activity_type'] = '进行中';
                } elseif ($v['activity_end_time'] < time()) {
                    $info[$k]['activity_type'] = '结束';
                }

                if (!empty($v['activity_rotation_chart'])) {
                    $info[$k]['activity_rotation_chart'] = unserialize($v['activity_rotation_chart']);
                }
                if (!empty($v['activity_ladder_array'])) {
                    $info[$k]['activity_ladder_array'] = unserialize($v['activity_ladder_array']);
                }
                if (!empty($v['activity_field_ids'])) {
                    $info[$k]['activity_field_ids'] = unserialize($v['activity_field_ids']);
                }

                if (isset($v['activity_rotation_chart']) && !empty($v['activity_rotation_chart'])) { //轮播图
                    $info[$k]['activity_rotation_chart'] = handle_img_take($v['activity_rotation_chart']);
                } else {
                    $info[$k]['activity_rotation_chart'] = [];
                }

                if ($v['activity_iscourse'] == 1) {
                    $info[$k]['activity_iscourse'] = true;
                } else {
                    $info[$k]['activity_iscourse'] = false;
                }
                if ($v['activity_isdistribution'] == 1) {
                    $info[$k]['activity_isdistribution'] = true;
                } else {
                    $info[$k]['activity_isdistribution'] = false;
                }
                if ($v['activity_isfictitious_user'] == 1) {
                    $info[$k]['activity_isfictitious_user'] = true;
                } else {
                    $info[$k]['activity_isfictitious_user'] = false;
                }
                if ($v['activity_ismusic'] == 1) {
                    $info[$k]['activity_ismusic'] = true;
                } else {
                    $info[$k]['activity_ismusic'] = false;
                }
                $info[$k]['course_id'] = unserialize($v['course_ids']);
                $info[$k]['fileList'] = [
                    'name' => '背景音乐',
                    'url' => $v['activity_music'],
                ];
                $info[$k]['activity_time_array'] = [$v['activity_start_time'] * 1000, $v['activity_end_time'] * 1000];
                $info[$k]['activity_time'] = date('Y-m-d H:i:s', $v['activity_start_time']) . '至' . date('Y-m-d H:i:s', $v['activity_end_time']);
                //求报名人数
                $were_activity = [
                    'activity_id' => $v['id'],
                    'status' => ['in', [2, 5, 8, 11, 12, 13]],//1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款，11待排课，12上课中，13已毕业，14已休学
                    'is_del' => 1
                ];
                //获取活动订单
                $activity_order_num = Crud::getCount('zht_activity_order', $were_activity);
                $info[$k]['activity_order_num'] = $activity_order_num;
                //求活动阶段价格 yx_zht_ladder_price
                $where_ladder = [
                    'activity_id' => $v['id'],
                    'is_del' => 1
                ];
                //求取本活动阶段
                $ladder_data = Crud::getData('zht_ladder_price', 2, $where_ladder, '*', 'ladder_num', 1, 10000);
                if (!$ladder_data) {
                    throw new NothingMissException();
                }
                $info[$k]['ladder_array'] = $ladder_data;
                $new_ladder_data = $ladder_data;
                unset($new_ladder_data[0]);
                foreach ($ladder_data as $kk => $vv) {
                    foreach ($new_ladder_data as $kkk => $vvv) {
                        if ($vv['ladder_num'] <= $activity_order_num && $vvv['ladder_num'] >= $activity_order_num) {
                            $info[$k]['ladder_now_num'] = $kk + 1;
                            $info[$k]['ladder_now_price'] = $vv['ladder_price'];
                            break;
                        }
                        if ($ladder_data[0]['ladder_num'] > $activity_order_num) {
                            $info[$k]['ladder_now_num'] = 0;
                            $info[$k]['ladder_now_price'] = $v['activity_price'];
                            break;
                        }
                    }
                }
            }
            $num = Crud::getCountSelNun('zht_activity', $where_activity, $join, $alias, $field = 'za.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new  NothingMissException();
        }

    }

    //获取活动列表字段
    public static function getActivityListField()
    {
        $data = [
            ['prop' => 'activity_img', 'name' => '活动信息', 'width' => 380, 'state' => ''],
            ['prop' => 'cname', 'name' => '发布机构', 'width' => '', 'state' => ''],
            ['prop' => 'activity_time', 'name' => '活动时间', 'width' => '', 'state' => ''],
            ['prop' => 'activity_order_num', 'name' => '参团总人数', 'width' => '', 'state' => '1'],
            ['prop' => 'ladder_now_num', 'name' => '当前阶梯', 'width' => '', 'state' => ''],
            ['prop' => 'activity_price', 'name' => '商品原价', 'width' => '', 'state' => ''],
            ['prop' => 'activity_price', 'name' => '最新团价', 'width' => '', 'state' => ''],

        ];
        return jsonResponseSuccess($data);
    }

    //修改活动
    public static function editZhtActivity()
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        if ($data['activity_iscourse'] == true) {
            $data['course_ids'] = serialize($data['course_id']);
            //验证是否修改活动课程
            $is_activity_course = self::isActivityCourse($data); //1 为未修改,2为修改,其他为数据错误
            if ($is_activity_course['course_id'] == 2) {
                //activity_course_category 1线上课程，2线下课程，3其他
                if ($data['activity_course_category'] == 1) {
                    //删除之前的活动线上课程
                    self::eidtcopyActivityOnlineCourse($is_activity_course['course_id']);
                    //复制线上课程
                    $course_array = self::copyActivityOnlineCourse($data);
                } elseif ($data['activity_course_category'] == 2) {
                    //删除这前的活动课程
                    self::eidtcopyActivityCourse($is_activity_course['course_id']);
                    //复制课程
                    $course_array = self::copyActivityCourse($data);
                    $data['course_num_id'] = $course_array['course_num_id'];//课包ID
                }
                $data['course_id'] = $course_array['course_id'];//课程ID

            } elseif (!is_array($is_activity_course)) {
                return $is_activity_course;
            }
            $data['activity_iscourse'] = 1;
        } else {
            $data['activity_iscourse'] = 2;
        }


        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }

        if ($data['activity_isdistribution'] == true) {
            $data['activity_isdistribution'] = 1;
        } else {
            $data['activity_isdistribution'] = 2;
        }
        if ($data['activity_isfictitious_user'] == true) {
            $data['activity_isfictitious_user'] = 1;
        } else {
            $data['activity_isfictitious_user'] = 2;
        }
        if ($data['activity_ismusic'] == true) {
            $data['activity_ismusic'] = 1;
        } else {
            $data['activity_ismusic'] = 2;
        }
        $data['activity_start_time'] = $data['activity_time_array'][0] / 1000;
        $data['activity_end_time'] = $data['activity_time_array'][1] / 1000;
        if ($data['activity_start_time'] > time()) {
            $data['status'] = 1;
        } elseif ($data['activity_start_time'] < time() && $data['activity_end_time'] > time()) {
            $data['status'] = 2;
        } elseif ($data['activity_end_time'] < time()) {
            $data['status'] = 3;
        }
        $data['activity_field_ids'] = serialize($data['activity_field_ids']);
        $activity_ladder_array = $data['activity_ladder_array'];
        $data['activity_ladder_array'] = serialize($data['activity_ladder_array']);

        if (isset($data['activity_rotation_chart']) && !empty($data['activity_rotation_chart'])) { //轮播图
            $data['activity_rotation_chart'] = handle_img_deposit($data['activity_rotation_chart']);
        }
        $id = $data['id'];
        unset($data['id']);
        $data['update_time'] = time();
        $data['activity_type'] = 1;
        if (isset($data['activity_fictitious_user_num']) && !empty($data['activity_fictitious_user_num'])) {
            $activity_data = Crud::getData('zht_activity', 1, ['id' => $id, 'is_del' => 1], 'activity_enroll_num,activity_fictitious_user_num');
            if (!$activity_data) {
                $activity_enroll_num = 0;
            } else {
                $activity_enroll_num = $activity_data['activity_enroll_num'] + $data['activity_fictitious_user_num'] - $activity_data['activity_fictitious_user_num'];
            }
            $data['activity_enroll_num'] = $activity_enroll_num;
        }


        $activity_data = Crud::setUpdate('zht_activity', ['id' => $id], $data);
        if ($activity_data) {
            //删除阶梯信息
            $activity_ladder_data = Crud::setUpdate('zht_ladder_price', ['activity_id' => $id], ['is_del' => 2]);
            if (!$activity_ladder_data) {
                throw new DelMissException();
            }
            foreach ($activity_ladder_array as $k => $v) {
                $add_ladder = [
                    'activity_id' => $id,
                    'ladder_num' => $v['ladder_num'],
                    'ladder_price' => $v['ladder_price'],
                ];
                $activity_ladder_data = Crud::setAdd('zht_ladder_price', $add_ladder);
                if (!$activity_ladder_data) {
                    throw new AddMissException();
                }
            }
            return jsonResponseSuccess($activity_ladder_data);
        } else {
            throw new NothingMissException();
        }


    }

    //修改复制课程(删除复用课程)
    public static function eidtcopyActivityCourse($course_id)
    {
        $where_course = [
            'id' => $course_id,
            'is_del' => 1
        ];
        //获取课程详情
        $course_data = Crud::getData('zht_course', 1, $where_course, '*');
        if (!$course_data) {
            throw new NothingMissException();
        } else {
            $del_course = Crud::setUpdate('zht_course', ['id' => $course_data['id']], ['is_del' => 2]);
            if (!$del_course) {
                throw new DelMissException();
            }
            $del_course_num = Crud::setUpdate('zht_course_num', ['course_id' => $course_data['id']], ['is_del' => 2]);
            if (!$del_course_num) {
                throw new DelMissException();
            }
            return $del_course_num;
        }


    }

    //修改复制线上课程(删除复用线上课程)
    public static function eidtcopyActivityOnlineCourse($course_id)
    {
        $where_course = [
            'id' => $course_id,
            'is_del' => 1
        ];
        //获取课程详情
        $course_data = Crud::getData('zht_online_course', 1, $where_course, '*');
        if (!$course_data) {
            throw new NothingMissException();
        } else {
            $del_course = Crud::setUpdate('zht_online_course', ['id' => $course_data['id']], ['is_del' => 2]);
            if (!$del_course) {
                throw new DelMissException();
            }
            return $del_course;
        }


    }

    //查看是否更换此课程
    public static function isActivityCourse($data)
    {
        //获取现活动绑定的活动ID
        $activity_course_data = Crud::getData('zht_activity', 1, ['id' => $data['id']], 'course_id');
        if (!$activity_course_data) {
            throw new  NothingMissException();
        }
        if ($data['course_id'][2] == $activity_course_data['course_id']) {
            $info = [
                'code' => 1,
            ];
            return $info;
        } else {
            $info = [
                'code' => 1,
                'course_id' => $activity_course_data['course_id']
            ];
            return $info;
        }

    }

    //获取活动订单 yx_zht_activity_order
    public static function getActivityOrder($page = 1, $pageSize = 8)
    {
        $data = input();
        $account_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $where = [
            'zao.mem_id' => $data['mem_id'],
            'zao.activity_id' => $data['activity_id'],
            'zao.is_del' => 1
        ];
        isset($data['student_name']) && !empty($data['student_name']) && $where['ls.student_name'] = $data['student_name'];
        $join = [
            ['yx_lmport_student ls', 'zao.student_id = ls.id', 'left'], //学生表
        ];
        $alias = 'zao';
        $info = Crud::getRelationData('zht_activity_order', $type = 2, $where, $join, $alias, $order = 'zao.id desc', $field = 'zao.*,ls.student_name', $page, $pageSize);
        if ($info) {  //create_time_Exhibition
            foreach ($info as $k => $v) {
                $info[$k]['create_time_Exhibition'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $num = Crud::getCountSelNun('zht_activity_order', $where, $join, $alias, $field = 'zao.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);

        } else {
            throw new NothingMissException();
        }
    }

    //获取活动订单字段
    public static function getActivityOrderField($activity_id = '')
    {

        $activity_data = Crud::getData('zht_activity', 1, ['id' => $activity_id, 'is_del' => 1], 'activity_field_ids');
        if ($activity_data) {
            $activity_field_ids = unserialize($activity_data['activity_field_ids']);
            $activity_field_ids = Many_One($activity_field_ids);
            $activity_field = Crud::getData('zht_activity_field', 2, ['id' => ['in', $activity_field_ids], 'is_del' => 1], 'field');
        }
        $data = [
            ['prop' => 'student_name', 'name' => '学生名称', 'width' => '', 'state' => ''],
        ];
        foreach ($activity_field as $k => $v) {

            if ($v['field'] == 'phone') {
                $data[] = ['prop' => 'phone', 'name' => '学生名称', 'width' => '', 'state' => ''];
            }
            if ($v['field'] == 'address') {
                $data[] = ['prop' => 'address', 'name' => '联系电话', 'width' => '', 'state' => ''];
            }
            if ($v['field'] == 'school') {
                $data[] = ['prop' => 'school', 'name' => '学校', 'width' => '', 'state' => ''];
            }
            if ($v['field'] == 'class') {
                $data[] = ['prop' => 'class', 'name' => '班级', 'width' => '', 'state' => ''];
            }
            if ($v['field'] == 'height') {
                $data[] = ['prop' => 'height', 'name' => '身高', 'width' => '', 'state' => ''];
            }
            if ($v['field'] == 'wechat') {
                $data[] = ['prop' => 'wechat', 'name' => '微信', 'width' => '', 'state' => ''];
            }
        }
        $data[] = ['prop' => 'create_time_Exhibition', 'name' => '创建时间', 'width' => '', 'state' => ''];
        return jsonResponseSuccess($data);
    }

    //活动删除
    public static function delActivity($activity_id = '')
    {
        //查询出来要删除的活动
        $course_id_data = Crud::getData('zht_activity', 2, ['id' => ['in', $activity_id], 'is_del' => 1], 'course_id,activity_field_ids');
        if (!$course_id_data) {
            throw new DelMissException();
        }

        $course_ids = Many_One($course_id_data);
        //删除活动课程
        $del_course = Crud::setUpdate('zht_course', ['id' => ['in', $course_ids], 'is_del' => 1], ['is_del' => 2]);
        if (!$del_course) {
            throw new DelMissException();
        }

        //删除活动
        $course_id_data = Crud::setUpdate('zht_activity', ['id' => ['in', $activity_id], 'is_del' => 1], ['is_del' => 2]);
        if (!$course_id_data) {
            throw new DelMissException();
        }
        return jsonResponseSuccess($course_id_data);
    }


}