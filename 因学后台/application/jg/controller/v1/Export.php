<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/9 0009
 * Time: 13:05
 */

namespace app\jg\controller\v1;
use app\common\controller\Base;
use app\common\model\Crud;

class Export extends Base
{
    //导出订单
    public function exportjgOrder($token, $name = '', $order_id = '', $sname = '', $status = '', $cou_status = '', $time = '', $cname = '')
    {
        $token_data = Crud::isUserToken($token);
        $where = [
            'o.is_del' => 1,
            'o.mid' => $token_data['mem_id'],
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
        if (isset($status) && !empty($status)) {//1未支付，2已支付，3申请退款，4已退款，5课程开始，6课程结束，7课程失败，8免费
            $where['o.status'] = $status;
        } else {
            $where['o.status'] = ['in', [2, 5, 6, 8]];
        }
        (isset($cou_status) && !empty($cou_status)) && $where['o.cou_status'] = $cou_status; //1普通课程，2体验课程，3活动课程，4秒杀课程
        $join = [
//            ['yx_course c', 'o.cid = c.id', 'left'],  //课程
            ['yx_student s', 'o.student_id =s.id ', 'left'],  //学生信息
            ['yx_user u', 'o.uid =u.id ', 'left'],  //用户信息
            ['yx_teacher t', 'o.teacher_id =t.id ', 'left'],  //老师
            ['yx_member m', 'o.mid =m.uid ', 'left'],  //机构
            ['yx_community_name cn', 'o.community_id =cn.id ', 'left'],  //社区
        ];
        $alias = 'o';
        $table = 'order';
        $list = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.id,o.order_id,cn.name cnname,o.order_num,o.name,o.status,o.price,o.cou_status,s.name sname,s.sex,s.age,s.phone,u.img,o.create_time,o.start_time,o.c_num,t.name tname,o.classroom_id,m.cname,o.sname osname,o.sex osex,o.age oage,o.phone ophone', 1, 10000);
        foreach ($list as $k=>$v){
            if(empty($v['sname'])&&empty($v['age'])){
                $list[$k]['sname'] = $v['osname'];
                $list[$k]['sex'] = $v['osex'];
                $list[$k]['phone'] = $v['ophone'];
                $list[$k]['age'] = $v['oage'];
            }
        }
        vendor('PHPExcel.Classes.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '订单号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '课程名');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '学生名称');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '性别');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '学生年龄');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '社区名称');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '时间');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);

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
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $val['cnname']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date('Y-m-d H:i:s', $val['create_time']));
        }
        // 1.保存至本地Excel表格
        $objWriter->save('xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=订单.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }
}