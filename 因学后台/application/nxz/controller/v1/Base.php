<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/30 0030
 * Time: 9:42
 */

namespace app\nxz\controller\v1;


use think\Collection;

class Base extends Collection
{
    //跨域问题
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
    }
}