<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/19 0019
 * Time: 16:08
 */

namespace app\jg\controller\v1;

use think\Controller;
use app\lib\exception\ISUserMissException;
use app\common\model\Crud;
use think\Request;

class BaseController extends Controller
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
        parent::__construct();
        self::isuserData();//验证用户信息

    }

    //验证用户信息（后期加Token）
    public static function isuserData()
    {
        $token = Request::instance()->header('Authorization');
        if (empty($token)) {
            throw new ISUserMissException();
        }
        $user_data = Crud::isUserToken($token, 2);
        if (!$user_data) {
            throw new ISUserMissException();
        } else {
            return $user_data;
        }
    }

    //获取用户名
    public static function getUsername()
    {
        $token = Request::instance()->header('Authorization');
        $where = [
            'is_del' => 1,
            'token' => $token,
        ];
        $table = 'login_account';
        $user_data = Crud::getData($table, $type = 1, $where, $field = 'username');
        if (!$user_data) {
            throw new ISUserMissException();
        } else {
            return $user_data;
        }
    }

    //验证是否综合体账号
    public static function iszhtMember($token, $mem_id)
    {
        $where = [
            'mem_id' => $mem_id,
            'token' => $token,
            'type' => 3, //1用户，2机构，3综合体
        ];
        $table = 'login_account';
        $user_data = Crud::getData($table, $type = 1, $where, $field = 'id');
        if (!$user_data) {
            throw new ISUserMissException();
        } else {
            return $user_data;
        }
    }



}