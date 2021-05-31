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
use app\lib\exception\UpdateMissException;
use think\Db;

class Export extends Base
{
    //导入学生
    public function LmportStudentList()
    {
        $data = input();
        $account_data = Crud::isUserToken($data['token']);
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
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
//                remove_duplicate($excel_array, $res, $args)
                foreach ($excel_array as $k => $v) {
                    if ($v[1] == null) {
                        continue;
                    }
                    if ($v[0] == null) {
                        $user_name = '';
                    } else {
                        $user_name = $v[0];
                    }

                    if ($v[6] == null) {
                        $birthday = '';
                    } else {
                        $birthday = $v[6];
                    }


                    if ($v[5] == '男') {
                        $sex = 1;
                    } elseif ($v[5] == '女') {
                        $sex = 2;
                    } else {
                        $sex = 3;
                    }

                    //parent_student_relation  1爸爸，2妈妈，3爷爷/外公，4奶奶/外婆，5其他
                    if ($v[2] == '爸爸') {
                        $user_relation = 1;
                    } elseif ($v[2] == '妈妈') {
                        $user_relation = 2;
                    } elseif ($v[2] == '爷爷/外公') {
                        $user_relation = 3;
                    } elseif ($v[2] == '奶奶/外婆') {
                        $user_relation = 4;
                    } elseif ($v[2] == '其他') {
                        $user_relation = 5;
                    } else {
                        $user_relation = 5;
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
                        'user_name' => $user_name,
                        'mem_id' => $account_data['mem_id'],
                        'student_name' => $v[1],
                        'phone' => $v[3],
                        'user_relation' => $user_relation, //关系
                        'create_time' => time(),
                        'sex' => $sex,
                        'birthday' => $birthday,
                        'customer_type' => $customer_type,
                        'school' => $v[8],
                        'class' => $v[9],
                        'province' => $v[10],
                        'city' => $v[11],
                        'area' => $v[12],
                        'address' => $v[13],
                        'community' => $v[14],
                        'introducer' => $v[15],
                        'salesman_id' => $account_data['admin_user_id'],
                        'student_identifier' => $order_num = time() . rand(10, 99),
                    ];
                    //先查询此用户是否存在
                    $student_info = self::isStudent($v[1], $v[3]);
                    if ($student_info) {
                        //查询此机构是否绑定此学员
                        $student_member_data = self::isMemberStudent($student_info['id'], $account_data['mem_id']);
                        if ($student_member_data) {
                            //有值为用户已存(为异常用户)
                            //如果异常数据有此用户
                            $student_unusual_data = self::isStudentUnusual($v[1], $account_data['mem_id'], $v[3]);
                            if ($student_unusual_data) {
                                $Unusual_num++;
                            } else {
                                $student_unusual_data = Crud::setAdd('lmport_student_unusual', $add_data, 1);
                                if ($student_unusual_data) {
                                    $Unusual_num++;
                                }
                            }
                        } else {
                            //添加机构与学生关系  yx_lmport_student_member
                            $add_data['student_id'] = $student_info['id'];
                            $student_member_id = Crud::setAdd('lmport_student_member', $add_data, 2);
                            if (!$student_member_id) {

                            }
                            //添加家长 yx_parent  $user_relation
                            $parent_id = self::addParent($v[0], $student_info['id'], $account_data['mem_id'], $v[3], $v[4], $user_relation, $student_member_id);
                            if (!$parent_id) {

                            }
                            //添加家长与学生关系
                            $student_parent_relation_data = self::addParentRelation($parent_id, $student_info['id'], $account_data['mem_id'], $user_relation, $student_member_id);
                            if ($student_parent_relation_data) {
                                $Success_num++;
                            }
                        }
                    } else {
                        $add_lmport_student = [
                            'user_name' => $user_name,
                            'student_name' => $v[1],
                            'phone' => $v[3],
                            'sex' => $sex,
                            'birthday' => $birthday,
                            'school' => $v[8],
                            'class' => $v[9],
                        ];
                        $student_id = Crud::setAdd('lmport_student', $add_lmport_student, 2);
                        if (!$student_id) {

                        }
                        //添加机构与学生关系  yx_lmport_student_member
                        $add_data['salesman_id'] = $account_data['admin_user_id'];
                        $add_data['student_id'] = $student_id;
                        $student_member_id = Crud::setAdd('lmport_student_member', $add_data, 2);
                        if (!$student_member_id) {

                        }
                        //添加家长 yx_parent
                        $parent_id = self::addParent($v[0], $student_id, $account_data['mem_id'], $v[3], $v[4], $user_relation, $student_member_id);
                        if (!$parent_id) {

                        }

                        //添加家长与学生关系
                        $student_parent_relation_data = self::addParentRelation($parent_id, $student_id, $account_data['mem_id'], $user_relation, $student_member_id);
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
        } else {
            throw new ISUserMissException();
        }
    }

    //先查询此用户是否存在
    public static function isStudent($student_name, $phone)
    {
        $where = [
            'student_name' => $student_name,
            'phone' => $phone,
            'is_del' => 1,
        ];
//        $student_info = Crud::getData('lmport_student', 1, $where, 'id,mem_id');
        $student_info = Crud::getData('lmport_student', 1, $where, 'id');
        if ($student_info) {
            return $student_info;
        }
    }

    //验证此学生是否绑定机构
    public static function isMemberStudent($student_id, $mem_id)
    {
        $where_student_member = [
            'student_id' => $student_id,
            'mem_id' => $mem_id,
            'is_del' => 1,
        ];
        $student_member_data = Crud::getData('lmport_student_member', 1, $where_student_member, 'id');
        if ($student_member_data) {
            return $student_member_data;
        }
    }

    //验证异常库是否有此学生
    public static function isStudentUnusual($student_name, $mem_id, $phone)
    {
        $where_unusual = [
            'student_name' => $student_name,
            'mem_id' => $mem_id,
            'phone' => $phone,
            'is_del' => 1,
        ];
        $student_unusual_data = Crud::getData('lmport_student_unusual', 1, $where_unusual, $field = 'id');
        if ($student_unusual_data) {
            return $student_unusual_data;
        }
    }


    //添加家长
    public static function addParent($parent_name, $student_id, $mem_id, $phone, $we_chat, $user_relation, $student_member_id)
    {
        $data_parent = [
            'parent_name' => $parent_name,
            'student_id' => $student_id,
            'mem_id' => $mem_id,
            'phone' => $phone,
            'we_chat' => $we_chat,
            'user_relation' => $user_relation,
            'student_member_id' => $student_member_id,
        ];
        $parent_id = Crud::setAdd('parent', $data_parent, 2);
        if ($parent_id) {
            return $parent_id;
        }
    }

    //添加家长关系
    public static function addParentRelation($parent_id, $student_id, $mem_id, $user_relation, $student_member_id)
    {
        $data_student_parent_relation = [
            'parent_id' => $parent_id,
            'student_id' => $student_id,
            'mem_id' => $mem_id,
            'user_relation' => $user_relation,
            'student_member_id' => $student_member_id,
        ];
        $student_parent_relation_data = Crud::setAdd('student_parent_relation', $data_student_parent_relation);
        if ($student_parent_relation_data) {
            return $student_parent_relation_data;
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
//            'ls.is_del' => $data['del_type'],
//            'p.is_del' => 1,
            'ls.mem_id' => $data['mem_id'],
            'ls.student_status' => $data['student_status'],
        ];
        if (isset($data['del_type']) && !empty($data['del_type'])) {
            $where['ls.is_del'] = $data['del_type'];
        } else {
            $where['ls.is_del'] = 1;
        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_parent p', 'ls.id = p.student_member_id', 'left'], //家长信息
//            ['yx_parent p', 'ls.id = p.student_id', 'left'], //机构信息
        ];
        $alias = 'ls';
        $list = Crud::getRelationData($table, $type = 2, $where, $join, $alias, '', 'ls.*,p.we_chat,p.qq,p.email,p.company,p.parent_name,p.user_relation', 1, 10000);
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
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $val['parent_name']);
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
            } else {
                $customer_type = '无';
            }

            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $customer_type); //客户来源
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $val['school']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $val['class']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $val['province']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $val['city']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, $val['area']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $i, $val['address']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $i, $val['community']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $i, $val['introducer']);
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

    //导出删除数据
    public function exportDelStudentList()
    {
        $data = input();
        $account_data = Crud::isUserToken($data['token']);
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            $where = [
                'm.status' => 1,
                'm.is_del' => 1,
                'lsm.is_del' => 2,
                'lsm.student_status' => 1
            ];
        } else {
            throw new ISUserMissException();
        }
        if (empty($mem_id)) {
            $where['lsm.mem_id'] = $account_data['mem_id'];
        } else {
            $where['lsm.mem_id'] = $mem_id;
        }
