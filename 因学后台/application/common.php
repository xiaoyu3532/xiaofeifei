<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
/**
 * 阿里云OSS图片上传
 * @author Steed
 * @param $file
 * @param $rootpath
 * @return array
 */
function uploadOss($file, $rootpath)
{
    $data = [];
    if (count((array)$file) < 1) {
        return ['status' => false, 'msg' => '上传文件不存在！'];
    }
    $savepath = '/' . date('Ymd');
    \think\Loader::import('OSS.Oss', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.OssClient', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.OssUtil', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.MimeTypes', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.RequestCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.ResponseCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.Result', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.PutSetDeleteResult', VENDOR_PATH, EXT);
    $oss = new \OSS\Oss(\think\Config::get('oss'));


//    dump($file);exit;
    //单文件上传
    if (is_object($file)) {
        $file = $file->getInfo();
        //获取文件后缀
        $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
        /* 检查文件后缀 */
        if (!$oss->checkExt($file['ext'])) return ['status' => false, 'msg' => $oss->getError()];
        /* 检查文件大小 */
        if (!$oss->checkSize($file['size'])) return ['status' => false, 'msg' => $oss->getError()];
        $file['savepath'] = $rootpath . $savepath . '/';
        /* 生成文件名 */
        $file['savename'] = time() . rand(1, 10000) . '.' . $file['ext'];
        /* 上传文件 */
        if (!$oss->save($file)) return ['status' => false, 'msg' => $oss->getError()];

        $data = ['savepath' => $file['savepath'] . $file['savename'], 'savename' => $file['savename']];
    } else if (is_array($file)) {

        //多文件上传
        foreach ($file as $key => $value) {
            $value = $value->getInfo();
            //获取文件后缀
            $value['ext'] = pathinfo($value['name'], PATHINFO_EXTENSION);
            /* 检查文件后缀 */
            if (!$oss->checkExt($value['ext'])) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            /* 检查文件大小 */
            if (!$oss->checkSize($value['size'])) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            $value['savepath'] = $rootpath . $savepath . '/';
            /* 生成文件名 */
            $value['savename'] = time() . rand(1, 10000) . '.' . $value['ext'];
            /* 上传文件 */
            if (!$oss->save($value)) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            $data[$key] = ['savepath' => $value['savepath'], 'savename' => $value['savename'], 'ext' => $value['ext'], 'size' => $value['size'], 'name' => $value['name']];
        }
    }
    return ['status' => true, 'msg' => '', 'data' => $data];
}

/**
 * 返回json 备用
 * @param $code
 * @param $msg
 * @param array $data
 * @return string
 * 1000为成功
 * 2000为失败给客户
 * 3000为失败给后台人员看
 */
function jsonResponses($code, $msg, $data = [])
{
    $content = [
        'code' => (int)$code,
        'msg' => $msg,
        'data' => $data
    ];
    return json_encode($content);
}

/**
 * 返回json
 * @param $code
 * @param $msg
 * @param array $data
 * @return string
 */
function jsonResponse($code, $msg, $data = [])
{
    $content = [
        'code' => $code * 1,
        'msg' => $msg,
        'data' => $data
    ];
    return json_encode($content);
}

/**
 * 去除特殊符号
 * @param $strParam
 * @return null|string|string[]
 */
function replaceSpecialChar($strParam)
{
    $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\（|\）|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\||\s+/";
    return preg_replace($regex, "", $strParam);
}

/**
 * 生日计算年龄
 * @param $age
 * @return float
 */
function CalculationAge($age)
{
    $age_data = strtotime($age);
    $new_age = round((time() - $age_data) / 31536000);
    return $new_age;
}


/**
 * 返回成功
 * @param array $data
 * @return string
 */
function jsonResponseSuccess($data = [])
{
    $content = [
        'code' => 1000 * 1,
        'msg' => '成功',
        'data' => $data
    ];
    return json_encode($content);
}

/**
 * 数组去重
 * @param $arr
 * @param $key
 * @return mixed
 */
function assoc_unique($arr, $key)
{
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if ($v[$key] != null) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
    }
    sort($arr); //sort函数对数组进行排序
    return $arr;
}

