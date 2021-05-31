<?php

namespace app\xcx\controller\v3;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use app\xcx\controller\v2\Base;


/**
 * 小候鸟
 */
class Migratory extends Base
{
    protected $exceptTicket = ["getMarket", 'getMarketCourse'];

    /**
     * @Notes: 获取活动
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 10:02
     * @Interface getMarket
     * @return string
     */
    public function getMarket()
    {
        //活动
        $marketId = input('post.id');
        if (empty($marketId)) {
            return returnResponse('1001', '参数错误');
        }
        //'market_type' => 1
        $market = Crud::getData('zht_market', 1, ['id' => $marketId, 'is_del' => 1], "id,name,image,start_time,end_time,small_name,detail,market_type");
        if (empty($market)) {
            return returnResponse('1003', '活动已结束');
        }
        $marketList = Crud::getDataunpage("zht_market_list", 2, ['market_id' => $marketId, 'is_del' => 1, 'market_list_type' => 1], "id,name,img,brief,type");
        if (count($marketList) > 0) {
            foreach ($marketList as &$item) {
                if ($item['type'] == 1) {
                    $marketListDetailsId = Crud::getDataunpage("zht_market_list_detail", 2, ['is_del' => 1, 'market_list_id' => $item['id']], 'id');
                    $arr = array_column($marketListDetailsId, 'id');
                    $whe['is_del'] = ["=", 1];
                    $whe['market_list_detail_id'] = ["in", $arr];
                    $counts = Crud::getData("zht_market_list_detail_time", 1, $whe, 'IFNULL(sum(used_quota),0) as used_quota');
                    $item['used_quota'] = empty($counts) ? 0 : $counts['used_quota'];

                }

            }
        }
        $market['market_list'] = $marketList;
        $market['start_time'] = date("Y-m-d", $market['start_time']);
        $market['end_time'] = date("Y-m-d", $market['end_time']);
        $market['detail'] = unserialize($market['detail']);
        return returnResponse('1000', '', $market);
    }

