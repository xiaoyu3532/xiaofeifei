<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/11 0011
 * Time: 15:38
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\AddMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\common\controller\IsTime;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class Course extends BaseController
{
    //获取课程列表  course_name课程名称课程名称  category_id 一级学习类目 category_small_id二级学习类目 start_age开始年龄 end_age结束年龄区间 title课程简介
    //teacher_id老师ID surplus_num课程总人数 unit_price课程单价       discount折扣  discount_start_time 折扣开始时间  discount_end_time 折扣结束时间
    //course_img 封面图 details 详情 start_time课程开始有效时间  end_time课程有效时间结束 mid,后期将用mem_id c_num 课程数量
    public static function getjgCourse($page = '1', $pageSize = 9, $course_name = '', $category_small_ids = '', $mem_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'c.is_del' => 1,
//                'ca.type' => 1,
//                'ca.is_del' => 1,
                'm.status' => 1,
                'm.is_del' => 1,
                'c.activity_type' => 2,
            ];
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $where['c.mem_id'] = ['in', $mem_ids];
            } else {
                $where['c.mem_id'] = $mem_id;
            }
        } else {
            throw new ISUserMissException();
        }

        //名称搜索
        (isset($course_name) && !empty($course_name)) && $where['c.course_name'] = ['like', '%' . $course_name . '%'];
        //机构 ID
        (isset($mem_id) && !empty($mem_id)) && $where['c.mem_id'] = $mem_id;
        //学科二级分类
        if (isset($category_small_ids) && !empty($category_small_ids)) {
            $where['c.category_id'] = $category_small_ids[0];
            $where['c.category_small_id'] = $category_small_ids[1];
        }
        //上下架状态
        (isset($type) && !empty($type)) && $where['c.type'] = $type;