/**
 * 验证是否是手机号
 * @param $text
 * @return bool
 */
function is_mobile($data)
{
    $search = '/^0?1[3|4|5|6|7|8][0-9]\d{8}$/';
    if (preg_match($search, $data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * 验证是否是数字
 * @param $text
 * @return bool
 */
function is_num($num)
{
    if (is_numeric($num)) {
        return (true);
    } else {
        return (false);
    }
}

function uniquArr($array)
{
    $result = array();
    foreach ($array as $k => $val) {
        $code = false;
        foreach ($result as $_val) {
            if ($_val['id'] == $val['id']) {
                $code = true;
                break;
            }
        }
        if (!$code) {
            $result[] = $val;
        }
    }
    return $result;
}

/**
 * 三维数组变二维数组
 * @param $data
 */
function Three_Two_array($data)
{
    $array = [];
    foreach ($data as $k => $v) {
        foreach ($v as $kk => $vv) {
            $array[] = $vv;
        }
    }
    return $array;
}

/**
 * 取两维数组某一字段最小值
 * @param $arr
 * @param $field
 * @return bool|mixed
 */
function searchmax($arr, $field) // 最小值 只需要最后一个max函数  替换为 min函数即可
{
    if (!is_array($arr) || !$field) { //判断是否是数组以及传过来的字段是否是空
        return false;
    }

    $temp = array();
    foreach ($arr as $key => $val) {
        $temp[] = $val[$field]; // 用一个空数组来承接字段
    }
    return min($temp);  // 用php自带函数 min 来返回该数组的最小值，一维数组可直接用min函数
}

/**
 * 多维数组变一维数组
 */
function Many_One($data)
{
    $result = [];
    array_walk_recursive($data, function ($value) use (&$result) {
        array_push($result, $value);
    });
    return $result;
}

function Many_One_Val($data, $val)
{
    $arr_Array = array_reduce($data, function (&$arr_Array, $v) {
        $arr_Array[] = $v['$val'];
        return $arr_Array;
    });
}


/**
 * 多维数组取差集
 * @param $arr1
 * @param $arr2
 * @param string $pk 以哪个键取差集
 * @return array
 */
function get_diff_array_by_pk($arr1, $arr2, $pk)
{
    try {
        $res = [];
        foreach ($arr2 as $item) $tmpArr[$item[$pk]] = $item;
        foreach ($arr1 as $v) if (!isset($tmpArr[$v[$pk]])) $res[] = $v;
        return $res;
    } catch (\Exception $exception) {
        return $arr1;
    }
}

/**
 * 数组某一字段排序
 * @param $data
 * @param $value
 * @param $type
 * SORT_ASC 升序
 * SORT_DESC 降序
 */
function array_sort($data, $value, $type)
{
    $last_names = array_column($data, $value);
    array_multisort($last_names, $type, $data);
    return $data;
}


/**
 * 判读是否是序列化字符串
 * @param $data
 * @return int
 * 1是，2不是
 */
function is_serialized($data)
{
    $data = trim($data);
    if ('N;' == $data) return true;
    if (!preg_match('/^([adObis]):/', $data, $badions)) return false;
    switch ($badions[1]) {
        case 'a' :
        case 'o' :
        case 's' :
            if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) return true;
            break;
        case 'b' :
        case 'i' :
        case 'd' :
            if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data)) return true;
            break;
    }
    return false;
}

/**
 * 拼接密码
 * @author Steed
 * @param $password
 * @param $salt
 * @return string
 */
function splice_password($password, $salt)
{
    return md5(sha1($password . $salt));
}

/**
 * 时间戳转时间
 */
function conversion_time($time)
{
    return date('Y-m-d H:i:s', $time);
}

function conversion_time_year($time)
{
    return date('Y-m-d', $time);
}


/**
 * 生成随机字符串
 * @param $length
 * @return null|string
 */
function get_rand_char($length)
{
    $str = null;
    $strPol = "0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];    //rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    return $str;
}

/**
 * 处理图片取 前端
 */
function get_take_img($img)
{
    if (!empty($img)) {
        if (is_serialized($img)) {
            $img = unserialize($img);
            return $img;
        } else {
            return '';
            // return $img;
        }
    }
}

