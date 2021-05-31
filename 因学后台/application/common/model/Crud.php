<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 10:54
 */

namespace app\common\model;


use app\lib\exception\AddMissException;
use app\lib\exception\AddressMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;
use think\Db;

class Crud
{
//    protected $field = true;

    /**
     * @param $table 表名
     * @param $type  查询一条还是多条 1:find 2select
     * @param $where 查询条件
     * @param $field 查询内容
     * @param $order 是否排序
     */
    public static function getData($table, $type = 1, $where, $field = '*', $order = '', $page = '1', $pageSize = '16', $group = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $info = Db::name($table)->where($where)->field($field)->find();
                return $info;
            } elseif ($type == 2) {
                $info = Db::name($table)->where($where)->order($order)->field($field)->group($group)->page($page, $pageSize)->select();
                return $info;
            } elseif ($type == 3) {
                $info = Db::name($table)->where($where)->field($field)->order($order)->find();
                return $info;
            }
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    public static function getDataWhereOr($table, $type = 1, $where, $whereor, $field = '*', $order = '', $page = '1', $pageSize = '16', $group = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $info = Db::name($table)->where($where)->whereOr($whereor)->field($field)->find();
                return $info;
            } elseif ($type == 2) {
                $info = Db::name($table)->where($where)->whereOr($whereor)->order($order)->field($field)->group($group)->page($page, $pageSize)->select();
                return $info;
            } elseif ($type == 3) {
                $info = Db::name($table)->where($where)->whereOr($whereor)->field($field)->order($order)->find();
                return $info;
            }
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    public static function getDatas($table, $type = 1, $where, $field = '*', $order = '', $page = '1', $pageSize = '16', $group = '', $group1 = '', $group2 = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $info = Db::name($table)->where($where)->field($field)->find();
                return $info;
            } elseif ($type == 2) {
                $info = Db::name($table)->where($where)->order($order)->field($field)->group($group)->group($group1)->group($group2)->page($page, $pageSize)->select();
                return $info;
            }
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * @param $table 表名
     * @param $type  查询一条还是多条 1:find 2select
     * @param $where 查询条件
     * @param $field 查询内容
     * @param $order 是否排序
     */
    public static function getDataunpage($table, $type = 1, $where, $field = '*', $order = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $info = Db::name($table)->where($where)->order($order)->field($field)->find();
                return $info;
            } elseif ($type == 2) {
                $info = Db::name($table)->where($where)->order($order)->field($field)->select();
                return $info;
            }
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * @param $table 表名
     * @param $type  查询一条还是多条 1:find 2select
     * @param $where 查询条件
     * @param $field 查询内容
     * @param $order 是否排序
     * @param $pagetype 1无分页，2有分页
     */
    public static function getDataGroup($table, $type = 1, $where, $field = '*', $order = '', $group = '', $pagetype = 1, $page = '1', $pageSize = '16')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $info = Db::name($table)->where($where)->order($order)->field($field)->find();
                return $info;
            } elseif ($type == 2) {
                if ($pagetype == 1) {
                    $info = Db::name($table)->where($where)->order($order)->field($field)->group($group)->select();
                } elseif ($pagetype == 2) {
                    $info = Db::name($table)->where($where)->order($order)->field($field)->group($group)->page($page, $pageSize)->select();
                }

                return $info;
            }
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * @param $table
     * @param $type 查询一条还是多条 1:find 2select
     * @param $where
     * @param $join
     * @param $alias
     * @param string $field
     * @param $page 从第几页开始
     * @param $pageSize 一页多少条
     * @return mixed
     * $page = max(input('param.page/d', 1), 1);
     * $pageSize = input('param.numPerPage/d', 16);
     */
    public static function getRelationData($table, $type = 1, $where, $join, $alias, $order = '', $field = '*', $page = 1, $pageSize = '16', $group = '')
    {

//        try {
        $strpos = strpos($table, '.');
        if ($strpos) {
            $table = substr($table, $strpos);
            $table = substr($table, 1);
        }
        if ($type == 1) {
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->order($order)
                ->find();
        } elseif ($type == 2) {
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->order($order)
                ->group($group)
                ->page($page, $pageSize)
                ->select();
        }
        return $info;
//        } catch (Exception $ex) {
//            throw $ex;
//        }

    }

    /**
     * 联查获取获取最大值
     * @param $table
     * @param $where
     * @param $join
     * @param $alias
     * @param $value
     * @return mixed
     */
    public static function getRelationMax($table, $where, $join, $alias, $field, $value)
    {

//        try {
        $strpos = strpos($table, '.');
        if ($strpos) {
            $table = substr($table, $strpos);
            $table = substr($table, 1);
        }
        $info = Db::name($table)->field($field)
            ->alias($alias)
            ->join($join)
            ->where($where)
            ->max($value);
        return $info;

    }

    /**
     * 联查加whereor
     * @param $table
     * @param int $type
     * @param $where
     * @param string $whereOr
     * @param $join
     * @param $alias
     * @param string $order
     * @param string $field
     * @param int $page
     * @param string $pageSize
     * @param string $group
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getRelationDataWhereOr($table, $type = 1, $where, $whereOr = '', $join, $alias, $order = '', $field = '*', $page = 1, $pageSize = '16', $group = '')
    {

//        try {
        $strpos = strpos($table, '.');
        if ($strpos) {
            $table = substr($table, $strpos);
            $table = substr($table, 1);
        }
        if ($type == 1) {
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->whereOr($whereOr)
                ->order($order)
                ->find();
        } elseif ($type == 2) {
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->whereOr($whereOr)
                ->order($order)
                ->group($group)
                ->page($page, $pageSize)
                ->select();
        }
        return $info;
//        } catch (Exception $ex) {
//            throw $ex;
//        }

    }

    /**
     * @param $table
     * @param $pageType 1:不加分页 2加分页
     * @param $where
     * @param $join
     * @param $alias
     * @param string $field
     * @param $page 从第几页开始
     * @param $pageSize 一页多少条
     * @return mixed
     * $page = max(input('param.page/d', 1), 1);
     * $pageSize = input('param.numPerPage/d', 16);
     */
    public static function getRelationDataGroup($table, $where, $join, $alias, $order = '', $field = '*', $group, $pageType = 1, $page = '1', $pageSize = '16')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($pageType == 1) {
                $info = Db::name($table)->field($field)
                    ->alias($alias)
                    ->join($join)
                    ->where($where)
                    ->order($order)
                    ->group($group)
                    ->select();

            } elseif ($pageType == 2)
                $info = Db::name($table)->field($field)
                    ->alias($alias)
                    ->join($join)
                    ->where($where)
                    ->order($order)
                    ->group($group)
                    ->page($page, $pageSize)
                    ->select();

            return $info;
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * 添加信息
     * @param $table
     * @param $data
     * @param $type 1 返回1 2返回ID
     * @return int|string
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setAdd($table, $data, $type = 1)
    {
        if (isset($data['version']) && !empty($data['version'])) {
            unset($data['version']);
        }
//        try {
        $strpos = strpos($table, '.');
        if ($strpos) {
            $table1 = substr($table, $strpos);
            $table = substr($table1, 1);
        }
        $data['create_time'] = time();
        if ($type == 1) {
            $res = Db::name($table)->strict(false)->insert($data);
        } elseif ($type == 2) {
            $res = Db::name($table)->strict(false)->insertGetId($data);
        } else {
            $res = Db::name($table)->strict(false)->insertAll($data);
        }
        return $res;
//        } catch (Exception $ex) {
//            throw $ex;
//        }
    }

    /**
     * 更改状态
     * @param $table
     * @param $where
     * @param $upData
     * @return int|string
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setUpdate($table, $where, $upData)
    {
//        try {
        if (isset($upData['version'])) {
            unset($upData['version']);
        }
        $strpos = strpos($table, '.');
        if ($strpos) {
            $table1 = substr($table, $strpos);
            $table = substr($table1, 1);
        }
        $upData['update_time'] = time();
        $res = Db::name($table)->strict(false)->where($where)->update($upData);
        return $res;
//        } catch (Exception $ex) {
//            throw $ex;
//        }
    }

    /**
     * 获取个数
     * @param $table
     * @param $where
     * @return int|string
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function getCount($table, $where)
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table1 = substr($table, $strpos);
                $table = substr($table1, 1);
            }
            $res = Db::name($table)->where($where)->count();
            return $res;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 获取个数 加分组
     * @param $table
     * @param $where
     * @return int|string
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function getGroupCount($table, $where, $data)
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table1 = substr($table, $strpos);
                $table = substr($table1, 1);
            }
            $res = Db::name($table)->where($where)->group($data)->select();
            if (isset($res)) {
                $res_num = count($res);
            }
            return $res_num;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 获取适合年龄
     * @param $value
     * $type 1为一维数组，2为多维数组
     */
    public static function getage($res, $type)
    {
        if ($type == 1) {
            if (!empty($res['aid'])) {
                //将字符串转成数组
                $aids = explode(",", $res['aid']);
                //查询年龄名称
                $age_names = Db::name('age')->where(['id' => ['in', $aids], 'is_del' => 1, 'type' => 1])->field('name')->select();
                if ($age_names) {
                    //二维变一维
                    $age_names = array_column($age_names, 'name');
                    //将数组转成字符串
                    $age_name = implode(",", $age_names);
                    $res['age_name'] = $age_name;
                } else {
                    $res['age_name'] = '';
                }
                return $res;
            }
        } elseif ($type == 2) {
            foreach ($res as $k => $v) {
                if (!empty($v['aid'])) {
                    //将字符串转成数组
                    $aids = explode(",", $v['aid']);
                    //查询年龄名称
                    $age_names = Db::name('age')->where(['id' => ['in', $aids], 'is_del' => 1, 'type' => 1])->field('name')->select();
                    if ($age_names) {
                        //二维变一维
                        $age_names = array_column($age_names, 'name');
                        //将数组转成字符串
                        $age_name = implode(",", $age_names);
                        $res[$k]['age_name'] = $age_name;
                    } else {
                        $res[$k]['age_name'] = '';
                    }
                }
            }
            return $res;

        }

    }

    /**
     * 查询机构人员数量将赋于原结构
     * @param $res
     * @param $fs_type 1为计算一条，2为计算多条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function Nums($res, $fs_type)
    {  //$fs_type
        //计算名额单价
        $user = Db::name('user_price')->where(['is_del' => 1])->field('price')->find();
        //计算优惠
        $discount = Db::name('discount')->where(['is_del' => 1])->field('discount')->find();
        //计算名称
        if ($fs_type == 1) {
            if ($res['give_type'] == 1) { //give_type 1有赠送名称，2无赠送名称
                //查询赠送名额数量
                $num = Db::name('give_num')->where(['mid' => $res['uid'], 'is_del' => 1])->field('num')->find();
                if (!$num) {
                    $num = 0;
                }
                if ($res['ismember'] == 1) {//1是会员，2非会员
                    $res['surplus_num'] = intval($res['balance'] / ($user['price'] * $discount['discount']) + $num['num']);
                } elseif ($res['ismember'] == 2) {
                    $res['surplus_num'] = intval($res['balance'] / $user['price'] + $num['num']);
                }
            } elseif ($res['give_type'] == 2) {
                if ($res['ismember'] == 1) {
                    $res['surplus_num'] = intval($res['balance'] / ($user['price'] * $discount['discount']));
                } elseif ($res['ismember'] == 2) {
                    $res['surplus_num'] = intval($res['balance'] / $user['price']);
                }
            }
            return $res;
        } elseif ($fs_type == 2) {
            foreach ($res as $k => $v) {
                if ($v['give_type'] == 1) { //give_type 1有赠送名称，2无赠送名称
                    //查询赠送名额数量
                    $num = Db::name('give_num')->where(['mid' => $v['uid'], 'is_del' => 1])->field('num')->find();
                    if (!$num) {
                        $num = 0;
                    }
                    //算佣金
                    if ($v['ismember'] == 1) {  //1是会员，2非会员
                        $res[$k]['surplus_num'] = intval($v['balance'] / ($user['price'] * $discount['discount']) + $num['num']);
                    } elseif ($v['ismember'] == 2) {
                        $res[$k]['surplus_num'] = intval($v['balance'] / $user['price'] + $num['num']);
                    }
                } elseif ($v['give_type'] == 2) {
                    //算佣金
                    if ($v['ismember'] == 1) {  //1是会员，2非会员
                        $res[$k]['surplus_num'] = intval($v['balance'] / ($user['price'] * $discount['discount']));
                    } elseif ($v['ismember'] == 2) {
                        $res[$k]['surplus_num'] = intval($v['balance'] / $user['price']);
                    }
                }
            }
            return $res;
        }

    }

    /**
     * 购物车加数量
     * @param $table
     * @param $where
     * @param $data
     * @return int|string
     * @throws \Exception
     */
    public static function setIncs($table, $where, $data, $num = 1)
    {
        if (isset($data['version']) && !empty($data['version'])) {
            unset($data['version']);
        }
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table1 = substr($table, $strpos);
                $table = substr($table1, 1);
            }
            $res = Db::name($table)->where($where)->setInc($data, $num);
            return $res;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 减库存
     * @param $table
     * @param $where
     * @param $data
     * @return int|string
     * @throws \Exception
     */
    public static function setDecs($table, $where, $data, $num = 1)
    {
        if (isset($data['version']) && !empty($data['version'])) {
            unset($data['version']);
        }
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table1 = substr($table, $strpos);
                $table = substr($table1, 1);
            }
            $res = Db::name($table)->where($where)->setDec($data, $num);
            return $res;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 验证用户信息（目前用于小程序后期完善不会使用）
     */
    public static function isUserData($user_id)
    {
        $where = [
            'id' => $user_id,
            'is_del' => 1,
            'type' => 1
        ];
        $user_data = Db::name('user')->where($where)->field('id')->find();
        return $user_data;
    }

    /**
     *验证用户token()
     */
    public static function isUserToken($token, $type = '')
    {

        $where = [
            'token' => $token,
            'is_del' => 1,
//            'type' => ['in',$type],
        ];
//        $user_data = Db::name('login_account')->where($where)->field('id,type,mem_id,user_id,syntheticalcn_id,admin_user_id,community_id')->find();
        $user_data = Db::name('login_account')->where($where)->field('*')->find();
        return $user_data;
    }

    /**
     * 获取用户购物车详情（用于不同状态下进行分类取出）
     * @param $res
     * @return mixed
     * @throws \Exception
     */
    public static function getCatData($res)
    {
        foreach ($res as $k => $v) {
            if ($v['status'] == 1) { //1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体课程
                $table1 = 'course';
                $where1 = [ //课程条件
                    'c.is_del' => 1,
                    'c.type' => 1,
                    'c.id' => $v['cou_id']
                ];
                $join = [
                    ['yx_curriculum cu', 'c.curriculum_id = cu.id', 'left'],
                ];
                $field = 'c.img,cu.name,c.title,c.present_price,c.original_price';
                $alias = 'c';
                $info = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field, 1, 10000);
                $res[$k]['img'] = $info['img'];
                $res[$k]['name'] = $info['name'];
                $res[$k]['title'] = $info['title'];
                $res[$k]['present_price'] = $info['present_price'];
                $res[$k]['original_price'] = $info['original_price'];
            } elseif ($v['status'] == 2) {//1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体课程
                $where1 = [
                    'ex.is_del' => 1,
                    'ex.type' => 1,
                    'ex.id' => $v['cou_id']
                ];
                $join = [
                    ['yx_curriculum cu', 'ex.curriculum_id = cu.id', 'left'],
                ];
                $table1 = 'experience_course';
                $field = 'ex.img,cu.name,ex.title,ex.present_price,ex.original_price';
                $alias = 'ex';
                $info = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field, 1, 10000);
                $res[$k]['img'] = $info['img'];
                $res[$k]['name'] = $info['name'];
                $res[$k]['title'] = $info['title'];
                $res[$k]['present_price'] = $info['present_price'];
                $res[$k]['original_price'] = $info['original_price'];
            } elseif ($v['status'] == 3) {//1普通课程，2体验课程，3社区活动课程，4秒杀课程,5综合体课程
                $where1 = [
                    'cc.is_del' => 1,
                    'cc.type' => 1,
                    'cc.id' => $v['cou_id']
                ];
                $join = [
                    ['yx_community_curriculum cu', 'cc.curriculum_id = cu.id', 'left'],
                ];
                $table1 = 'community_course';
                $field = 'cc.img,cu.name,cc.title,cc.present_price,cc.original_price';
                $alias = 'cc';
                $info = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field, 1, 10000);
                $res[$k]['img'] = $info['img'];
                $res[$k]['name'] = $info['name'];
                $res[$k]['title'] = $info['title'];
                $res[$k]['present_price'] = $info['present_price'];
                $res[$k]['original_price'] = $info['original_price'];
            } elseif ($v['status'] == 4) {//1普通课程，2体验课程，3活动课程，4秒杀课程,5综合体课程
                $where1 = [
                    'se.is_del' => 1,
                    'se.type' => 1,
                    'se.id' => $v['cou_id']
                ];
                $join = [
                    ['yx_curriculum cu', 'se.curriculum_id = cu.id', 'left'],
                ];
                $table1 = 'seckill_course';
                $field = 'se.img,cu.name,se.title,se.present_price,se.original_price';
                $alias = 'se';
                $info = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field, 1, 10000);
                $res[$k]['img'] = $info['img'];
                $res[$k]['name'] = $info['name'];
                $res[$k]['title'] = $info['title'];
                $res[$k]['present_price'] = $info['present_price'];
                $res[$k]['original_price'] = $info['original_price'];
            } elseif ($v['status'] == 5) {//1普通课程，2体验课程，3社区活动课程，4秒杀课程,5综合体课程
                $where1 = [
                    'sc.is_del' => 1,
                    'sc.type' => 1,
                    'sc.id' => $v['cou_id']
                ];
                $join = [
                    ['yx_curriculum cu', 'sc.curriculum_id = cu.id', 'left'],
                ];
                $table1 = 'synthetical_course';
                $field = 'sc.img,cu.name,sc.title,sc.present_price,sc.original_price';
                $alias = 'sc';
                $info = Crud::getRelationData($table1, $type = 1, $where1, $join, $alias, $order = '', $field, 1, 10000);
                $res[$k]['img'] = $info['img'];
                $res[$k]['name'] = $info['name'];
                $res[$k]['title'] = $info['title'];
                $res[$k]['present_price'] = $info['present_price'];
                $res[$k]['original_price'] = $info['original_price'];
            }
        }
        foreach ($res as $kk => $vv) {
            if (!empty($vv['img'])) {
                $res[$kk]['img'] = get_take_img($vv['img']);
            }
        }
        return $res;
    }

    /**
     * @param $table
     * @param $whrer
     * @param $type 1count 2查看数组个数
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCounts($table, $where, $type = '1')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            if ($type == 1) {
                $num = Db::name($table)->where($where)->count();
            }
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 联查取分页
     * @param $table
     * @param $where
     * @param $join
     * @param $alias
     * @param string $field
     * @param $group
     * @return int
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCountSel($table, $where, $join, $alias, $field = '*', $group = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->group($group)
                ->select();
            $num = count($info);
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    public static function getCountSelWhereOr($table, $where, $whereor, $join, $alias, $field = '*', $group = '')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            $info = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->whereOr($whereor)
                ->group($group)
                ->select();
            $num = count($info);
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * 联查直接返回num
     * @param $table
     * @param $where
     * @param $join
     * @param $alias
     * @param string $field
     * @return int|string
     * @throws \Exception
     * @throws \think\Exception
     */
    public static function getCountSelNun($table, $where, $join, $alias, $field = '*')
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            $num = Db::name($table)->field($field)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->count();
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * 计算总金额
     * @param $table
     * @param $where
     * @param $data
     * @return float|int
     * @throws \Exception
     */
    public static function getSum($table, $where, $data)
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            $num = Db::name($table)
                ->where($where)
                ->sum($data);
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }

    }


    public static function getRelationSum($table, $where, $join, $alias, $data)
    {
        try {
            $strpos = strpos($table, '.');
            if ($strpos) {
                $table = substr($table, $strpos);
                $table = substr($table, 1);
            }
            $num = Db::name($table)
                ->alias($alias)
                ->join($join)
                ->where($where)
                ->sum($data);
            return $num;
        } catch (Exception $ex) {
            throw $ex;
        }

    }

    /**
     * 验证课目，如此机构没课目，将机构直接添加
     * @param $data
     * $type  1 修改课目使用 2添加课程验证使用
     */
    public static function isCurriculum($data, $type)
    {
        $table = 'curriculum';
        $mem_ids = $data['mem_id'];
        unset($data['mem_id']);
        if ($type == 1) {
            //循环机构ID验证此机构是否有此课目
            $id = $data['course_id']; //课目的ID
            unset($data['course_id']);
            foreach ($mem_ids as $k => $v) {
                $where1 = [
                    'id' => $id,
                ];
                $data['mid'] = $mem_ids[0];
                $info = Crud::setUpdate($table, $where1, $data);
                if ($info) {
                    return 1000;
                } else {
                    throw new UpdateMissException();
                }
            }
        } elseif ($type == 2) {
            $curriculum_find = [];
            foreach ($mem_ids as $k => $v) {
                $where0 = [
                    'is_del' => 1,
                    'mid' => $v,
                    'id' => $data['curriculum_id']
//                    'curriculum_relation' => $data['curriculum_relation'],
                ];
                $curriculum_finds = self::getData($table, 1, $where0, 'curriculum_relation');
                if ($curriculum_finds) {
                    $curriculum_find = $curriculum_finds;
                }
            }
            if (!$curriculum_find) {
                throw  new NothingMissException();
            }
            $curriculum_data = [];
//            $curriculum_new_data = [];
            foreach ($mem_ids as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'mid' => $v,
//                    'id' => $data['curriculum_id']
                    'curriculum_relation' => $curriculum_find['curriculum_relation'],
                ];
                //验证此课程是否有此课目
                $curriculum_datas = self::getData($table, 1, $where, 'id,mid');
                if (!$curriculum_datas) {
                    //查询获取此课目
                    $where1 = [
                        'curriculum_relation' => $curriculum_find['curriculum_relation'],
                        'is_del' => 1,
                    ];
                    $curriculum_info = self::getData($table, 1, $where1, 'name,title,details,gid,aid,cid,csid,sort,st_id,sts_id,wheel_img,curriculum_relation');
                    if ($curriculum_info) {
                        $curriculum_info['mid'] = $v;
                        //添加
                        $curriculum_add = self::setAdd($table, $curriculum_info, 2);
                        if ($curriculum_add) {
                            $curriculum_data[] = [
                                'id' => $curriculum_add,
                                'mid' => $v,
                            ];
                        } else {
                            throw new AddMissException();
                        }
                    } else {
                        throw new NothingMissException();
                    }
                } else {
                    $curriculum_data[] = $curriculum_datas;
                }
            }
            return $curriculum_data;
        } elseif ($type == 3) {
            foreach ($mem_ids as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'mid' => $v,
                    'id' => $data['curriculum_id'],
                ];
                //验证此课程是否有此课目
                $curriculum_data = self::getData($table, 1, $where, '*');
                if (!$curriculum_data) {
                    //查询获取此课目
                    $where1 = [
                        'id' => $data['curriculum_id'],
                        'is_del' => 1,
                    ];
                    $curriculum_info = self::getData($table, 1, $where1, 'name,title,details,gid,aid,cid,csid,sort,st_id,sts_id,wheel_img');
                    if ($curriculum_info) {
                        $curriculum_info['mid'] = $v;
                        //添加
                        $curriculum_id = self::setAdd($table, $curriculum_info, 2);
                        if ($curriculum_id) {
                            return $curriculum_id;
                        } else {
                            throw new AddMissException();
                        }
                    } else {
                        throw new NothingMissException();
                    }
                } else {
                    return $curriculum_data;
                }
            }
        }
    }

    /**
     * 添加课程时验证教室
     * $type  1 备用 2添加课程验证使用
     * @param $data
     * @return int
     * @throws AddMissException
     * @throws NothingMissException
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function isClassroom($data, $type)
    {
        $table = 'classroom';
        if ($type == 1) {
            $id = $data['cla_id'];
            unset($data['cla_id']);
            $where1 = [
                'id' => $id,
            ];
            $data['mem_id'] = $data['mem_id'][0];
            $info = Crud::setUpdate($table, $where1, $data);
            if ($info) {
                return 1000;
            } else {
                throw new UpdateMissException();
            }
        } elseif ($type == 2) {
            $classroom_find = [];
            foreach ($data['mem_id'] as $k => $v) {
                $where0 = [
                    'is_del' => 1,
                    'mem_id' => $v,
                    'id' => $data['classroom_id'],
//                    'classroom_relation' => $data['classroom_relation'],
                ];
                $classroom_finds = self::getData($table, 1, $where0, 'id,classroom_relation');
                if ($classroom_finds) {
                    $classroom_find = $classroom_finds;
                }
            }
            if (!$classroom_find) {
                throw new NothingMissException();
            }
            $classroom_data = [];
            foreach ($data['mem_id'] as $k => $v) {
                $where = [
                    'is_del' => 1,
                    'mem_id' => $v,
                    'classroom_relation' => $classroom_find['classroom_relation'],
//                    'classroom_relation' => $data['classroom_relation'],
                ];
                //验证此课程是否有此课目
                $classroom_datas = self::getData($table, 1, $where, 'id,mem_id');
                if (!$classroom_datas) {
                    //查询获取此课目
                    $where1 = [
                        'classroom_relation' => $classroom_find['classroom_relation'],
                        'is_del' => 1,
                    ];
                    $classroom_info = self::getData($table, 1, $where1, 'name,province,city,area,address,brief,img,type_id,longitude,latitude,province_num,city_num,area_num,classroom_relation');
                    if ($classroom_info) {
                        $classroom_info['mem_id'] = $v;
                        //添加
                        $curriculum_id = self::setAdd($table, $classroom_info, 2);
                        if ($curriculum_id) {
                            $classroom_data[] = [
                                'id' => $curriculum_id,
                                'mem_id' => $v,
                            ];
                        } else {
                            throw new AddMissException();
                        }
                    } else {
                        throw new NothingMissException();
                    }
                } else {
                    $classroom_data[] = $classroom_datas;
                }
            }
            return $classroom_data;
        }

    }


    /**
     * 复制课程
     * @param $data
     * @param $table
     * @param $cou_status 1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
     * @return int
     * @throws AddMissException
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function copyjgCourses($data, $table, $cou_status = 1)
    {
        //查询出该课程所有值
        $where = [
            'id' => $data['id'],
            'is_del' => 1
        ];
        if ($cou_status == 4) {  //seckill_theme_id 从普通课到秒杀课没秒杀主题，需要用户选择主题
            $field = 'classroom_id,curriculum_id,img,name,title,present_price,original_price,enroll_num,surplus_num,arrange_time,start_time,end_time,details,sort,longitude,latitude,c_num,classroom_type,teacher_name,curriculum_cid,curriculum_csid,start_age,end_age,num_type';
        } elseif ($cou_status == 2) {
            $field = 'classroom_id,curriculum_id,img,name,title,present_price,original_price,enroll_num,surplus_num,arrange_time,start_time,end_time,details,sort,longitude,latitude,c_num,classroom_type,teacher_name,curriculum_cid,curriculum_csid,start_age,end_age,num_type';
        } elseif ($cou_status == 1) {
            $field = 'classroom_id,curriculum_id,img,name,title,present_price,original_price,enroll_num,surplus_num,arrange_time,start_time,end_time,details,cid,csid,sort,longitude,latitude,st_id,sts_id,c_num,wheel_img,classroom_type,teacher_name,curriculum_cid,curriculum_csid,start_age,end_age,num_type';
        }
        $Courses_data = Crud::getData($table, 1, $where, $field);
//        dump($Courses_data);
        //获取教室详情
        $where1 = [
            'id' => $Courses_data['classroom_id'],
            'is_del' => 1
        ];
        $table1 = 'classroom';
        $Classroom_data = Crud::getData($table1, 1, $where1, $field = 'name,province,city,area,address,brief,img,type_id,longitude,latitude,province_num,city_num,area_num,classroom_relation');
        //获取课目
        $where2 = [
            'id' => $Courses_data['curriculum_id'],
            'is_del' => 1
        ];
        $table2 = 'curriculum';
        $Curriculum_data = Crud::getData($table2, 1, $where2, $field = 'name,title,details,cid,csid,sort,longitude,latitude,st_id,sts_id,wheel_img,curriculum_relation');
        $mem_ids = $data['mem_id'];
        unset($data['mem_id']);
        //机构循环添加教室、课目、课程
        foreach ($mem_ids as $k => $v) {
            //添加课目
            $Curriculum_data['mid'] = $v;
            $Curriculum_id = Crud::setAdd($table2, $Curriculum_data, 2);
            //添加教室
            $Classroom_data['mem_id'] = $v;
            $Classroom_id = Crud::setAdd($table1, $Classroom_data, 2);
            //添加课程
            $Courses_data['mid'] = $v;
            $Courses_data['classroom_id'] = $Classroom_id;
            $Courses_data['curriculum_id'] = $Curriculum_id;
            if ($cou_status == 2) {
                $table = 'experience_course';
            } elseif ($cou_status == 4) {
                $table = 'seckill_course';
            }
            $Courses_info = Crud::setAdd($table, $Courses_data);
        }
        if ($Courses_info) {
            return 1000;
        } else {
            throw new AddMissException();
        }

    }


    /**
     * 复制成功中心课程
     * @param $data
     * @param $table
     * @param $cou_status 1普通课程，2体验课程，3活动课程，4秒杀课程，5综合体
     * @return int
     * @throws AddMissException
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改时 syntheticalcn_id 加字段获取成长中心ID
     */
    public static function copyczzxCourses($data, $table, $cou_status = 0)
    {
        //查询出该课程所有值
        $where = [
            'id' => $data['id'],
            'is_del' => 1
        ];
        $field = 'id,img,mid,syntheticalcn_id,title,present_price,original_price,total_price,enroll_num,surplus_num,arrange_time,start_time,end_time,details,sort,longitude,latitude,c_num,classroom_id,classroom_type,teacher_id,teacher_name,teacher_type,curriculum_id,curriculum_cid,curriculum_csid,start_age,end_age,num_type,mid';
        $Courses_data = Crud::getData($table, 1, $where, $field);
        //获取教室详情
        $where1 = [
            'id' => $Courses_data['classroom_id'],
            'is_del' => 1
        ];
        $table1 = 'classroom';
        $Classroom_data = Crud::getData($table1, 1, $where1, $field = 'name,province,city,area,address,brief,img,type_id,longitude,latitude,province_num,city_num,area_num,classroom_relation');
        //获取课目
        $where2 = [
            'id' => $Courses_data['curriculum_id'],
            'is_del' => 1
        ];
        $table2 = 'curriculum';
        $Curriculum_data = Crud::getData($table2, 1, $where2, $field = 'name,title,details,cid,csid,sort,longitude,latitude,st_id,sts_id,wheel_img,curriculum_relation');
        //机构循环添加教室、课目、课程
        //添加课目
        $Curriculum_data['mid'] = $Courses_data['mid'];
        $Curriculum_data['syntheticalcn_id'] = $Courses_data['syntheticalcn_id'];
        $Curriculum_id = Crud::setAdd($table2, $Curriculum_data, 2);
        //添加教室
        $Classroom_data['mem_id'] = $Courses_data['mid'];
        $Classroom_data['syntheticalcn_id'] = $Courses_data['syntheticalcn_id'];
        $Classroom_id = Crud::setAdd($table1, $Classroom_data, 2);
        unset($Courses_data['id']);
        //添加课程
//            $Courses_data['mid'] = $v;
        $Courses_data['classroom_id'] = $Classroom_id;
        $Courses_data['curriculum_id'] = $Curriculum_id;
        $Courses_data['syntheticalcn_id'] = $Courses_data['syntheticalcn_id'];
        if ($cou_status == 2) {
            $table = 'experience_course';
        } elseif ($cou_status == 4) {
            $table = 'seckill_course';
        } else {
            $Courses_data['apply_type'] = 2; //1申请中，2通过，3拒绝
        }
        $Courses_info = Crud::setAdd($table, $Courses_data);

        if ($Courses_info) {
            return 1000;
        } else {
            throw new AddMissException();
        }

    }

    /**
     * 获取综合体ID
     * @param $mem_id
     * @return mixed
     * @throws ISUserMissException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getczzxID($mem_id)
    {
        $syntheticalcn_id = Db::name('synthetical_name')->where(['mem_id' => $mem_id])->field('id')->find();
        if ($syntheticalcn_id) {
            return $syntheticalcn_id['id'];
        } else {
            throw new ISUserMissException();
        }

    }

    /**
     * 添加机构发布课程数
     * @param $mem_id
     * @return int|string
     * @throws \Exception
     */
    public static function setIncsMemberId($mem_id)
    {
        $info = self::setIncs('member', ['uid' => $mem_id], 'course_num');
        if ($info) {
            return $info;
        }
    }

    /**
     * 添加课程时，并添加机构分类ID
     * @param $curriculum_cid
     * @param $mem_id
     * @throws NothingMissException
     * @throws \Exception
     */
    public static function setIncMemberCaid($curriculum_cid, $mem_id)
    {
        //获取分类名称
        $where = [
            'is_del' => 1,
            'type' => 1,
            'id' => $curriculum_cid //分类ID
        ];
        $curriculum_data = self::getData('category', 1, $where, 'name,id');
        if (!$curriculum_data) {
            throw new NothingMissException();
        }
        //获取机构
        $where1 = [
            'uid' => $mem_id,
            'status' => 1,
            'is_del' => 1,
        ];
        $memder_data = self::getData('member', 1, $where1, 'caid,caname');
        if ($memder_data) {
            if (!empty($memder_data['caid'])) {
                $caid = explode(',', $memder_data['caid']);
                $caid = self::unsetemptyArray($caid); //去除空数组
            } else {
                $caid_data = $curriculum_data['id'];
                $caname_data = $curriculum_data['name'];
                $update_data = [
                    'caid' => $caid_data,
                    'caname' => $caname_data,
                    'update_time' => time(),
                ];
                $memder_update = self::setUpdate('member', ['uid' => $mem_id], $update_data);
                if ($memder_update) {
                    return 1000;
                } else {
                    throw new AddressMissException();
                }
            }
            if (!empty($memder_data['caname'])) {
                $caname = explode(',', $memder_data['caname']);
                $caname = self::unsetemptyArray($caname); //去除空数组
            }
            if (in_array($curriculum_data['id'], $caid)) {
                return 1000;
            } else {
                $caid[] = $curriculum_data['id'];
                $caname[] = $curriculum_data['name'];
                //将组转成字符串
                $caid_data = implode(",", $caid);
                $caname_data = implode(",", $caname);
                $update_data = [
                    'caid' => $caid_data,
                    'caname' => $caname_data,
                    'update_time' => time(),
                ];
                $memder_update = self::setUpdate('member', ['uid' => $mem_id], $update_data);
                if ($memder_update) {
                    return 1000;
                } else {
                    throw new AddressMissException();
                }
            }
        }
    }

    /**
     * 去除空数组
     * @param $data
     * @return mixed
     */
    public static function unsetemptyArray($data)
    {
        foreach ($data as $k => $v) {
            if (!$v)
                unset($data[$k]);
        }
        return $data;
    }

    /**
     * 增加机构浏览量
     * @param $mem_id
     */
    public static function IncMemberNum($mem_id)
    {
        $member_num = self::setIncs('member', ['uid' => $mem_id], 'browse_num');
        if ($member_num) {
            return $member_num;
        }
    }

}