<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/28 0028
 * Time: 13:11
 */

namespace app\pc\controller\v2;


use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use app\pc\controller\v1\BaseController;
use app\common\model\Crud;

class ZhtBanner extends BaseController
{
    const table_zht_banner = 'zht_banner';

    //获取轮播图
    public static function getZhtBanner($banner_name = '', $page = 1, $pageSize = 16)
    {
        $where = [
            'is_del' => 1
        ];
        (isset($banner_name) && !empty($banner_name)) && $where['name'] = ['like', '%' . $banner_name . '%'];
        $table = self::table_zht_banner;
        $info = Crud::getData($table, 2, $where, 'id banner_id,mem_id,url_id,url_market_id,name,img,type,value,is_disable,sort,create_time', 'sort asp id desc', $page, $pageSize);
        if ($info) {
            $num = Crud::getCount($table, $where);
            foreach ($info as $k => $v) {
                $info[$k]['create_time_Exhibition'] = date('Y-m-d', $v['create_time']);
                //类型 1默认 2线上课程 3线下课程 4活动 5外链 6暑期活动 7机构
                if ($v['type'] == 1) {
                    $info[$k]['type_name'] = '默认';
                } elseif ($v['type'] == 2) {
                    $info[$k]['type_name'] = '线上课程';
                } elseif ($v['type'] == 3) {
                    $info[$k]['type_name'] = '线下课程';
                } elseif ($v['type'] == 4) {
                    $info[$k]['type_name'] = '活动';
                } elseif ($v['type'] == 5) {
                    $info[$k]['type_name'] = '外链';
                } elseif ($v['type'] == 6) {
                    $info[$k]['type_name'] = '暑期活动';
                } elseif ($v['type'] == 7) {
                    $info[$k]['type_name'] = '机构';
                }
            }
            $info_data = [
                'info' => $info,
                'pageSize' => (int)$pageSize,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }

    }

    //获取轮播图字段
    public function getZhtBannerField()
    {
        $arrye = [
            ['name', '名称'],
            ['img', '主活动图片', '220'],
            ['type_name', '主活时间'],
            ['create_time_Exhibition', '主活动创建时间'],
        ];
        $array_Field = self::fieldArray($arrye);
        return jsonResponseSuccess($array_Field);
    }

    //添加轮播图
    public static function addZhtBanner()
    {
        $data = input();
        $data = self::getValue($data);
        $table = self::table_zht_banner;
        $info = Crud::setAdd($table, $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new AddMissException();
        }

    }

    //修改轮播图
    public static function editZhtBanner()
    {
        $data = input();
        $data = self::getValue($data);
        $table = self::table_zht_banner;
        $info = Crud::setUpdate($table, ['id' => $data['banner_id']], $data);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new UpdateMissException();
        }

    }

    //删除
    public static function delZhtBanner($banner_id)
    {
        $table = self::table_zht_banner;
        $info = Crud::setUpdate($table, ['id' => $banner_id], ['is_del' => 2]);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new DelMissException();
        }
    }

    //上下架
    public static function typeZhtBanner()
    {
        $data = input();
        $table = self::table_zht_banner;
        if ($data['is_disable'] == 1) {
            $is_disable = 2;
        } elseif ($data['is_disable'] == 2) {
            $is_disable = 1;
        }
        $market_info = Crud::setUpdate($table, ['id' => ['in', $data['banner_id']]], ['is_disable' => $is_disable]);
        if ($market_info) {
            return jsonResponseSuccess($market_info);
        } else {
            throw new UpdateMissException();
        }
    }

    /**
     * type 类型 1默认 2线上课程 3线下课程 4活动 5外链 6暑期活动 7机构
     */
    public static function getValue($data)
    {
        if ($data['type'] == 2) {
//            $data['value'] = '/coursePages/courseInfo/courseInfo?id=' . $data['url_id'] . '&type=1&course_category=1';
        } elseif ($data['type'] == 3) {
            $data['value'] = '/coursePages/courseInfo/courseInfo?id=' . $data['url_id'] . '&type=1&course_category=2';  //type 1 普通课 2活动课
        } elseif ($data['type'] == 4) {
            //获取课程信息
            //activity_course_category 1线上课程，2线下课程，3其他
            $activity_data = Crud::getData('zht_activity', 1, ['id' => $data['url_id'], 'is_del' => 1], 'course_id,activity_course_category');
            if (!$activity_data) {
                return jsonResponse(3000, '添加或修改失败');
            } elseif ($activity_data['activity_course_category'] == 1) {
                //线上活动课程
            } elseif ($activity_data['activity_course_category'] == 2) {
                //线下活动课程
                $data['value'] = '/coursePages/courseInfo/courseInfo?id=' . $activity_data['course_id'] . '&type=2&course_category=2';  //type 1 普通课 2活动课
            }

        } elseif ($data['type'] == 5) {
            $data['value'] = '/orderPages/webView/webView?path=' . $data['value'];
        } elseif ($data['type'] == 6) {
            $data['value'] = '/orderPages/birdie/birdie?id=' . $data['url_market_id'];
        } elseif ($data['type'] == 7) {
            $data['value'] = '/coursePages/institution/institution?mem_id=' . $data['mem_id'];
        }
        return $data;
    }

    //获取机构名
    public static function getMemberNameData()
    {
        $info = Crud::getData('member', 2, ['is_del' => 1], 'uid mem_id,cname', '', 1, 10000000);
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }

    }

    //获取跳转类型及内容
    public static function getJumpdata()
    {
        $data = input();
        if ($data['type'] == 2) {
            $info = Crud::getData('zht_online_course', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,course_name name', '', 1, 1000000);
        } elseif ($data['type'] == 3) {
            $info = Crud::getData('zht_course', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,course_name name', '', 1, 1000000);
        } elseif ($data['type'] == 4) {
            $info = Crud::getData('zht_activity', 2, ['mem_id' => $data['mem_id'], 'is_del' => 1], 'id,activity_title name', '', 1, 1000000);
        } elseif ($data['type'] == 6) {
            $account_data = self::isuserData();
            $info = Crud::getData('zht_market', 2, ['mem_id' => $account_data['mem_id'], 'is_del' => 1], 'id,name', '', 1, 1000000);
        }
        if ($info) {
            return jsonResponseSuccess($info);
        } else {
            throw new NothingMissException();
        }
    }

}