/**
 * 处理视频取 前端
 */
function get_take_video($video)
{
    if (!empty($video)) {
        if (is_serialized($video)) {
            $video = unserialize($video);
            return $video[1];
        } else {
            return '';
        }
    }
}

/**
 *处理图片存库 后端
 * $type 1为多图处理，2为单图处理
 */
function handle_img_deposit($img, $type = 1)
{
    if ($type == 1) {
        if (isset($img) && !empty($img)) {
            $img_array = [];
            foreach ($img as $k => $v) {
                if (isset($v['response'])) {
                    $img_array[] = $v['response'];
                } else {
                    $img_array[] = $v['url'];
                }
            }
            $img = serialize($img_array);
        }
    } elseif ($type == 2) {
        if (isset($img) && !empty($img)) {
            if (isset($img['response'])) {
                $img = $img['response'];
            } else {
                $img = $img['url'];
            }
        }
    }

    return $img;
}

function handle_video_deposit($video, $type = 1)
{
    if ($type == 1) {
        if (isset($video) && !empty($video)) {
            foreach ($video as $k => $v) {
                if (isset($v['response'])) {
                    $video[] = $v['response'];
                    $video_name = $v['name'];
                } else {
                    $video[] = $v['url'];
                    $video_name = $v['name'];
                }
            }
            $video = serialize($video);
        }
    }
    $data = [
        'video' => $video,
        'video_name' => $video_name,
    ];

    return $data;
}


/**
 *处理图片取展示 后端
 */
function handle_img_take($img)
{
    if (!empty($img)) {
        $img = unserialize($img);
        $img_array = [];
        foreach ($img as $k => $v) {
            $img_array[] = [
                'name' => 'food.jpg',
                'url' => $v
            ];
        }
        $img = $img_array;
    } else {
        $img = [];
    }
    return $img;
}

function handle_video_take($video_data)
{
    if (!empty($video_data)) {
        $video = unserialize($video_data['video']);
//        dump($video);exit;
        $video_array = [];
//        foreach ($video as $k => $v) {
        $video_array[] = [
            'name' => $video_data['video_name'],
            'url' => $video[1]
        ];
//        }
        $video = $video_array;
    } else {
        $video = [];
    }
    return $video;
}

/**
 * 处理分类
 */
function handle_type_deposit($values1, $values2, $data)
{
    $data[$values1] = $data[0];
    if (!empty($data[1])) {
        $data[$values2] = $data[1];
    }
    return $data;
}

/**
 * 函数 数组多条件去重
 */

function handle_arguments($val, $value, $args)
{
    foreach ($args as $k => $v) {
        if ($val[$v] != $value[$v]) {
            return false;
        }
    }
    return true;
}

/**
 * 函数 数组多条件去重
 */

function remove_duplicate($data, $res, $args)
{
    $list = [];
    $flag = '';
    foreach ($data as $key => $val) {
        foreach ($res as $k => $v) {
            $tmpStr = implode(',', $v);

            if ($key == 0 && $k == 0) {
                $flag .= $tmpStr;
                $list[] = $data[$key];
            }

            if (handle_arguments($val, $v, $args) && strpos($flag, $tmpStr) === false) {
                $flag .= '|' . $tmpStr;
                $list[] = $data[$key];
            }
        }
    }
    return $list;
}


function isNumType($data)
{
    if ($data['surplus_num'] == 0 || $data['surplus_num'] == null || $data['surplus_num'] == '') {
        $data['num_type'] = 1;
    }
    return $data;
}


/**
 * 如果为空值以'-'代替
 * @param $v
 * @return string
 */
function isemptydata($v)
{
    if (empty($v)) {
        return '-';
    }
}

/**
 * 判断两个时间区间是否重叠
 */

function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')
{
    $status = $beginTime2 - $beginTime1;
    if ($status > 0) {
        $status2 = $beginTime2 - $endTime1;
        if ($status2 > 0) {
            return false;
        } elseif ($status2 < 0) {
            return true;
        } else {
            return false;
        }
    } elseif ($status < 0) {
        $status2 = $endTime2 - $beginTime1;
        if ($status2 > 0) {
            return true;
        } else if ($status2 < 0) {
            return false;
        } else {
            return false;
        }
    } else {
        $status2 = $endTime2 - $beginTime1;
        if ($status2 == 0) {
            return false;
        } else {
            return true;
        }
    }
}