//        (isset($student_id) && !empty($student_id)) && $where['lsm.student_id'] = $student_id;
//        (isset($mem_data['user_id']) && !empty($mem_data['user_id'])) && $where['lsm.salesman_id'] = $mem_data['user_id'];

        //名称搜索 手机号
        $whereOr = [];
//        (isset($student_name) && !empty($student_name)) && $where['lsm.student_name'] = ['like', '%' . $student_name . '%'];
//        (isset($student_name) && !empty($student_name)) && $whereOr['lsm.phone'] = $student_name;
//        (isset($mem_id) && !empty($mem_id)) && $where['lsm.mem_id'] = $mem_id;
//        (isset($return_visit_type) && !empty($return_visit_type)) && $where['lsm.return_type'] = $return_visit_type;
//        //时间筛选
//        if (isset($time) && !empty($time)) {
//            $start_time = $time[0] / 1000;
//            $end_time = $time[1] / 1000;
//            $where['lm.create_time'] = ['between', [$start_time, $end_time]];
//        }

        $table = 'lmport_student_member';
        $join = [
            ['yx_member m', 'lsm.mem_id = m.uid', 'left'], //机构信息
            //['yx_lmport_student lm', 'lsm.student_id = lm.id', 'left'], //导入学生信息
            ['yx_salesman sa', 'lsm.salesman_id = sa.id', 'left'], //业务员信息
        ];
        $alias = 'lsm';
        $list = Crud::getRelationDataWhereOr($table, 2, $where, $whereOr, $join, $alias, 'lsm.create_time desc', 'lsm.*,m.cname,m.province mprovince,m.area marea,m.city mcity,m.address msaddress,sa.user_name salesman_name', 1, 1000000);
        vendor('PHPExcel.Classes.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '学生名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '年龄');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '学生姓别');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '联系电话');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '机构名称');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '机构地址');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '入池时间');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '添加时间');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '回访信息');
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

        //设置单元格为文本
        foreach ($list as $k => $val) {
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $val['student_name']);
            if (!empty($val['birthday'])) {
                $year_age = CalculationAge($val['birthday']);
            }
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $year_age);
            if ($val['sex'] == 1) {
                $sex = '男';
            } else if ($val['sex'] == 2) {
                $sex = '女';
            } else {
                $sex = '未知';
            }
            $maddress = $val['mprovince'] . $val['mcity'] . $val['marea'] . $val['msaddress'];
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $sex);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $val['phone']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $val['cname']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $maddress);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, date('Y-m-d H:i:s', $val['get_into_time']));
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date('Y-m-d H:i:s', $val['create_time']));
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $val['return_visit_content']);

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
        header("Content-Disposition:attachment;filename=删除学生信息.xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    //下载模板
    function downloadExc($readBuffer = 1024, $allowExt = ['jpeg', 'jpg', 'peg', 'gif', 'zip', 'rar', 'txt', 'xlsx'])
    {
        $filePath = ROOT_PATH . "public/yxmn.xlsx";
        //检测下载文件是否存在 并且可读
        if (!is_file($filePath) && !is_readable($filePath)) {
            return false;
        }
        //检测文件类型是否允许下载
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowExt)) {
            return false;
        }
        //设置头信息
        //声明浏览器输出的是字节流
        header('Content-Type: application/octet-stream');
        //声明浏览器返回大小是按字节进行计算
        header('Accept-Ranges:bytes');
        //告诉浏览器文件的总大小
        $fileSize = filesize($filePath);//坑 filesize 如果超过2G 低版本php会返回负数
        header('Content-Length:' . $fileSize); //注意是'Content-Length:' 非Accept-Length
        //声明下载文件的名称
        header('Content-Disposition:attachment;filename=' . basename($filePath));//声明作为附件处理和下载后文件的名称
        //获取文件内容
        $handle = fopen($filePath, 'rb');//二进制文件用‘rb’模式读取
        while (!feof($handle)) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
            echo fread($handle, $readBuffer);
        }
        fclose($handle);//关闭文件句柄
        exit;

    }

    //扫码进入
    public static function sweepCodeOrder($order_num = '')
    {
        $where = [
            'o.order_num' => $order_num,
            's.is_del' => 1,
            'o.is_del' => 1
        ];
        $join = [
            ['yx_lmport_student_member s', 'o.student_member_id =s.id ', 'left'],  //学生信息
            ['yx_zht_course c', 'o.course_id =c.id ', 'left'],  //课程
        ];
        $alias = 'o';
        $table = 'zht_order';
        $order_data = Crud::getRelationData($table, $type = 1, $where, $join, $alias, $order = 'o.create_time desc', $field = 'o.*,c.course_img,s.student_name,s.phone');
        if ($order_data) {  //1待付款，2待排课，3上课中，4已毕业，5已休学，6已退款 7已取消
            if ($order_data['status'] != 1) {
                return jsonResponse('2000', '此订单已支付');
            }
            return jsonResponseSuccess($order_data);
        } else {
            throw new NothingMissException();
        }

    }

    //0无支付
    public static function payment($order_id)
    {
        $where_order = [
            'order_id' => $order_id,
            'status' => 1,
            'is_del' => 1,
        ];
        $order_data = Crud::getData('zht_order', 1, $where_order, 'order_num,price,student_member_id');
        if ($order_data) {
            //更改小订单
            Db::startTrans();
            $order_update = Crud::setUpdate('zht_order', ['order_id' => $order_id], ['status' => 2]);
            if (!$order_update) {
                Db::rollback();
                throw new UpdateMissException();
            }
            //更改大订单
            $order_num_update = Crud::setUpdate('zht_order_num', ['order_num' => $order_data['order_num']], ['status' => 2]);
            if (!$order_num_update) {
                Db::rollback();
                throw new UpdateMissException();
            }
            $lmport_student_member = Crud::getData('lmport_student_member', 1, ['id' => $order_data['student_member_id']], 'student_status');
            if ($lmport_student_member && $lmport_student_member['student_status'] <> 3) {
                //更改学生类型 yx_lmport_student_member 改为在读学生
                $lmport_student_member_update = Crud::setUpdate('lmport_student_member', ['id' => $order_data['student_member_id']], ['student_status' => 3]);
                if (!$lmport_student_member_update) {
                    Db::rollback();
                    throw new UpdateMissException();
                }
            }
            if ($order_num_update) {
                Db::commit();
                return jsonResponseSuccess($order_num_update);
            }
        }

    }


}