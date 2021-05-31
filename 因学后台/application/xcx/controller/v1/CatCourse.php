<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/27 0027
 * Time: 15:26
 */

namespace app\xcx\controller\v1;


use app\lib\exception\CatCouresMissException;
use app\lib\exception\CatCouresMissExceptionYes;
use app\lib\exception\ISCourseMissException;
use app\lib\exception\NothingMissException;
use app\validate\CatCourseMustBePostiveInt;
use app\common\model\Crud;
use app\validate\UserIDMustBePostiveInt;
use think\Db;

class CatCourse extends BaseController
{
    /**
     * 添加购物车
     */
    public static function setCatCourse()
    {
        $data = input();  //加一个课程价格
        (new CatCourseMustBePostiveInt())->goCheck();

        //判断课程是否正确
        $isCourse = self::isCourse($data);
        if (!$isCourse) {
            throw new ISCourseMissException();
        }
        $payExperienceCourse = self::payExperienceCourse($data);
        if ($payExperienceCourse != 1000) {
            return jsonResponse('3000', '你已购买此课程', 2013);
        }
        //获取用户购物车是否有此课程
        unset($data['present_price']);
        $isCat = self::isCatData($data);
        $table = request()->controller();
        if ($isCat == 1) {
            //用户购物车无此课做添加购物车
            $info = Crud::setAdd($table, $data);
        } elseif ($isCat == 2) {
            return jsonResponse('9001', '你已加入购物');
        }
        if (!$info) {
            throw new CatCouresMissException();
        } else {
            return jsonResponse('1000', '加入购物成功', $info);
        }
    }