    /**
     * @Notes: 获取活动相关课程
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 14:20
     * @Interface getMarketCourse
     * @return string
     */
    public function getMarketCourse()
    {
        $marketId = input('post.id');
        if (empty($marketId)) {
            return returnResponse('1001', '参数错误');
        }
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $latitude = input('post.latitude/f');
        $latitude = empty($latitude) ? "30.185019" : $latitude;
        $longitude = input('post.longitude/f');
        $longitude = empty($longitude) ? "120.193549" : $longitude;
        $field = 'zc.id as course_id,zc.course_type,zc.course_img,zc.discount_start_time,zc.discount_end_time,zc.discount,zy.name,zc.course_name,zc.title,ROUND(6378.138*2*ASIN(SQRT(POW(SIN((' . $latitude . '*PI()/180-m.latitude*PI()/180)/2),2)+COS(' . $latitude . '*PI()/180)*COS(m.latitude*PI()/180)*POW(SIN((' . $longitude . '*PI()/180-m.longitude*PI()/180)/2),2)))*1000) AS distance';
        $join = [
            ['yx_zht_course zc', 'zmc.course_id = zc.id'],
            ['yx_zht_category zy', 'zc.category_id = zy.id', 'left'],
            ['yx_member m', 'zc.mem_id = m.uid', 'left'],
        ];
        $result = Crud::getRelationData("zht_market_course", 2, ['zmc.market_id' => $marketId, 'zmc.is_del' => 1, 'zc.is_del' => 1, 'zc.type' => 1], $join, 'zmc', 'distance ASC', $field, $page, $pageSize);
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $enrollNum = Crud::getData('zht_course_num', 1, ['course_id' => $item['course_id'], 'is_del' => 1], "IFNULL(sum(enroll_num),0) as enroll_num,IFNULL(sum(surplus_num),0) as surplus_num,min(course_section_price) as present_price");
                $item['enroll_num'] = bcsub($enrollNum['surplus_num'], $enrollNum['enroll_num']);
                $item['course_category'] = 2;
                if ($item['discount_start_time'] <= time() && $item['discount_end_time'] >= time()) {
                    $dis = bcdiv($item['discount'], 10, 2);
                    $item['present_price'] = bcmul($dis, $enrollNum['present_price'], 2);
                } else {
                    $item['present_price'] = $enrollNum['present_price'];
                }
                unset($item['discount_start_time']);
                unset($item['discount_end_time']);
                unset($item['discount']);


            }
        }
        $results = [
            'result' => $result
        ];


        return returnResponse('1000', '', $results);

    }

    /**
     * @Notes: 获取小活动详情
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 14:17
     * @Interface getMarketListDetail
     * @return string
     */
    public function getMarketListDetail()
    {
        $marketListId = input('post.id');
        if (empty($marketListId)) {
            return returnResponse('1001', '参数错误');
        }

        $marketList = Crud::getData('zht_market_list', 1, ['id' => $marketListId, 'is_del' => 1], "id,name,brief,banner");
        if (empty($marketList)) {
            return returnResponse('1002', '活动已结束');
        }
        $marketList['banner'] = unserialize($marketList['banner']);

        //获取小活动信息
        $join = [
            ['yx_user u', 'zmo.user_id = u.id', 'left']
        ];
        //头像
        $avatar = Crud::getRelationData('zht_market_order', 2, ['zmo.market_list_id' => $marketListId, 'zmo.is_del' => 1], $join, 'zmo', 'zmo.id DESC', 'u.img');
        $marketList['avatar'] = $avatar;
        //报名人数
        $marketListDetailIds = Crud::getDataunpage("zht_market_list_detail", 2, ['is_del' => 1, 'market_list_id' => $marketListId], 'id');
        $ids = array_column($marketListDetailIds, 'id');
        $wh['market_list_detail_id'] = ['in', $ids];
        $wh['is_del'] = ['=', 1];
        $counts = Crud::getData("zht_market_list_detail_time", 1, $wh, 'IFNULL(sum(used_quota),0)as used_quota');

        $marketList['used_quota'] = empty($counts) ? 0 : $counts['used_quota'];
        //活动具体信息
        $detail = Crud::getDataunpage("zht_market_list_detail", 2, ['market_list_id' => $marketListId, 'is_del' => 1], 'id,name,brief,province,city,area,address,longitude,latitude');
        if (count($detail) > 0) {
            foreach ($detail as &$item) {
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
                unset($item['used_quota']);
                //获取具体时间段
                // $item['time'] = Crud::getDataunpage("zht_market_list_detail_time", 2, ['is_del' => 1, 'market_list_detail_id' => $item['id']], "id,(quota - used_quota) as quota,FROM_UNIXTIME(start_time, '%y年%m月%d日 %H:%i') as start_time,FROM_UNIXTIME(end_time, '%y年%m月%d日 %H:%i') as end_time");
                $time = Crud::getDataunpage("zht_market_list_detail_time", 2, ['is_del' => 1, 'market_list_detail_id' => $item['id']], "id,(quota - used_quota) as quota,start_time,end_time", 'start_time ASC');
                if (count($time) > 0) {
                    foreach ($time as &$it) {
                        $s = $it['start_time'];
                        $e = $it['end_time'];
                        $it['start_time'] = date("Y-m-d", $s) == date("Y-m-d", $e) ? date("y年m月d日 H:i", $s) : date("y年m月d日 H:i", $s);
                        $it['end_time'] = date("Y-m-d", $s) == date("Y-m-d", $e) ? date("H:i", $e) : date("y年m月d日 H:i", $e);
                    }
                }
                $item['time'] = $time;
            }
        }
        $marketList['detail'] = $detail;
        return returnResponse('1000', '', $marketList);
    }

    /**
     * @Notes: 报名活动
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 14:58
     * @Interface createMarketOrder
     * @return string
     */
    public function createMarketOrder()
    {

        if (!$contacts = input("post.contacts")) {
            return returnResponse("1001", '请填写联系人');
        }
        if (!$phone = input("post.phone")) {
            return returnResponse("1001", '请填写联系号码');
        }
        $chars = "/^1(3|4|5|6|7|8|9)\d{9}$/";
        if (!preg_match($chars, $phone)) {
            return returnResponse('1001', '手机号输入有误');
        }
        if (!$studentId = input('post.student_id/d')) {
            return returnResponse("1001", '请选择报名学员');
        }
        //判断学生绑定关系是否存在
        $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
        if (empty($student)) {
            return returnResponse('1002', '学员选择错误');
        }

        if (!$marketListId = input('post.market_list_id')) {
            return returnResponse("1001", '参数错误');
        }
        //校验小活动
        $marketList = Crud::getData('zht_market_list', 1, ['id' => $marketListId, 'is_del' => 1], "market_id");
        if (empty($marketList)) {
            return returnResponse('1002', '活动已结束');
        }
        //校验大活动
        $market = Crud::getData("zht_market", 1, ['id' => $marketList['market_id'], 'market_type' => 1, 'is_del' => 1], 'id');
        if (empty($market)) {
            return returnResponse('1002', '活动已结束');
        }
        $marketListDetailIds = input("detail_ids/a", []);
        if (count($marketListDetailIds) <= 0) {
            return returnResponse('1002', '请选择想参与的时间段');
        }

        Db::startTrans();
        try {
            foreach ($marketListDetailIds as $k => $item) {
                if (count($item) < 2) {
                    throw new Exception('请选择活动');
                }
                if (count($item[1]) <= 0) {
                    throw new Exception('请选择活动时间段');
                }

                $detail = Crud::getData("zht_market_list_detail", 1, ['id' => $item[0], 'market_list_id' => $marketListId, 'is_del' => 1], 'id');
                if (empty($detail)) {
                    throw new Exception('活动已失效');
                }
                foreach ($item[1] as $v) {
                    //查询名额是否剩余
                    $qua = Crud::getData("zht_market_list_detail_time", 1, ['id' => $v, 'is_del' => 1], '(quota - used_quota) as quota,used_quota,quota,start_time,end_time');
                    if (empty($qua)) {
                        throw new Exception('所选时间段异常');
                    }
                    if ($qua <= 0) {
                        throw new Exception('所选时间段报名已结束');
                    }
                    //查询许学员是够已报名
                    $order = Crud::getData("zht_market_order", 1, ['student_id' => $studentId, 'user_id' => $this->userId, 'market_list_detail_id' => $item[0], 'market_list_detail_time_id' => $v, 'is_del' => 1], 'id');
                    if (!empty($order)) {
                        throw new Exception('该学员已参与此次活动');
                    }

                    //创建订单 与 修改活动名额
                    $wheres['id'] = ['=', $v];
                    $wheres['used_quota'] = ['<', $qua['quota']];
                    $update = Crud::setUpdate("zht_market_list_detail_time", $wheres, ['used_quota' => $qua['used_quota'] + 1, 'update_time' => time()]);
                    if (empty($update)) {
                        throw new Exception('修改活动名额失败' . $v);
                    }

                    $add = Crud::setAdd("zht_market_order", ['user_id' => $this->userId, 'student_id' => $studentId, 'contacts' => $contacts, 'phone' => $phone, 'market_id' => $marketList['market_id'], 'market_list_id' => $marketListId, 'market_list_detail_id' => $item[0], 'market_list_detail_time_id' => $v, 'start_time' => $qua['start_time'], 'end_time' => $qua['end_time']]);
                    if (empty($add)) {
                        throw new Exception('创建活动订单失败');
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return returnResponse('1002', $e->getMessage());
        }
        return returnResponse('1000', '报名成功');

    }

    /**
     * @Notes: 获取活动订单
     * @Author: asus
     * @Date: 2020/6/17
     * @Time: 16:11
     * @Interface getMarketOrder
     * @return string
     */
    public function getMarketOrder()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $join = [
            ['yx_zht_market zm', 'zmo.market_id = zm.id', 'left'],
            ['yx_zht_market_list zml', 'zmo.market_list_id = zml.id', 'left'],
            ['yx_zht_market_list_detail zmld', 'zmo.market_list_detail_id = zmld.id', 'left'],
            ['yx_lmport_student ls', 'zmo.student_id = ls.id']
        ];
        $search = input("post.search");
        if (!empty(trim($search))) {
            $where['zmld.name|zml.name'] = ['like', "%" . $search . "%"];
        }
        $status = input('post.status/d', 0);

        if ($status == 1) {
            $where['zmo.start_time'] = ['>', time()];
        } elseif ($status == 2) {
            $where['zmo.start_time'] = ['<=', time()];
            $where['zmo.end_time'] = ['>', time()];
        } elseif ($status == 3) {
            $where['zmo.end_time'] = ['<', time()];
        }

        $where['zmo.user_id'] = ['=', $this->userId];
        $where['zmo.is_del'] = ['=', 1];
        $field = "zmo.id,ABS(zmo.start_time - unix_timestamp()) as time,zmo.signin_type,zmo.signin_time,zmo.start_time,zmo.end_time,zm.name,zml.img,zml.name as small_name,zmld.name as detail_name,zmld.province,zmld.city,zmld.area,zmld.address,zmld.longitude,zmld.latitude,ls.student_name,zmo.market_list_id,zmo.market_list_detail_time_id";
        $result = Crud::getRelationData("zht_market_order", 2, $where, $join, 'zmo', 'zmo.signin_type DESC,time ASC', $field, $page, $pageSize);
        //halt(Db::name("zht_market_order")->getLastSql());
        if (count($result) > 0) {
            foreach ($result as &$item) {
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
                if ($item['start_time'] > time()) {
                    $item['time_status'] = 1;
                }
                if ($item['end_time'] < time()) {
                    $item['time_status'] = 3;
                }
                if ($item['end_time'] > time() && $item['start_time'] < time()) {
                    $item['time_status'] = 2;
                }
                if ($item['signin_type'] == 2) {
                    //未签到时
                    if ($item['end_time'] < strtotime(date('Y-m-d 00:00:00'))) {
                        $item['status'] = 1;
                    }
                    if ($item['start_time'] >= strtotime(date('Y-m-d 00:00:00')) && $item['end_time'] <= strtotime(date('Y-m-d 23:59:59'))) {
                        $item['status'] = 2;
                    }
                    if ($item['start_time'] > strtotime(date('Y-m-d 23:59:59'))) {
                        $item['status'] = 3;
                    }
                    unset($item['signin_time']);
                }
                if ($item['signin_type'] == 1) {
                    $item['status'] = 4;
                    $item['signin_time'] = date("Y-m-d H:i:s", $item['signin_time']);
                }
                unset($item['signin_type']);
                $s = $item['start_time'];
                $e = $item['end_time'];
                //$time = Crud::getData('zht_market_list_detail_time', 1, ["id" => $item['market_list_detail_time_id']], 'start_time,end_time');
                $item['end_time'] = date("Y-m-d", $s) == date("Y-m-d", $e) ? date("H:i", $e) : date("y年m月d日 H:i", $e);
                $item['start_time'] = date("Y-m-d", $s) == date("Y-m-d", $e) ? date("y年m月d日 H:i", $s) : date("y年m月d日 H:i", $s);

            }
        }
        $results = [
            'result' => $result
        ];
        return returnResponse('1000', '', $results);
    }

    public function addHomeSign()
    {
        if (!$id = input('post.id/d')) {
            return returnResponse("1001", '扫码失败！');
        }
        if (!$studentId = input('post.student_id')) {
            return returnResponse("1001", '扫码失败！');
        }
        $where['user_id'] = ['=', $this->userId];
        $where['market_list_detail_id'] = ['=', $id];
        $where['student_id'] = ['=', $studentId];
        $where['start_time'] = ['>=', strtotime(date('Y-m-d 00:00:00'))];
        $where['signin_type'] = ['=', 2];
        $where['is_del'] = ['=', 1];
        // $where['zmldt.end_time'] = ['<=', strtotime(date('Y-m-d 23:59:59'))];
        // $where['zmldt.end_time'] = ['>=', time()];
        $where['end_time'] = ['between', [time(), strtotime(date('Y-m-d 23:59:59'))]];
        $field = "id,signin_type,ABS(start_time - unix_timestamp()) as time,market_id,market_list_detail_id,market_list_id";
        $result = Crud::getData("zht_market_order", 3, $where, $field, 'time ASC');
        //halt(Db::name("zht_market_order")->getLastSql());
        $time = time();
        if (empty($result)) {
            //获取第一条数据
            $where['end_time'] = ['<=', strtotime(date('Y-m-d 23:59:59'))];
            $re = Crud::getData("zht_market_order", 3, $where, 'id,market_id,market_list_detail_id,market_list_id', 'start_time ASC');
            if (empty($re)) {
                return returnResponse("1001", '签到失败');
            }
            $updateRe = Crud::setUpdate("zht_market_order", ['id' => $re['id']], ['update_time' => $time, 'signin_time' => $time, 'signin_type' => 1]);
            if (empty($updateRe)) {
                return returnResponse("1001", '签到失败');
            }
            $marketListId = $re['market_list_id'];
            $market = Crud::getData("zht_market", 1, ['id' => $re['market_id'], 'is_del' => 1], 'name');
            $marketListDetail = Crud::getData("zht_market_list_detail", 1, ['id' => $re['market_list_detail_id'], 'is_del' => 1], 'name');
        } else {
            $updateResult = Crud::setUpdate("zht_market_order", ['id' => $result['id']], ['update_time' => $time, 'signin_time' => $time, 'signin_type' => 1]);
            if (empty($updateResult)) {
                return returnResponse("1001", '签到失败');
            }
            $marketListId = $result['market_list_id'];
            $market = Crud::getData("zht_market", 1, ['id' => $result['market_id'], 'is_del' => 1], 'name');
            $marketListDetail = Crud::getData("zht_market_list_detail", 1, ['id' => $result['market_list_detail_id'], 'is_del' => 1], 'name');

        }
        $studentName = Crud::getData("lmport_student", 1, ['id' => $studentId, 'is_del' => 1], 'student_name');

        return returnResponse("1000", '签到成功', ['student_name' => $studentName['student_name'], 'time' => date("Y/m/d H:i:s", $time), 'marketName' => $market['name'], 'detail_name' => $marketListDetail['name'], 'market_list_id' => $marketListId]);

    }

    public function addOrderSign()
    {
        if (!$orderId = input('post.order_id')) {
            return returnResponse("1001", '扫码失败！');
        }
        if (!$id = input('post.id/d')) {
            return returnResponse("1001", '扫码失败！');
        }
        $time = time();
        $where['user_id'] = ['=', $this->userId];
        $where['id'] = ['=', $orderId];
        $where['market_list_detail_id'] = ['=', $id];
        $where['start_time'] = ['>=', strtotime(date('Y-m-d 00:00:00'))];
        $where['signin_type'] = ['=', 2];
        $where['end_time'] = ['<=', strtotime(date('Y-m-d 23:59:59'))];
        $order = Crud::getData("zht_market_order", 1, $where, 'id,market_id,market_list_detail_id,student_id,market_list_id');
        if (empty($order)) {
            return returnResponse("1001", '您还没有参加这个活动哦，马上报名参加吧！');
        }
        $updateResult = Crud::setUpdate("zht_market_order", ['id' => $order['id']], ['update_time' => $time, 'signin_time' => $time, 'signin_type' => 1]);
        if (empty($updateResult)) {
            return returnResponse("1001", '签到失败');
        }
        $market = Crud::getData("zht_market", 1, ['id' => $order['market_id'], 'is_del' => 1], 'name');
        $marketListDetail = Crud::getData("zht_market_list_detail", 1, ['id' => $order['market_list_detail_id'], 'is_del' => 1], 'name');
        $studentName = Crud::getData("lmport_student", 1, ['id' => $order['student_id'], 'is_del' => 1], 'student_name');
        return returnResponse("1000", '签到成功', ['student_name' => $studentName['student_name'], 'time' => date("Y/m/d H:i:s", $time), 'marketName' => $market['name'], 'detail_name' => $marketListDetail['name'], 'market_list_id' => $order['market_list_id']]);
    }

    public function getSignStudent()
    {
        if (!$id = input('post.id/d')) {
            return returnResponse("1001", '扫码失败！');
        }
        $where['zmo.market_list_detail_id'] = ['=', $id];
        $where['zmo.user_id'] = ['=', $this->userId];
        $where['zmo.start_time'] = ['>=', strtotime(date('Y-m-d 00:00:00'))];
        $where['zmo.signin_type'] = ['=', 2];
        $where['zmo.end_time'] = ['<=', strtotime(date('Y-m-d 23:59:59'))];
        $join = [
            ['yx_lmport_student ls', 'zmo.student_id = ls.id', 'left']
        ];
        $field = "ls.student_name,ls.phone,zmo.student_id";
        $res = Crud::getRelationDataGroup("zht_market_order", $where, $join, 'zmo', 'zmo.id DESC', $field, 'zmo.student_id', 1);
        if (count($res) == 0) {
            $wh['market_list_detail_id'] = ['=', $id];
            $wh['user_id'] = ['=', $this->userId];
            $wh['start_time'] = ['>=', strtotime(date('Y-m-d 00:00:00'))];
            $wh['signin_type'] = ['=', 1];
            $wh['end_time'] = ['<=', strtotime(date('Y-m-d 23:59:59'))];
            $re = Crud::getData("zht_market_order", 1, $wh, 'id');
            if (!empty($re)) {
                return returnResponse('1001', '您已完成今日活动的签到，再看看其他活动吧!');
            }
            return returnResponse('1001', '您还没有参加这个活动哦，马上报名参加吧！');
        }
        return returnResponse('1000', '', $res);
    }
}