//        $table = request()->controller();
        $table = 'zht_course';
        $join = [
            ['yx_zht_category ca', 'c.category_id = ca.id', 'left'], //大分类
            ['yx_category_small cs', 'c.category_small_id = cs.id', 'left'], //小分类
            ['yx_member m', 'c.mem_id = m.uid', 'left'], //机构
        ];
        $alias = 'c';
        $info = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'c.id desc', $field = 'm.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,m.phone member_phone,c.*,ca.name category_name,cs.category_small_name', $page, $pageSize);
        if (!$info) {
            throw new NothingMissException();
        } else {
            $time_datas = time();
            foreach ($info as $k => $v) {
                $info[$k]['category'] = $v['category_name'] . '-' . $v['category_small_name'];
                $info[$k]['age_section'] = $v['start_age'] . '-' . $v['end_age'];
                $info[$k]['course_section_time'] = conversion_time_year($v['course_start_time']) . '-' . conversion_time_year($v['course_end_time']);
                $course_section_package = Crud::getData('zht_course_num', 2, ['course_id' => $v['id'], 'is_del' => 1], 'course_section_num,course_section_price,surplus_num');
                if ($course_section_package) {
                    $info[$k]['course_section_package'] = $course_section_package;
                } else {
                    $info[$k]['course_section_package'] = [];
                }
                $info[$k]['category_ids'] = [[$v['category_id']], [$v['category_small_id']]];
                if (!empty($v['course_start_time']) && !empty($v['course_end_time'])) {
                    $info[$k]['course_time'] = [($v['course_start_time'] * 1000), ($v['course_end_time'] * 1000)];
                }
                if (!empty($v['discount_start_time']) && !empty($v['discount_end_time'])) {
                    $info[$k]['discount_time'] = [($v['discount_start_time'] * 1000), ($v['discount_end_time'] * 1000)];
                }
                $info[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
                $info[$k]['category_ids'] = [$v['category_id'], $v['category_small_id']];
                if ($v['course_type'] == 1) {  //1体验课程，2普通课程 ，3活动课程,4试听课，5赠送课
                    $info[$k]['course_type_name'] = '体验课程';
                } elseif ($v['course_type'] == 2) {
                    $info[$k]['course_type_name'] = '普通课程';
                } elseif ($v['course_type'] == 3) {
                    $info[$k]['course_type_name'] = '活动课程';
                } elseif ($v['course_type'] == 4) {
                    $info[$k]['course_type_name'] = '试听课';
                } elseif ($v['course_type'] == 5) {
                    $info[$k]['course_type_name'] = '赠送课';
                }
                $info[$k]['course_type'] = (string)$v['course_type'];
                if (isset($v['course_wheel_img']) && !empty($v['course_wheel_img'])) { //轮播图
                    $info[$k]['course_wheel_img'] = handle_img_take($v['course_wheel_img']);
                } else {
                    $info[$k]['course_wheel_img'] = [];
                }
                $section_packag = Crud::getData('zht_course_num', 2, ['course_id' => $v['id'], 'is_del' => 1], 'course_section_price', 'course_section_price asp');
                if (isset($section_packag) && !empty($section_packag)) {
                    if ($section_packag[0]['course_section_price'] == 0) {
                        $info[$k]['course_section_price'] = '免费'; //列表只获取第一个价格
                        $info[$k]['course_discount_price'] = '免费';
                    } else {
                        $info[$k]['course_section_price'] = $section_packag[0]['course_section_price']; //列表只获取第一个价格
                        if (($v['discount_start_time'] < $time_datas) && $v['discount_end_time'] > $time_datas) {
                            $course_discount_price = $section_packag[0]['course_section_price'] * $v['discount'] * 0.1;
                            $info[$k]['course_discount_price'] = round($course_discount_price, 2);
                        } else {
                            $info[$k]['course_discount_price'] = $section_packag[0]['course_section_price'];
                        }
                    }
                } else {
                    $info[$k]['course_section_price'] = '免费'; //列表只获取第一个价格
                    $info[$k]['course_discount_price'] = '免费';
                }
            }
            $num = Crud::getCountSelNun($table, $where, $join, $alias, $field = 'c.id');
            $info_data = [
                'info' => $info,
                'num' => $num,
                'pageSize' => (int)$pageSize,
            ];
            return jsonResponseSuccess($info_data);
        }
    }


    //添加课程 course_wheel_img 轮播图 course_type课程类型 （1体验课程，2普通课程 ，3活动课程）  course_name课程名称  category_id 一级学习类目 category_small_id 二级学习类目 start_age开始年龄 end_age结束年龄区间 title课程简介
    //teacher_id老师ID surplus_num课程总人数 unit_price课程单价       discount折扣  discount_start_time 折扣开始时间  discount_end_time 折扣结束时间
    //course_img 封面图 course_details 详情 start_time课程开始有效时间  end_time课程有效时间结束 mid,后期将用mem_id course_num_id 课程数量ID
    public static function addjgCourse()
    {
        $mem_data = self::isuserData();
        $data = input();
        $member_info = Crud::getData('member', 1, ['uid' => $mem_data['mem_id'], 'is_del' => 1], 'longitude,latitude');
        if ($member_info) {
            $data['longitude'] = $member_info['longitude'];
            $data['latitude'] = $member_info['latitude'];
        }
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $mem_data['mem_id'];
        }

        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }

        //discount
        if (!isset($data['discount']) || empty($data['discount']) || $data['discount'] == 0) {
            $data['discount'] = 10;
        }


        if (isset($data['course_time']) && !empty($data['course_time'])) {
            $data['course_start_time'] = $data['course_time'][0] / 1000;
            $data['course_end_time'] = $data['course_time'][1] / 1000;
        } else {
            $data['course_start_time'] = time();
            $data['course_end_time'] = time() + 31536000;
        }

        if (isset($data['discount_time']) && !empty($data['discount_time'])) {
            $data['discount_start_time'] = $data['discount_time'][0] / 1000;
            $data['discount_end_time'] = $data['discount_time'][1] / 1000;
        } else {
            $data['discount_start_time'] = time();
            $data['discount_end_time'] = time() + 31536000;
        }
        if (isset($data['course_wheel_img']) && !empty($data['course_wheel_img'])) { //轮播图
            $data['course_wheel_img'] = handle_img_deposit($data['course_wheel_img']);
        }
        //获取最低价格前台展示价格
        $present_price = searchmax($data['course_section_package'], 'course_section_price');
        if ($present_price == 0) {//1正常 2有免费
            $data['price_type'] = 2;
        }
        $data['present_price'] = $present_price;
        $table = 'zht_course';
        $data['update_time'] = time();
        $course_id = Crud::setAdd($table, $data, 2);
        //机构添加分类
        if (!$course_id) {
            throw new AddMissException();
        }
        //添加课程包course_section_package
        if (isset($data['course_section_package']) && !empty($data['course_section_package'])) {
            $section_packag = self::addandupdate($data['course_section_package'], $data['mem_id'], $course_id);
            if ($section_packag != 1) {
                return $section_packag;
            }
        }
        if ($course_id) {
            //添加机构课程数量
            $course_num = Crud::setIncsMemberId($mem_data['mem_id']);
            return jsonResponseSuccess($course_id);
        }
    }

    //修改课程
    public static function setjgCourse()
    {
        $data = input();
        $mem_data = self::isuserData();
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $mem_data['mem_id'];
        }
        $where = [
            'id' => $data['course_id'],
        ];
        if (isset($data['category_ids']) && !empty($data['category_ids'])) {
            $data['category_id'] = $data['category_ids'][0];
            $data['category_small_id'] = $data['category_ids'][1];
        }
        if (!isset($data['discount']) || empty($data['discount']) || $data['discount'] == 0) {
            $data['discount'] = 10;
        }
        //discount

        if (isset($data['course_time']) && !empty($data['course_time'])) {
            $data['course_start_time'] = $data['course_time'][0] / 1000;
            $data['course_end_time'] = $data['course_time'][1] / 1000;
        }

        if (isset($data['discount_time']) && !empty($data['discount_time'])) {
            $data['discount_start_time'] = $data['discount_time'][0] / 1000;
            $data['discount_end_time'] = $data['discount_time'][1] / 1000;
        }
        if (isset($data['course_wheel_img']) && !empty($data['course_wheel_img'])) { //轮播图
            $data['course_wheel_img'] = handle_img_deposit($data['course_wheel_img']);
        }
        $data['update_time'] = time();
        $table = 'zht_course';
        $info = Crud::setUpdate($table, $where, $data);
        if (!$info) {
            throw new UpdateMissException();
        }
        //添加课程包course_section_package
        if (isset($data['course_section_package']) && !empty($data['course_section_package'])) {
            $section_packag = self::addandupdate($data['course_section_package'], $data['mem_id'], $data['course_id'], 2);
            if ($section_packag != 1) {
                return $section_packag;
            }
        }
        if ($info) {
            return jsonResponseSuccess($info);
        }

    }

    //删除课程
    public static function deljgCourse($course_id)
    {
        $where = [
            'id' => ['in', $course_id],
        ];
        $data = [
            'is_del' => 2,
            'update_time' => time()
        ];
        $table = 'zht_course';
        //删除课程
        $del_course = Crud::setUpdate($table, $where, $data);
        if (!$del_course) {
            throw new UpdateMissException();
        }
        //删除课程绑定 yx_zht_course_num
        $del_course_num = Crud::setUpdate('zht_course_num', ['course_id' => ['in', $course_id]], ['is_del' => 2, 'update_time' => time()]);
        if (!$del_course_num) {
            throw new UpdateMissException();
        }
        return jsonResponseSuccess($del_course_num);

    }

    //上下架操作
    public static function editjgCourseType($course_id, $type)
    {
        $where = [
            'id' => $course_id,
        ];
        if ($type == 1) {
            $type = 2;
        } elseif ($type == 2) {
            $type = 1;
        }
        $table = 'zht_course';
        $info = Crud::setUpdate($table, $where, ['type' => $type]);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //上下架操作(备用)
    public static function editjgCourseStatus($course_id, $course_status)
    {
        $where = [
            'id' => $course_id,
        ];
        $data = [
            'course_status' => $course_status
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }
    }

    //添加修改课程节包 2时为修改，删除以前的课程关联的课包
    public static function addandupdate($course_section_package, $mem_id, $course_id, $type = 1)
    {
        if ($type == 2) {
            $package_data = Crud::setUpdate('zht_course_num', ['course_id' => $course_id], ['is_del' => 2, 'update_time' => time()]);
//            if (!$package_data) {
//                throw new UpdateMissException();
//            }
        }
        foreach ($course_section_package as $k => $v) {
            $package_add = [
                'mem_id' => $mem_id,
                'course_id' => $course_id,
                'course_section_num' => $v['course_section_num'],
                'course_section_price' => $v['course_section_price'],
                'surplus_num' => $v['surplus_num'],
            ];
            $package_data = Crud::setAdd('zht_course_num', $package_add);
        }
        if ($package_data) {
            return 1;
        } else {
            throw new AddMissException();
        }
    }


    //组合课目分类及课程名称
    public static function getjgCourseTypesearch($mem_id = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            $mem_data = self::isuserData();
            if (!isset($mem_id) || empty($mem_id)) {
                $mem_id = $mem_data['mem_id'];
            }
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $mem_id,
                ];
                $children = Crud::getData('category_small', $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                    foreach ($children as $kk => $vv) {
                        $where = [
                            'is_del' => 1,
                            'type' => 1,
                            'mem_id' => $mem_id,
                            'category_small_id' => $vv['value']
                        ];
                        $curriculum_info = Crud::getData('zht_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                        if ($curriculum_info) {
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                        } else {
                            $info[$k]['children'][$kk]['children'] = [];
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

    //组合课目分类和课程名和课程包
    public static function getjgCourseTypesearchNum($mem_id = '')
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $info = Crud::getData('zht_category', $type = 2, $where, $field = 'id value,name label', $order = 'sort desc', $page = 1, $pageSize = '1000');
        if ($info) {
            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $mem_id = ['in', $mem_ids];
            }
            foreach ($info as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'type' => 1,
                    'category_id' => $v['value'],
                    'mem_id' => $mem_id,
                ];
                $children = Crud::getData('category_small', $type = 2, $where, $field = 'id value,category_small_name label', $order = 'sort desc', $page, $pageSize = '1000');
                if ($children) {
                    $info[$k]['children'] = $children;
                    foreach ($children as $kk => $vv) {
                        $where = [
                            'is_del' => 1,
                            'mem_id' => $mem_id,
//                            'activity_type' => 2,
                            'category_small_id' => $vv['value']
                        ];
                        $curriculum_info = Crud::getData('zht_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                        if ($curriculum_info) {
                            foreach ($curriculum_info as $ck => $cv) {
                                //获取活动信息
                                $activity_data = Crud::getData('zht_activity', 1, ['course_id' => $cv['value'], 'is_del' => 1], 'activity_title');
                                if ($activity_data) {
                                    $curriculum_info[$ck]['label'] = $cv['label'] . ' 【活动】 ' . $activity_data['activity_title'];
                                }
                            }
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                            foreach ($curriculum_info as $kkk => $vvv) {
                                $where = [
                                    'mem_id' => $mem_id,
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
                } else {
                    $info[$k]['children'] = [];
                }

            }
            return jsonResponseSuccess($info);
        } else {
            throw new  NothingMissException();
        }
    }

    //获取课时
    public static function getCourseNum($course_num_id)
    {
        $course_num = Crud::getData('zht_course_num', 1, ['id' => $course_num_id, 'is_del' => 1], 'course_section_price,course_section_num');
        if (!$course_num) {
            throw new AddMissException();
        } else {
            return $course_num;
        }
    }


}