/**
 * xcx返回值
 * @param $errorCode
 * @param $msg
 * @param array $data
 * @return string
 */
function returnResponse($errorCode, $msg, $data = [])
{
    $content = [
        'error_code' => $errorCode,
        'msg' => $msg,
        'data' => $data
    ];
    return json_encode($content);
}

function field_library()
{
    $array = [
        ['student_name' => '学生名称 '], //学生名称
        ['sex' => '性别'], //数据
        ['sex_name' => '性别'], //展示汉字
        ['year_age' => '年龄'],//年龄
        ['phone_look' => '查看手机号'],//查看手机号
        ['create_time' => '添加时间'],
        ['cname' => '机构名称'],
        ['maddress' => '机构地址'],//加详细地址
        ['mlocation' => '机构地址'],//不加详细地址
        ['mprovince' => '省'],//机构省
        ['mcity' => '市'],//机构市
        ['marea' => '区'],//机构区
        ['msaddress' => '详细地址'],//机构详细地址
        ['saddress' => '学生地址'],//加详细地址
        ['slocation' => '学生地址'],//不加详细地址
        ['sprovince' => '省'],//学生省
        ['scity' => '市'],//学生市
        ['sarea' => '区'],//学生区
        ['saddress' => '详细地址'],//学生详细地址
        ['customer_source' => '客户来源'],
        ['salesman_name' => '业务员'],
        ['is_audition_Exhibition' => '是否试听'],
        ['return_visit_type_name' => '回访状态标签'],
        ['return_visit_content' => '回访信息'],//回访内容
        ['create_time' => '添加时间'],
        ['parent_student_relation' => '家长学生关系'],//数据
        ['parent_student_relation_name' => '家长学生关系'],//字段展示
        ['del_time' => '删除时间'],//
        ['user_relation' => '关系'],// 学生和家长关系
        ['parent_student_relation_name' => '关系名'],// 学生和家长关系 例如爸爸
        ['get_into_time_Exhibition' => '入池时间'],// 入池时间
        ['create_time_Exhibition' => '添加时间'],// 添加时间
        ['category_combination' => '类目'],// 一级分类与二级分类组合

        ['classroom_name' => '教室名称'],
        ['classroom_address' => '教室地址'],
        ['fit_curriculum' => '适合课程'],
        ['contain_number' => '容纳人数'],
        ['area_number' => '教室面积（m²）'],
        ['member_phone' => '机构手机号'],
        ['grade' => '星级'],
        ['teacher_nickname' => '老师昵称'],
        ['teacher_name' => '老师真实名称'],
        ['teaching_age' => '老师教龄'],
        ['teacher_type_name' => '老师适合课程'],

        ['real_member_name' => '机构人员真实名称'],
        ['staff_phone' => '员工手机号'],
        ['role_name_exhibition' => '人员角色'], //只是展示
        ['relation_teacher_name' => '关联老师'], //机构管理人员中关联的老师
        ['contract_time' => '合同时间'],
        ['role_name' => '权限名称'],
        ['course_type_name' => '课程类型'], //1体验课程，2普通课程 ，3活动课程

        ['category' => '课程分类'],//大小分类组合
        ['age_section' => '年龄区间'],//课程适合年龄区间
        ['course_wheel_img' => '轮播图'],//课程轮播图

        ['sex' => '性别'],
    ];
}

function returnVisitTypeName($type)
{
//1.未跟进，2后续联系，3无意向，4感兴趣，5有意向，6到访，7试听
    if ($type == 1) {
        return '未跟进';
    } elseif ($type == 2) {
        return '后续联系';
    } elseif ($type == 3) {
        return '无意向';
    } elseif ($type == 4) {
        return '感兴趣';
    } elseif ($type == 5) {
        return '有意向';
    } elseif ($type == 6) {
        return '到访';
    } elseif ($type == 7) {
        return '试听';
    }
//    return_visit_type_name
}






