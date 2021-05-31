<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/6 0006
 * Time: 13:26
 */

namespace app\xcx\controller\v1;

use app\common\model\Crud;
use app\common\controller\PhoneCode;
use think\Cache;

class Student extends BaseController
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
        if ($data['student_type'] == 1) {
            $type = 1;
            $where['recom'] = 1;
            $info = Crud::getData($table, $type, $where, $field = 'id,uid,name,sex,age,phone,recom');
            if(!$info){
                $info = Crud::getData($table, 1, ['is_del'=>1,'uid'=>$data['user_id']], $field = 'id,uid,name,sex,age,phone,recom');
                if($info){
                    return jsonResponseSuccess($info);
                }else{
                    return jsonResponse('2000', '无用户信息');
                }
            }
        } elseif ($data['student_type'] == 2) {
            $type = 2;
            $info = Crud::getData($table, $type, $where, $field = 'id,uid,name,sex,age,phone,recom');
        }
        if (!$info) {
            return jsonResponse('2000', '无用户信息');
        } else {
            return jsonResponseSuccess($info);
        }

    }

    //添加用户学生信息
    public static function setStudent()
    {
        $data = input();
        $code1 = str_replace(" ", '', $data['code']);
        $code = Cache::get($data['phone']);
        if ($code1 != 3536) { //测试验证码 3536
            if ($code != $code1) {
                return jsonResponse('2000', '验证码错误');
            }
        }
        $data1 = [
            'uid' => $data['user_id'],
            'name' => $data['name'],
            'sex' => $data['sex'],
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
            return jsonResponse('1000', '成功获取活动图', $info);
        } else {
            return jsonResponse('3000', '获取失败');
        }
    }

    //用户学生信息编辑
    public static function upStudent()
    {
        $data = input();
        $code1 = str_replace(" ", '', $data['code']);
        $code = Cache::get($data['phone']);
        if ($code1 != 3536) { //测试验证码 3536
            if ($code != $code1) {
                return jsonResponse('2000', '验证码错误');
            }
        }
        $table = request()->controller();
        $where = [
            'uid' => $data['user_id']
        ];
        if (isset($data['recom']) && !empty($data['recom'])) {
            if ($data['recom'] == 1) {
                //将本用户所有默认改为2
                $updata = [
                    'recom'=>2
                ];
                $uprecom = Crud::setUpdate($table, $where, $updata);
                if(!$uprecom){
                    return jsonResponse('3000', '修改状态失败');
                }
            }
        }
        $where1 = [
            'id'=>$data['id']
        ];
        unset($data['user_id']);
        unset($data['code']);
        unset($data['id']);
        $info = Crud::setUpdate($table, $where1, $data);
        if ($info) {
            return jsonResponse('1000', '修改成功', $info);
        } else {
            return jsonResponse('3000', '修改失败');
        }
    }

    //选择学生信息返回
    public static function chStudent(){
        $data = input();
        $where =[
            'id'=>$data['id'],
            'is_del'=>1,
            'uid'=>$data['user_id'],
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
    public static function delStudent(){
        $data = input();
        $where =[
            'id'=>$data['id'],
            'uid'=>$data['user_id'],
        ];
        $updata = [
            'is_del'=>2
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