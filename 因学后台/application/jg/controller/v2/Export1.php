<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/8 0008
 * Time: 20:20
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\common\controller\Base;

class Export extends Base
{
    //导入学生
    public function LmportStudentList()
    {
        $data = input();
        $mem_data = Crud::isUserToken($data['token']);
        if (empty($mem_data)) {
            throw new ISUserMissException();
        }

        vendor('PHPExcelguide.Classes.PHPExcel.IOFactory'); //导入PHPExcel文件中的IOFactory.php类
        $file = request()->file('file');//获取文件，file是请求的参数名
        $info = $file->move(ROOT_PATH . 'public' . DS . 'excel');//move将文件移动到项目文件的xxx
        if ($info) {
            $excel_path = $info->getSaveName();  //获取上传文件名
//          $excel_suffix = $info->getExtension(); //获取上传文件后缀
            $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $excel_path;   //上传文件的地址
            $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
//            $arr = reset($excel_array); //获取字段名
            unset($excel_array[0]); //删除字段名，剩下的都是存储到数据库的数据了！！
            unset($excel_array[1]); //删除字段名，剩下的都是存储到数据库的数据了！！
            if (empty($excel_array)) {
                throw new NothingMissException();
            }
            $Success_num = 0; //成功条数
            $Unusual_num = 0; //异常条数
            foreach ($excel_array as $k => $v) {
                if ($v[1] == null) {
                    continue;
                }
                //先查询此用户是否存在
                $where = [
                    'ls.student_name' => $v[1],
                    'ls.phone' => $v[3],
                    'ls.is_del' => 1,
                    'lm.mem_id' => $mem_data['mem_id'],
                    'lm.is_del' => 1,
                ];
                $join = [
                    ['yx_lmport_student_member lm', 'ls.id = lm.student_id', 'left'], //机构信息
                ];
                $table = 'lmport_student';
                $alias = 'ls';
                $info = Crud::getRelationData($table, $type = 1, $where, $join, $alias, '', 'ls.id');

                if ($v[5] == '男') {
                    $sex = 1;
                } elseif ($v[5] == '女') {
                    $sex = 2;
                } else {
                    $sex = 3;
                }

                //parent_student_relation  1爸爸，2妈妈，3爷爷/外公，4奶奶/外婆，5其他
                if ($v[2] == '爸爸') {
                    $parent_student_relation = 1;
                } elseif ($v[2] == '妈妈') {
                    $parent_student_relation = 2;
                } elseif ($v[2] == '爷爷/外公') {
                    $parent_student_relation = 3;
                } elseif ($v[2] == '奶奶/外婆') {
                    $parent_student_relation = 4;
                } elseif ($v[2] == '其他') {
                    $parent_student_relation = 5;
                } else {
                    $parent_student_relation = 5;
                }
                //customer_type  1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
                if ($v[7] == '线下活动') {
                    $customer_type = 1;
                } elseif ($v[7] == '转介绍') {
                    $customer_type = 2;
                } elseif ($v[7] == '自主上门') {
                    $customer_type = 3;
                } elseif ($v[7] == '网络平台') {
                    $customer_type = 4;
                } elseif ($v[7] == '其他渠道') {
                    $customer_type = 5;
                }
                $add_data = [
                    'user_name' => $v[0],
                    'mem_id' => $mem_data['mem_id'],
                    'student_name' => $v[1],
                    'phone' => $v[3],
                    'user_relation' => $parent_student_relation, //关系
                    'create_time' => time(),
                    'sex' => $sex,
                    'birthday' => $v[6],
                    'customer_type' => $customer_type,
                    'school' => $v[8],
                    'class' => $v[9],
                    'province' => $v[10],
                    'city' => $v[11],
                    'area' => $v[12],
                    'address' => $v[13],
                    'community' => $v[14],
                ];

                if ($info) {
                    //有值为用户已存(为异常用户)
                    //如果异常数据有此用户
                    $where_unusual = [
                        'student_name' => $v[1],
                        'mem_id' => $mem_data['mem_id'],
                        'phone' => $v[3],
                        'is_del' => 1,
                    ];
                    $unusual_data = Crud::getData('lmport_student_unusual', 1, $where_unusual, $field = 'id');
                    if ($unusual_data) {
                        $Unusual_num++;
                    } else {
                        $add_unusual_data = [
                            'user_name' => $v[0],
                            'student_name' => $v[1],
                            'phone' => $v[3],
                            'user_relation' => $parent_student_relation, //关系
                            'create_time' => time(),
                            'sex' => $sex,
                            'birthday' => $v[6],
                            'customer_type' => $customer_type,
                            'school' => $v[8],
                            'class' => $v[9],
                            'province' => $v[10],
                            'city' => $v[11],
                            'area' => $v[12],
                            'address' => $v[13],
                            'community' => $v[14],
                            'mem_id' => $mem_data['mem_id'],
                        ];
                        $add_info = Crud::setAdd('lmport_student_unusual', $add_unusual_data, 1);
                        if ($add_info) {
                            $Unusual_num++;
                        }
                    }
                } else {
                    //添加学生
                    $student_id = Crud::setAdd($table, $add_data, 2);
                    if (!$student_id) {

                    }
                    //添加机构与学生关系  yx_lmport_student_member
                    $data_student_member = [
                        'mem_id' => $mem_data['mem_id'],
                        'student_id' => $student_id,
                        'salesman_id' => $mem_data['user_id'],
                    ];
                    $lmport_student_member = Crud::setAdd('lmport_student_member', $data_student_member);
                    if (!$lmport_student_member) {

                    }

                    //添加家长 yx_parent
                    $data_parent = [
                        'parent_name' => $v[0],
                        'student_id' => $student_id,
                        'mem_id' => $mem_data['mem_id'],
                        'phone' => $v[3],
                        'we_chat' => $v[4],
                        'parent_student_relation' => $parent_student_relation,
                    ];
                    $parent_id = Crud::setAdd('parent', $data_parent, 2);
                    if (!$parent_id) {

                    }
                    $data_student_parent_relation = [
                        'parent_id' => $parent_id,
                        'student_id' => $student_id,
                        'mem_id' => $mem_data['mem_id'],
                        'parent_student_relation' => $parent_student_relation,
                    ];
                    //添加家长与学生关系  yx_student_parent_relation
                    $student_parent_relation_data = Crud::setAdd('student_parent_relation', $data_student_parent_relation);
                    if ($student_parent_relation_data) {
                        $Success_num++;
                    }
                }
            }
            $data_info = [
                'Success_num' => $Success_num,
                'Unusual_num' => $Unusual_num,
                'Sum_num' => $Success_num + $Unusual_num,
            ];
            return jsonResponseSuccess($data_info);
        }
    }

