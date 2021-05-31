<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/5 0005
 * Time: 14:20
 */

namespace app\xcx\controller\v1;


use app\lib\exception\OrderMissExceptionFind;
use app\common\model\Crud;
use think\Db;

class Wxpay
{
    //获取订单号进行查询小订单信息
    public static function setWxpay()
    {
        $data = input();
        $user = Crud::getData('user', $type = 1, ['id' => $data['user_id']], $field = 'x_openid');
        if ($user) {
            $openId = $user['x_openid'];
//            $openId = 'oIxO25G8g0l6Em_bSjqiunx1D1Ig';
        }
        (new OrderMissExceptionFind())->getData();
        //获取大订单
        $where = [
            'order_num' => $data['order_num'],//大订单号
            'is_del' => 1,
            'status' => ['in', [1, 9]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中
        ];
        $table = 'order_num';
        $order_num_data = Crud::getData($table, $type = 1, $where, $field = 'price,status');
        if (!$order_num_data) {
            return jsonResponse('2000', '大订单信息有误');
        }
        //验证小订单
        $where = [
            'order_num' => $data['order_num'],//大订单号
            'is_del' => 1,
            'status' => ['in', [1, 9]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中
        ];
        $table1 = 'order';
        $order_data = Crud::getData($table1, $type = 2, $where, $field = 'id,order_id,order_num,mid,cid,name,status,price,cou_status');
        if (!$order_data) {
            return jsonResponse('2000', '小订单信息有误');
        }
        Db::startTrans();
        $table1 = 'order';
        foreach ($order_data as $k => $v) {
            $course_del = self::setOtherStock($v);
            if ($course_del != 1000) {
                Db::rollback();
                return $course_del;
            }
            $where1 = [
                'order_id' => $v['order_id']
            ];
            $upData1 = [
                'status' => 9,
                'update_time' => time(),
            ];
            //更改支付状态为 支付中(小订单)
            $order_data = Crud::setUpdate($table1, $where1, $upData1);
            if (!$order_data) {
                Db::rollback();
                return jsonResponse('3000', '小订单修改支付中失败');
            }
        }
        $table2 = 'order_num';
        $where1 = [
            'order_num' => $data['order_num']
        ];
        $upData1 = [
            'status' => 9,
            'update_time' => time()
        ];
        //更改支付状态为 支付中(大订单)
        $order_data = Crud::setUpdate($table2, $where1, $upData1);
        if (!$order_data) {
            return jsonResponse('3000', '大订单修改支付中失败');
        } else {
            Db::commit();
            $Wxparameter = config('wechat');
            //获取用户openid
            $user = Crud::getData('user', $type = 1, ['id' => $data['user_id']], $field = 'x_openid');
            if ($user) {
                $openId = $user['x_openid'];
            }
            $order_sn = $data['order_num'];
            $notify_url = 'https://zht.insooner.com/xcx/v1/wxpayNotify';
            $orderInfo = '';
            vendor('wxpayapi.lib.WxPayJsApiPay');
            //①、获取用户openid
            $tools = new \JsApiPay();
            //②、统一下单
            $input = new \WxPayUnifiedOrder();

            $input->SetAppid($Wxparameter['AppID']);
            $input->SetMch_id($Wxparameter['mch_id']);
            //订单名字
            $input->SetBody("因学教育机构充值");
            $input->SetAttach("因学教育机构充值");
            //订单号
            $input->SetOut_trade_no($order_sn);
            //价格
//            $input->SetTotal_fee($order_num_data['price'] * 100);
            $aaprice = 0.01;
            $input->SetTotal_fee($aaprice * 100);
            $input->SetGoods_tag("");
            //通知地址
            $input->SetNotify_url($notify_url);
            //订单类型
            $input->SetTrade_type("JSAPI");
            //客户openid
            $input->SetOpenid($openId);

            $order = \WxPayApi::unifiedOrder($input);
            if ($order['return_code'] == "FAIL") {
                return jsonResponse('30001', $order['return_msg']);
            }
            if ($order["result_code"] == "FAIL") {
                return jsonResponse('30002', $order['err_code_des']);
            }
            //得到需付款的数据
            $jsApiParameters = $tools->GetJsApiParameters($order);

            $jsApiParameters['code'] = 1;
            return json($jsApiParameters);
        }
        //进行支付.......
    }


    //支付回
    public static function wxpayNotify()
    {
//        file_put_contents('a1.log', print_r(1,true).PHP_EOL);
        //更改订单状态
        //机构加销售
        //添加流水
        //是否有邀请人员
        vendor('wxpayapi.lib.WxPayConfig');
        $result = file_get_contents('php://input', 'r');
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA);
        $result = json_encode($result);
        $result = json_decode($result, true);
//        file_put_contents('1.log', print_r($result,true).PHP_EOL);
        if ($result['result_code'] === 'SUCCESS' && $result['mch_id'] === \WxPayConfig::MCHID) {
            ksort($result);
            //拼接生成签名的字符串
            $sign_string = '';
            foreach ($result as $key => $value) {
                if ($key !== 'sign') {
                    $sign_string .= $key . '=' . $value . '&';
                }
            }
            $sign_string .= 'key=' . \WxPayConfig::KEY;
            $sign = strtoupper(md5($sign_string));
            if ($sign === $result['sign']) {
                Db::startTrans();
                try {
                    $where = [
                        'status' => 9,
                        'order_num' => $result['out_trade_no'],
                        'is_del' => 1,
                    ];
                    //验证大订单 yx_user_member_belong
                    $table = 'order_num';
                    $order_num_data = Crud::getData($table, $type = 1, $where, $field = 'order_num,price,uid');
                    if ($order_num_data) {
                        //获取小订单
                        $table1 = 'order';
                        $order_data = Crud::getData($table1, $type = 2, $where, $field = 'order_id,order_num,mid,cid,name,price,uid,cou_status,community_id,syntheticalcn_id');
                        if (!$order_data) {
                            Db::rollback();
                        }

                        foreach ($order_data as $k => $v) {
                            //修改小订单状态
                            $order_update = Crud::setUpdate($table1, ['order_id' => $v['order_id']], ['status' => 2]);
                            if (!$order_update) {
                                Db::rollback();
                            }

                            //机构加销量
                            if ($v['cou_status'] == 1 || $v['cou_status'] == 2 || $v['cou_status'] == 4) {
                                $enroll_num_inc = Crud::setIncs('member', ['uid' => $v['mid']], 'enroll_num', 1);
                                if (!$enroll_num_inc) {
                                    Db::rollback();
                                }
                            } elseif ($v['cou_status'] == 3) {
                                $enroll_num_inc = Crud::setIncs('community_name', ['id' => $v['community_id']], 'enroll_num', 1);
                                if (!$enroll_num_inc) {
                                    Db::rollback();
                                }

                            } elseif ($v['cou_status'] == 5) {
                                $enroll_num_inc = Crud::setIncs('synthetical_name', ['id' => $v['syntheticalcn_id']], 'enroll_num', 1);
                                if (!$enroll_num_inc) {
                                    Db::rollback();
                                }
                            }
                            //加用户属于此机构学生
                            $where1 = [
                                'mem_id' => $v['mid'],
                                'user_id' => $v['uid'],
                                'is_del' => 1,
                            ];
                            $user_member = Crud::getData('user_member_belong', $type = 2, $where1, $field = 'id');
                            if (!$user_member) {
                                $user_member_data = [
                                    'mem_id' => $v['mid'],
                                    'user_id' => $v['uid'],
                                ];
                                $user_member_belong = Crud::setAdd('user_member_belong', $user_member_data, $type = 1);
                                if (!$user_member_belong) {
                                    Db::rollback();
                                }
                            }
                            $where2 = [
                                'user_id' => $order_num_data['uid'],
                                'cou_id' => $v['cid'],
                                'status' => $v['cou_status'],
                            ];
                            $cat_data = Crud::getData('cat_course', 1, $where2, 'id');

                            //购物车
                            if ($cat_data) {
                                $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
//                        dump($del_cat);
                                if (!$del_cat) {
                                    Db::rollback();
                                }
                            }
                            //添加用户流水信息
                            $account_data = [
                                'uid' => $order_num_data['uid'],
                                'mid' => $v['mid'],
                                'order_id' => time() . rand(10, 99),
                                'price' => $v['price'],
                                'type' => 2, //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                                'status' => 1, //1完成，2未完成，3提出失败
                                'types' => 1, //1普通用户，2机构充值 （邀请也用此字段区分）
                                'cou_status' => $v['cou_status'], //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
                            ];
                            $account_info = Crud::setAdd('account', $account_data);
                            if (!$account_info) {
                                Db::rollback();
                            }
                        }
                        //更改大订单状态
                        $order_num_update = Crud::setUpdate($table, ['order_num' => $order_num_data['order_num']], ['status' => 2]);
                        if (!$order_num_update) {
                            Db::rollback();
                        }

                        if ($order_update) {
                            Db::commit();
                        }
                    } else {
                        Db::rollback();
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
                file_put_contents(ROOT_PATH . 'public' . DS . "wechat.txt", date("Y-m-d H:i:s") . "  " . json_encode($result) . "\r\n", FILE_APPEND);
                $return = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>"; //返回成功给微信端 一定要带上不然微信会一直回调8次
                ob_clean();
                echo $return;
                exit;
            }
        } else {
            return "fail";
        }

    }

    //其他课程减库存加销量
    public static function setOtherStock($data)
    {
        if ($data['cou_status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程
            //yx_course
            $table = 'course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid']
            ];
            //查年当库存
            $Course_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'surplus_num');
            if ($Course_data) {
                if ($Course_data['surplus_num'] > 0) {
                    $Course_num_del = Crud::setDecs($table, $where, 'surplus_num', 1);
                    if (!$Course_num_del) {
                        return jsonResponse('3000', '减库存失败');
                    }
                    $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
                    if (!$Course_num_incs) {
                        return jsonResponse('3000', '加销量失败');
                    }
                } else {
                    return jsonResponse('2000', '当前库存不足');
                }
            } else {
                return jsonResponse('2000', '当前课程已下架');
            }
        } elseif ($data['cou_status'] == 2) {
            $table = 'experience_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //查年当库存
            $Course_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'surplus_num');
            if ($Course_data) {
                if ($Course_data['surplus_num'] > 0) {
                    //查询活动课程
                    $Course_num_del = Crud::setDecs($table, $where, 'surplus_num', 1);
                    if (!$Course_num_del) {
                        return jsonResponse('3000', '减库存失败');
                    }
                    $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
                    if (!$Course_num_incs) {
                        return jsonResponse('3000', '加销量失败');
                    }
                } else {
                    return jsonResponse('2000', '当前课程名额已报满');
                }
            } else {
                return jsonResponse('2000', '当前课程已下架');
            }

        } elseif ($data['cou_status'] == 3) {
            $table = 'community_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //查年当库存
            $Course_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'surplus_num');
            if ($Course_data) {
                if ($Course_data['surplus_num'] > 0) {
                    //查询活动课程
                    $Course_num_del = Crud::setDecs($table, $where, 'surplus_num', 1);
                    if (!$Course_num_del) {
                        return jsonResponse('3000', '减库存失败');
                    }
                    $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
                    if (!$Course_num_incs) {
                        return jsonResponse('3000', '加销量失败');
                    }
                } else {
                    return jsonResponse('2000', '当前课程名额已报满');
                }
            } else {
                return jsonResponse('2000', '当前课程已下架');
            }

        } elseif ($data['cou_status'] == 4) {
            $table = 'seckill_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //查年当库存
            $Course_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'surplus_num');
            if ($Course_data) {
                if ($Course_data['surplus_num'] > 0) {
                    //查询秒杀课程
                    $Course_data = Crud::setDecs($table, $where, 'surplus_num', 1);
                    if (!$Course_data) {
                        return jsonResponse('3000', '减库存失败');
                    }
                    $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
                    if (!$Course_num_incs) {
                        return jsonResponse('3000', '加销量失败');
                    }
                } else {
                    return jsonResponse('2000', '当前课程名额已报满');
                }

            } else {
                return jsonResponse('2000', '当前课程已下架');
            }
        } elseif ($data['cou_status'] == 5) {
            $table = 'synthetical_course';
            $where = [
                'is_del' => 1,
                'type' => 1,
                'id' => $data['cid'],
            ];
            //查年当库存
            $Course_data = Crud::getDataGroup($table, $type = 1, $where, $field = 'surplus_num');
            if ($Course_data) {
                if ($Course_data['surplus_num'] > 0) {
                    //查询秒杀课程
                    $Course_data = Crud::setDecs($table, $where, 'surplus_num', 1);
                    if (!$Course_data) {
                        return jsonResponse('3000', '减库存失败');
                    }
                    $Course_num_incs = Crud::setIncs($table, $where, 'enroll_num', 1);
                    if (!$Course_num_incs) {
                        return jsonResponse('3000', '加销量失败');
                    }
                } else {
                    return jsonResponse('2000', '当前课程名额已报满');
                }

            } else {
                return jsonResponse('2000', '当前课程已下架');
            }

        } else {
            return jsonResponse('3000', '减余额失败');
        }
        return 1000;
    }


    public static function editOrderStatus()
    {
        $result['out_trade_no'] = 157777478651;//大订单号
        Db::startTrans();
        try {
            $where = [
                'status' => 9,
                'order_num' => $result['out_trade_no'],
                'is_del' => 1,
            ];
            //验证大订单 yx_user_member_belong
            $table = 'order_num';
            $order_num_data = Crud::getData($table, $type = 1, $where, $field = 'order_num,price,uid');
            if ($order_num_data) {
                //获取小订单
                $table1 = 'order';
                $order_data = Crud::getData($table1, $type = 2, $where, $field = 'order_id,order_num,mid,cid,name,price,uid,cou_status,community_id,syntheticalcn_id');
                if (!$order_data) {
                    Db::rollback();
                }

                foreach ($order_data as $k => $v) {
                    //修改小订单状态
                    $order_update = Crud::setUpdate($table1, ['order_id' => $v['order_id']], ['status' => 2]);
                    if (!$order_update) {
                        Db::rollback();
                    }

                    //机构加销量
                    if ($v['cou_status'] == 1 || $v['cou_status'] == 2 || $v['cou_status'] == 4) {
                        $enroll_num_inc = Crud::setIncs('member', ['uid' => $v['mid']], 'enroll_num', 1);
                        if (!$enroll_num_inc) {
                            Db::rollback();
                        }
                    } elseif ($v['cou_status'] == 3) {
                        $enroll_num_inc = Crud::setIncs('community_name', ['id' => $v['community_id']], 'enroll_num', 1);
                        if (!$enroll_num_inc) {
                            Db::rollback();
                        }

                    } elseif ($v['cou_status'] == 5) {
                        $enroll_num_inc = Crud::setIncs('synthetical_name', ['id' => $v['syntheticalcn_id']], 'enroll_num', 1);
                        if (!$enroll_num_inc) {
                            Db::rollback();
                        }
                    }
                    //加用户属于此机构学生
                    $where1 = [
                        'mem_id' => $v['mid'],
                        'user_id' => $v['uid'],
                        'is_del' => 1,
                    ];
                    $user_member = Crud::getData('user_member_belong', $type = 2, $where1, $field = 'id');
                    if (!$user_member) {
                        $user_member_data = [
                            'mem_id' => $v['mid'],
                            'user_id' => $v['uid'],
                        ];
                        $user_member_belong = Crud::setAdd('user_member_belong', $user_member_data, $type = 1);
                        if (!$user_member_belong) {
                            Db::rollback();
                        }
                    }
                    $where2 = [
                        'user_id' => $order_num_data['uid'],
                        'cou_id' => $v['cid'],
                        'status' => $v['cou_status'],
                    ];
                    $cat_data = Crud::getData('cat_course', 1, $where2, 'id');

                    //购物车
                    if ($cat_data) {
                        $del_cat = Crud::setUpdate('cat_course', $where2, ['is_del' => 2]);
//                        dump($del_cat);
                        if (!$del_cat) {
                            Db::rollback();
                        }
                    }
                    //添加用户流水信息
                    $account_data = [
                        'uid' => $order_num_data['uid'],
                        'mid' => $v['mid'],
                        'order_id' => time() . rand(10, 99),
                        'price' => $v['price'],
                        'type' => 2, //1充值，2买课，3提现，4退款，5会员充值,6邀请奖励,7申请退款,8拒绝退款
                        'status' => 1, //1完成，2未完成，3提出失败
                        'types' => 1, //1普通用户，2机构充值 （邀请也用此字段区分）
                        'cou_status' => $v['cou_status'], //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
                    ];
                    $account_info = Crud::setAdd('account', $account_data);
                    if (!$account_info) {
                        Db::rollback();
                    }
                }
                //更改大订单状态
                $order_num_update = Crud::setUpdate($table, ['order_num' => $order_num_data['order_num']], ['status' => 2]);
                if (!$order_num_update) {
                    Db::rollback();
                }

                if ($order_update) {
                    Db::commit();
                }
            } else {
                Db::rollback();
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }


    }


//生成随机字符串
    function generateNonceStr($length = 16)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }


}