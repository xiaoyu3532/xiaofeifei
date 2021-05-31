<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 10:17
 */

namespace app\xcx\controller\v1;


use app\lib\exception\ISUserMissException;
use app\validate\UserIDMustBePostiveInt;
use think\Collection;
use app\common\model\Crud;

class BaseController extends Collection
{

    public function __construct()
    {
        parent::__construct();
        $this->isuserData();//验证用户信息
    }
    //验证用户信息（后期加Token）
    public function isuserData(){
        $data = input();
        (new UserIDMustBePostiveInt())->goCheck();
        $info = Crud::isUserData($data['user_id']);
        if(!$info){
            throw new ISUserMissException();
        }
    }
}