<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/9 0009
 * Time: 9:44
 */

namespace app\common\controller;

use think\Controller;
use app\lib\exception\ISUserMissException;
use app\validate\UserIDMustBePostiveInt;
use app\common\model\Crud;
use think\Request;

class Base extends Controller
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
        parent::__construct();
    }




}