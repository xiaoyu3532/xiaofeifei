<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/17 0017
 * Time: 17:47
 */

namespace app\pc\controller\v1;

use app\common\model\Crud;
use app\lib\exception\CourseMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;


class Order extends BaseController
{
    //获取大订单列表
    public static function getpcOrderNumList($page = '1', $sname = '', $status = '', $time = '')
    {

        $where = [
            'o.is_del' => 1,
        ];
        if ((isset($time) && !empty($time))) {
            $start_time = strtotime($time[0]);
            $end_time = strtotime($time[1]);
            $where['o.create_time'] = ['between ', [$start_time, $end_time]];
        }
        (isset($sname) && !empty($sname)) && $where['s.name'] = ['like', '%' . $sname . '%']; //学生名查询
        (isset($order_id) && !empty($order_id)) && $where['o.order_id'] = ['like', '%' . $order_id . '%']; //订单号查询
        (isset($order_id) && !empty($order_id)) && $where['c.status'] = $status; //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
        $join = [
//            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
            ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
        ];
        $alias = 'o';
        $table = request()->controller();
        $order_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,o.create_time', $page);

        if ($order_data) {
//            foreach ($order_data as $k => $v) {
//                if (!empty($v['img'])) {
//                    $imgs = unserialize($v['img']);
//                    $cname_data[$k]['img'] = $imgs;
//                }
//            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = '*');
            $info_data = [
                'info' => $order_data,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取小订单列表
    public static function getpcOrderList($page = '1', $name = '', $order_id = '', $sname = '', $status = '', $cou_status = '', $time = '', $cname = '')
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] != 6) { //6为总平台
            throw new ISUserMissException();
        }
        $where = [
            'o.is_del' => 1,
            'o.status' => ['in', [2, 5, 6, 8]], //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
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
            ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //老师
            ['yx_member m', 'o.mid =m.uid ', 'left'],  //机构
        ];
        $alias = 'o';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.id,o.order_id,o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,o.create_time,o.start_time,o.c_num,t.name tname,o.classroom_id,m.cname,o.sname osname,o.sex osex,o.age oage,o.phone ophone', $page, 8);
        if ($cname_data) {
            foreach ($cname_data as $k => $v) { //1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
                if ($v['cou_status'] == 1 || $v['cou_status'] == 2 || $v['cou_status'] == 4) {
                    $clname = Crud::getData('classroom', 1, ['id' => $v['classroom_id']], 'name');
                    $cname_data[$k]['clname'] = $clname['name'];
                } elseif ($v['cou_status'] == 3) {
                    $clname = Crud::getData('community_classroom', 1, ['id' => $v['classroom_id']], 'name');
                    $cname_data[$k]['clname'] = $clname['name'];
                } elseif ($v['cou_status'] == 5) {
                    $clname['name'] = Crud::getData('synthetical_classroom', 1, ['id' => $v['classroom_id']], 'name');
                    $cname_data[$k]['clname'] = $clname['name'];;
                }
                if(empty($v['sname'])&&empty($v['age'])){
                    $cname_data[$k]['sname'] = $v['osname'];
                    $cname_data[$k]['sex'] = $v['osex'];
                    $cname_data[$k]['phone'] = $v['ophone'];
                    $cname_data[$k]['age'] = $v['oage'];
                }
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


    //修改小订单状态
    public static function setpcOrderStatus($order_id, $status)
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
    public static function delOrder($order_id)
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


    //订单详情
    public static function getpcOrderdetails($order_id)
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 6) { //1用户，2机构
            $where = [
                'o.is_del' => 1,
//                'o.mid' => $user_data['mem_id'],
                'o.id' => $order_id,
            ];
        }
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


            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
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

    //导出订单
    public function exportjgOrder($page = '1', $name = '', $order_id = '', $sname = '', $status = '', $cou_status = '', $time = '', $cname = '')
    {
        $where = [
            'o.is_del' => 1
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
            ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //老师
            ['yx_member m', 'o.mid =m.uid ', 'left'],  //机构
        ];
        $alias = 'o';
        $table = request()->controller();
        $list = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.id,o.order_id,o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,o.create_time,o.start_time,o.c_num,t.name tname,o.classroom_id,m.cname', 1, 10000);


        vendor('PHPExcel.Classes.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '订单号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '课程名');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '学生名称');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '性别');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '学生年龄');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '时间');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        //设置单元格为文本
        foreach ($list as $k => $val) {
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $val['order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $val['sname']);

            if ($val['sex'] == 1) {
                $sex = '男';
            } else if ($val['sex'] == 2) {
                $sex = '女';
            } else {
                $sex = '未知';
            }
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $sex);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $val['age']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $val['phone']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, date('Y-m-d H:i:s', $val['create_time']));
        }
        // 1.保存至本地Excel表格
        $objWriter->save("订单表.xls");
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename='订单表.xls'");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    //获取学生订单详情
    public static function getpcUserOrderdetails($user_id, $page = 1)
    {
        $user_data = self::isuserData();
        if ($user_data['type'] == 6) { //1用户，2机构
            $where = [
                'o.is_del' => 1,
                'o.uid' => $user_id,
                'o.status' => ['in', [2, 5, 6, 8]],  //1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费，9支付中，10拒绝退款
            ];
        }
        $join = [
            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
//            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
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