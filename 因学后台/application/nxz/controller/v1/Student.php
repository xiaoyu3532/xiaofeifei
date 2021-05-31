<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/6 0006
 * Time: 13:26
 */

namespace app\nxz\controller\v1;

use app\common\model\Crud;
use app\common\controller\PhoneCode;
use think\Cache;

class Student extends Base
{
    //获取用户孩子信息
    //student_type 1获取一条，2获取列表
    public static function getStudent()
    {
        $data = input();
        $where = [
            'is_del' => 1,
            'uid' => $data['user_id']
        ];
        $table = request()->controller();
        $where['recom'] = 1;
        $info = Crud::getData($table, 1, $where, $field = 'id,uid user_id,name,sex,age,phone,recom');
        if (!$info) {
            $info = Crud::getData($table, 1, ['is_del' => 1, 'uid' => $data['user_id']], $field = 'id,uid user_id,name,sex,age,phone,recom');
            if ($info) {
                return jsonResponseSuccess($info);
            } else {
                $info = [
                    'name' => '',
                    'age' => '',
                    'phone' => '',
                ];
                return jsonResponse('2000', '无用户信息', $info);
            }
        } else {
            if (empty($info['age'])) {
                $info['age'] = '-';
            }
            return jsonResponseSuccess($info);
        }

    }

    //添加用户学生信息
    public static function setStudent()
    {
        $data = input();
        $data1 = [
            'uid' => $data['user_id'],
            'name' => $data['name'],
//            'sex' => $data['sex'],
            'age' => $data['age'],
            'phone' => $data['phone'],
        ];
        isset($data['recom']) && !empty($data['recom']) && $data1['recom'] = $data['recom'];
        $table = request()->controller();
        $info = Crud::setAdd($table, $data1);
        if ($info) {
            $where = [
                'id' => $info
            ];
            $info = Crud::getData($table, 1, $where, $field = 'id,uid,name,sex,age,phone');
            return jsonResponseSuccess($info);
        } else {
            return jsonResponse('3000', '获取失败');
        }
    }

    //用户学生信息编辑
    public static function upStudent()
    {
        $data = input();
        $table = request()->controller();
        if (isset($data['user_id']) && !empty($data['user_id'])) {
            $data['uid'] = $data['user_id'];
            unset($data['user_id']);
        }
        if (empty($data['id'])) {
            $info = Crud::setAdd($table, $data);
            if($info){
                $msg = '添加成功';
            }else{
                $msg = '添加失败';
            }

        } else {
            $where1 = [
                'id' => $data['id']
            ];

            unset($data['id']);
            $info = Crud::setUpdate($table, $where1, $data);
            if($info){
                $msg = '修改成功';
            }else{
                $msg = '修改失败';
            }
        }

        if ($info) {
            return jsonResponse('1000', $msg, $info);
        } else {
            return jsonResponse('3000', $msg);
        }
    }

    //选择学生信息返回
    public static function chStudent()
    {
        $data = input();
        $where = [
            'id' => $data['id'],
            'is_del' => 1,
            'uid' => $data['user_id'],
        ];
        $table = request()->controller();
        $info = Crud::getData($table, 1, $where, $field = 'id,uid,name,sex,age,phone,recom');
        if (!$info) {
            return jsonResponse('2000', '无用户信息');
        } else {
            return jsonResponse('1000', '获取成功', $info);
        }

    }

    //删除学生信息
    public static function delStudent()
    {
        $data = input();
        $where = [
            'id' => $data['id'],
            'uid' => $data['user_id'],
        ];
        $updata = [
            'is_del' => 2
        ];
        $table = request()->controller();
        $info = Crud::setUpdate($table, $where, $updata);
        if (!$info) {
            return jsonResponse('2000', '删除失败');
        } else {
            return jsonResponse('1000', '删除成功', $info);
        }
    }
}