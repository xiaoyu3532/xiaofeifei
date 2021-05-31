<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/21 0021
 * Time: 13:54
 */

namespace app\jg\controller\v2;


use app\common\model\Crud;
use app\jg\controller\v1\BaseController;
use app\lib\exception\NothingMissException;
use think\Db;

class ShortMessage extends BaseController
{
    //添加剩余课时通知
    public static function setSurplusNotice()
    {
        $account_data = self::isuserData();
        $data = input();
        if (empty($mem_id)) {
            $data['mem_id'] = $account_data['mem_id'];
        }
        $course_number = implode(',', $data['course_number_array']);
        $data['course_number'] = $course_number . ',' . $data['course_number'];
        foreach ($data['course_data'] as $k => $v) {
            //course_data: [[3,  212,     92,   20, 144]]
//                            0     1       2      3    4
//                          分类  小分类   课程    班级  课节名
            $data['course_id'] = $v[2];
            $data['arrange_course_id'] = $v[3];
            $short_message_data = Crud::setAdd('zht_short_message', $data);
        }
        if ($short_message_data) {
            return jsonResponseSuccess($short_message_data);
        } else {
            throw new NothingMissException();
        }
    }

    //删除剩余课时通知
    public static function delSurplusNotice($short_message_id)
    {
        $del_short_message = Crud::setUpdate('zht_short_message', ['id' => $short_message_id], ['is_del' => 2]);
        if ($del_short_message) {
            return jsonResponseSuccess($del_short_message);
        } else {
            throw new NothingMissException();
        }
    }

    //获取剩余课时列表字段
    public static function getSurplusNoticeListField()
    {
        $data = [
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '', 'state' => ''],
            ['prop' => 'course_name', 'name' => '通知课程名', 'width' => '', 'state' => ''],
            ['prop' => 'course_number', 'name' => '第几课时提示', 'width' => '', 'state' => ''],
        ];
        return jsonResponseSuccess($data);
    }

    //获取剩余课时列表
    public static function getSurplusNoticeList($mem_id = '', $page = 1, $pageSize = 8)
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }
        $where = [
            'zsm.mem_id' => $mem_id,
            'zsm.is_del' => 1
        ];

        $join = [
            ['yx_member m', 'zsm.mem_id =m.uid ', 'left'],  //机构信息
            ['yx_zht_course zc', 'zsm.course_id =zc.id ', 'left'],  //课时
        ];
        $alias = 'zsm';
        $table = 'zht_short_message';
        $short_message_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'zsm.create_time desc', $field = 'zsm.*,m.cname,m.province,m.city,m.area,m.address,zc.course_name', $page, $pageSize);
        foreach ($short_message_data as $k => $v) {
            $short_message_data[$k]['maddress'] = $v['province'] . $v['city'] . $v['area'] . $v['address'];
        }
        $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'zsm.id');
        $info_data = [
            'info' => $short_message_data,
            'num' => $num,
            'pageSzie' => (int)$pageSize,
        ];
        return jsonResponseSuccess($info_data);
    }

    //获取多级课程到包
    public static function getSurplusNoticeCourse($mem_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }

        $where_short_message = [
            'mem_id' => $mem_id,
            'is_del' => 1
        ];

        $course_num_data = Crud::getData('zht_short_message', 2, $where_short_message, 'arrange_course_id');
        if (!$course_num_data) {
            throw new NothingMissException();
        }

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
                            'mem_id' => $mem_id,
                            'category_small_id' => $vv['value']
                        ];
                        $curriculum_info = Crud::getData('zht_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                        if ($curriculum_info) {
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                            foreach ($curriculum_info as $kkk => $vvv) {
                                $where = [
                                    'mem_id' => $mem_id,
                                    'course_id' => $vvv['value'],
                                    'is_del' => 1,
                                ];
                                $arrange_course_info = Crud::getData('zht_arrange_course', $type = 2, $where, $field = 'id value,arrange_course_name label', $order = '', $page = '1', $pageSize = '1000');
                            }
                            if ($arrange_course_info) {
                                $info[$k]['children'][$kk]['children'][$kkk]['children'] = $arrange_course_info;

                                foreach ($arrange_course_info as $kkkk => $vvvv) {
                                    $arrange_course_array = [];
                                    $where_arrange_course = [
                                        'arrange_course_id' => $vvvv['value'],
                                        'is_del' => 1,
                                    ];
                                    $course_timetable_info = Crud::getData('zht_course_timetable', $type = 2, $where_arrange_course, $field = 'id,attend_class_num', 'attend_class_num desc');
                                    $arrange_course_array[] = [
                                        'value' => $course_timetable_info[0]['id'],
                                        'label' => $course_timetable_info[0]['attend_class_num'],
                                    ];
                                    if ($arrange_course_array) {
                                        $info[$k]['children'][$kk]['children'][$kkk]['children'][$kkkk]['children'] = $arrange_course_array;
                                    } else {
                                        $info[$k]['children'][$kk]['children'][$kkk]['children'][$kkkk]['children'] = [];
                                    }
                                }

                            } else {
                                $info[$k]['children'][$kk]['children'][$kkk]['children'] = [];
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

    public static function getSurplusNoticeCourses($mem_id = '')
    {
        $account_data = self::isuserData();
        if (empty($mem_id)) {
            $mem_id = $account_data['mem_id'];
        }

        $where_short_message = [
            'mem_id' => $mem_id,
            'is_del' => 1
        ];

        $course_num_data = Crud::getData('zht_short_message', 2, $where_short_message, 'course_num_id');
        if (!$course_num_data) {
            $course_num_data = [];
        }

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
                            'mem_id' => $mem_id,
                            'category_small_id' => $vv['value']
                        ];
                        $curriculum_info = Crud::getData('zht_course', $type = 2, $where, $field = 'id value,course_name label', $order = '', $page = '1', $pageSize = '1000');
                        if ($curriculum_info) {
                            $info[$k]['children'][$kk]['children'] = $curriculum_info;
                            foreach ($curriculum_info as $kkk => $vvv) {
                                $where = [
                                    'mem_id' => $mem_id,
                                    'course_id' => $vvv['value'],
                                    'is_del' => 1,
                                ];
                                $course_num_info = Crud::getData('zht_course_num', $type = 2, $where, $field = 'id value,course_section_num label', $order = '', $page = '1', $pageSize = '1000');
                                if ($course_num_info) {
                                    foreach ($course_num_info as $kkkk => $vvvv) {
                                        $course_num_info[$kkkk]['disabled'] = false;
                                        foreach ($course_num_data as $ak => $av) {
                                            if ($vvvv['value'] == $av['course_num_id']) {
                                                $course_num_info[$kkkk]['disabled'] = true;
                                            }
                                        }
                                    }
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

}