    /**
     * 验证课程正确性
     * @param $data
     */
    public static function isCourse($data)
    { //1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        if ($data['status'] == 1) {
            $where['id'] = $data['cou_id'];
            $info = Db::name('course')->where($where)->field('id')->find();
        } elseif ($data['status'] == 2) {
            $where['id'] = $data['cou_id'];
            $info = Db::name('experience_course')->where($where)->field('id')->find();
        } elseif ($data['status'] == 3) {
            $where['id'] = $data['cou_id'];
            $info = Db::name('community_course')->where($where)->field('id')->find();
        } elseif ($data['status'] == 4) {
            $where['id'] = $data['cou_id'];
            $info = Db::name('seckill_course')->where($where)->field('id')->find();
        } elseif ($data['status'] == 5) {
            $where['id'] = $data['cou_id'];
            $info = Db::name('synthetical_course')->where($where)->field('id')->find();
        }
        return $info;

    }


    /**
     * 查看购物车是否有此课程
     */
    public static function isCatData($data)
    {
        $where = [
            'is_del' => 1,
            'user_id' => $data['user_id'],
            'cou_id' => $data['cou_id'],
            'status' => $data['status'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id');
        if ($info) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * 购物车展示
     */
    public static function getCatCourse()
    {
        $data = input();
        (new UserIDMustBePostiveInt())->goCheck();

        $table = request()->controller();
        $where1 = [
            'user_id' => $data['user_id'],
            'is_del' => 1,
        ];
        $info = Crud::getData($table, $type = 2, $where1, $field = 'mem_id,syntheticalcn_id,community_id,status', $order = 'create_time desc', 1, 10000);
        if ($info) {
            $remove_mem_data = assoc_unique($info, 'mem_id'); //去除mem_id
            $remove_syntheticalcn_data = assoc_unique($remove_mem_data, 'syntheticalcn_id');//去除syntheticalcn_id
            $total_dats = assoc_unique($remove_syntheticalcn_data, 'community_id');//去除community_id
            foreach ($total_dats as $k => $v) {
                if ($v['status'] == 1 || $v['status'] == 2 || $v['status'] == 4) {//1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体课程
                    $memder_data = Crud::getData('member', $type = 1, ['uid' => $v['mem_id']], $field = 'cname,uid', $order = '', 1, 10000);
                    if ($memder_data) {
                        $total_data_name[] = $memder_data;
                    } else {
                        $memder_data = [
                            'cname' => '',
                            'uid' => '',
                        ];
                        $total_data_name[] = $memder_data;
                    }
                    $total_data_name[$k]['status'] = $v['status'];
                } elseif ($v['status'] == 3) {
                    $community = Crud::getData('community_name', $type = 1, ['id' => $v['community_id']], $field = 'name cname,id coid', $order = '', 1, 10000);
                    if ($community) {
                        $total_data_name[] = $community;
                    } else {
                        $community = [
                            'cname' => '',
                            'coid' => '',
                        ];
                        $total_data_name[] = $community;
                    }
                    $total_data_name[$k]['status'] = $v['status'];
                } elseif ($v['status'] == 5) {
                    $synthetical_data = Crud::getData('synthetical_name', $type = 1, ['id' => $v['syntheticalcn_id']], $field = 'name cname,id syid', $order = '', 1, 10000);
                    if ($synthetical_data) {
                        $total_data_name[] = $synthetical_data;
                    } else {
                        $synthetical_data = [
                            'cname' => '',
                            'syid' => '',
                        ];
                        $total_data_name[] = $synthetical_data;
                    }
                    $total_data_name[$k]['status'] = $v['status'];
                }
            }
        }

        //获取用户购物车信息
        $where2 = [
            'user_id' => $data['user_id'],
            'is_del' => 1,
        ];

        $info = Crud::getData($table, $type = 2, $where2, $field = 'id,user_id,syntheticalcn_id,community_id,mem_id,cou_id,status,create_time', $order = '', 1, 10000);
        if (!$info) {
            throw new CatCouresMissException();
        } else {
            //不同类型课程购物车展示
            $info = Crud::getCatData($info); //1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体课程
            foreach ($info as $kt => $vt) {
                $info[$kt]['selected'] = true;
            }
            //组合购物车信息
            foreach ($total_data_name as $k => $v) {
                foreach ($info as $kk => $vv) {
                    if ($v['status'] == 1 || $v['status'] == 2 || $v['status'] == 4) {
                        if ($v['uid'] == $vv['mem_id']) {
                            $total_data_name[$k]['catData'][] = $vv;
                        }
                    } elseif ($v['status'] == 3) {
                        if ($v['coid'] == $vv['community_id']) {
                            $total_data_name[$k]['catData'][] = $vv;
                        }
                    } elseif ($v['status'] == 5) {
                        if ($v['syid'] == $vv['syntheticalcn_id']) {
                            $total_data_name[$k]['catData'][] = $vv;
                        }
                    }

                }
            }
            $res['member_cname_data'] = $total_data_name;
            //获取用户购物车信息
            $where3 = [
                'user_id' => $data['user_id'],
                'is_del' => 1,
            ];
            $cat_num = Crud::getCount($table, $where3);
            if ($cat_num) {
                $res['cat_num'] = $cat_num;
            }

            return jsonResponse('1000', '成功获取购物车', $res);
        }
    }

    /**
     * 删除购物车
     */
    public static function delCatCourse($carcou_id)
    {
        $where = [
            'id' => $carcou_id
        ];
        $upData = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $upData);
        if (!$info) {
            throw new ActivityMissException();
        } else {
            return jsonResponse('1000', '删除成功', $info);
        }
    }

    /**
     * 验证用户是否购买过此体验课
     * @param $data
     * @return int|string
     * @throws \Exception
     */
    public static function payExperienceCourse($data)
    {
        if ($data['present_price'] == 0 ||$data['present_price'] == null) {
//        if ($data['status'] == 2) {
            $where = [
                'is_del' => 1,
//                'status' => 8,
                'status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
                'uid' => $data['user_id'],
                'price' => $data['present_price'],
                'cid' => $data['cou_id'],
            ];
            $ExperienceCourse = Crud::getData('order', 1, $where, 'id');
            if ($ExperienceCourse) {
                return jsonResponse('3000', '你已购买此课程', 2013);
            } else {
                return 1000;
            }
        }else{
            return 1000;
        }
    }


}