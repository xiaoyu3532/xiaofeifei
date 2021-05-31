<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
//解决跨域问题
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
//更改日志存储位置
define('LOG_PATH', __DIR__ . '/../log/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';