    //导出数据
    public function exportStudentList()
    {
        $data = input();
//        $account_data = self::isuserData();
        $account_data = Crud::isUserToken($data['token']);
        if (!isset($data['mem_id']) || empty($data['mem_id'])) {
            $data['mem_id'] = $account_data['mem_id'];
        }

        $where = [
            'ls.is_del' => 1,
            'p.is_del' => 1,
            'ls.mem_id' => $data['mem_id']
        ];
        (isset($data['type']) && !empty($data['type'])) && $where['ls.type'] = $data['type'];

        $table = 'lmport_student';
        $join = [
            ['yx_parent p', 'ls.id = p.student_id', 'left'], //机构信息
//            ['yx_parent p', 'ls.id = p.student_id', 'left'], //机构信息
        ];
        $alias = 'ls';
        $list = Crud::getRelationData($table, $type = 2, $where, $join, $alias, '', 'ls.*,p.we_chat,p.qq,p.email,p.company', 1, 10000);

        vendor('PHPExcel.Classes.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '家长名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '学生名称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '关系');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '联系电话1');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '微信号');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '学生姓别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '学生出生日期');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '客户来源');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '在读学校');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', '在校班级');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', '省');
        $objPHPExcel->getActiveSheet()->setCellValue('L1', '市');
        $objPHPExcel->getActiveSheet()->setCellValue('M1', '区');
        $objPHPExcel->getActiveSheet()->setCellValue('N1', '详细地址');
        $objPHPExcel->getActiveSheet()->setCellValue('O1', '社区');
        $objPHPExcel->getActiveSheet()->setCellValue('P1', '备注');
        $objPHPExcel->getActiveSheet()->setCellValue('Q1', '时间');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);

        //设置单元格为文本
        foreach ($list as $k => $val) {
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $val['user_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $val['student_name']);
            if ($val['user_relation'] == 1) {  //用户关系1爸爸，2妈妈，3爷爷/外公，4奶奶/外婆，5其他
                $user_relation = '爸爸';
            } else if ($val['user_relation'] == 2) {
                $user_relation = '妈妈';
            } else if ($val['user_relation'] == 3) {
                $user_relation = '爷爷/外公';
            } else if ($val['user_relation'] == 4) {
                $user_relation = '奶奶/外婆';
            } else if ($val['user_relation'] == 5) {
                $user_relation = '其他';
            } else {
                $user_relation = '其他';
            }


            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $user_relation);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $val['phone']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $val['we_chat']);
            if ($val['sex'] == 1) {
                $sex = '男';
            } else if ($val['sex'] == 2) {
                $sex = '女';
            } else {
                $sex = '未知';
            }
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $sex);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $val['birthday']);

            if ($val['customer_type'] == 1) {  //1线下活动，2转介绍，3自主上门，4网络平台，5其他渠道
                $customer_type = '线下活动';
            } else if ($val['customer_type'] == 2) {
                $customer_type = '转介绍';
            } else if ($val['customer_type'] == 3) {
                $customer_type = '自主上门';
            } else if ($val['customer_type'] == 4) {
                $customer_type = '网络平台';
            } else if ($val['customer_type'] == 5) {
                $customer_type = '其他渠道';
            }

            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $customer_type); //客户来源
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $val['school']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $val['class']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $val['province']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $val['city']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, $val['area']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, $val['address']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, $val['community']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $i, '');
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $i, date('Y-m-d H:i:s', $val['create_time']));
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
        header("Content-Disposition:attachment;filename=学生信息.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

}