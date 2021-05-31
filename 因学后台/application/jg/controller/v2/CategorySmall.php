<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/10 0010
 * Time: 10:49
 */

namespace app\jg\controller\v2;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\EditRecoMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\pc\controller\v1\BaseController;
use app\jg\controller\v1\MemberMemberBinding as bindingMember;

class CategorySmall extends BaseController
{
    //一级分类获取下拉
    public static function getCategoryList()
    {
        $Category_data = Crud::getData('zht_category', 2, ['is_del' => 1], 'id,name category_name');
        if ($Category_data) {
            return jsonResponseSuccess($Category_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取机构二级分类列表
    public static function getCategorySmallList($page = 1, $pageSize = 8, $mem_id = '', $category_id = '')
    {
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) { //1用户，2机构
            $where = [
                'c.is_del' => 1,
                'c.type' => 1,
                'cs.is_del' => 1,
                'cs.type' => 1,
                'm.is_del' => 1,
                'm.status' => 1,
            ];

            if (empty($mem_id)) {
                $mem_ids = bindingMember::getbindingjgMemberId();
                $where['cs.mem_id'] = ['in', $mem_ids];
            } else {
                $where['cs.mem_id'] = $mem_id;
            }
        } else {
            throw new ISUserMissException();
        }

        (isset($category_id) && !empty($category_id)) && $where['cs.category_id'] = $category_id;//二级分类
        $join = [
            ['yx_member m', 'cs.mem_id = m.uid', 'left'], //right
            ['yx_zht_category c', 'cs.category_id = c.id', 'left'], //right
        ];
        $alias = 'cs';
        $table = request()->controller();
        $category_small_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'cs.create_time desc', $field = 'cs.id,cs.category_id,cs.mem_id,cs.category_small_name,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,c.name category_name', $page, $pageSize);
        if ($category_small_data) {
            foreach ($category_small_data as $k => $v) {
                $category_small_data[$k]['maddress'] = $v['mprovince'] . $v['mcity'] . $v['marea'] . $v['msaddress'];
            }
            $num = Crud::getCountSel($table, $where, $join, $alias, $field = 'cs.id');
            $info_data = [
                'info' => $category_small_data,
                'pageSize' => (int)$pageSize,
                'num' => $num,
            ];
            return jsonResponseSuccess($info_data);
        } else {
            throw new NothingMissException();
        }
    }

    //获取机构二级分类列表字段
    public static function getCategorySmallListField()
    {
        $data = [
            ['prop' => 'cname', 'name' => '机构名称', 'width' => '', 'state' => ''],
            ['prop' => 'maddress', 'name' => '机构地址', 'width' => '', 'state' => ''],
            ['prop' => 'category_name', 'name' => '平台科目分类', 'width' => '', 'state' => ''],
            ['prop' => 'category_small_name', 'name' => '二级分类', 'width' => '160', 'state' => '1'],
        ];
        return jsonResponseSuccess($data);
    }

    //添加机构二级分类列表 category_small_name 二级分类名称 mem_id机构id category_id 一级分类ID
    public static function addCategorySmall()
    {
        $data = input();
        $account_data = self::isuserData();
        if ($account_data['type'] == 2 || $account_data['type'] == 7) {
            if (!isset($data['mem_id']) || empty($data['mem_id'])) {
                $data['mem_id'] = $account_data['mem_id'];
            }
//            if (!isset($data['category_id']) || empty($data['category_id'])) {
//                $data['pid'] = $data['category_id'];
//            }
            $table = request()->controller();
            $category_small_data = Crud::setAdd($table, $data);
            if ($category_small_data) {
                return jsonResponseSuccess($category_small_data);
            } else {
                throw new AddMissException();
            }
        }
    }

    //获取编辑机构二级分类
    public static function getCategorySmall($category_small_id)
    {
        $where = [
            'id' => $category_small_id
        ];
        $table = request()->controller();
        $CategorySmall = Crud::getData($table, 1, $where, 'id,pid,name');
        if ($CategorySmall) {
            return jsonResponseSuccess($CategorySmall);
        } else {
            throw new NothingMissException();
        }

    }

    //修改机构二级分类
    public static function editCategorySmall()
    {
        $data = input();
        $id = $data['category_small_id'];
        unset($data['category_small_id']);
        $table = request()->controller();
        $where = [
            'id' => $id,
        ];
        $category_small = Crud::setUpdate($table, $where, $data);
        if ($category_small) {
            return jsonResponseSuccess($category_small);
        } else {
            throw new EditRecoMissException();
        }
    }

    //删除机构二级分类
    public static function delCategorySmall($category_small_id)
    {
        $where = [
            'id' => ['in', $category_small_id]
        ];
        $table = request()->controller();
        $category_small = Crud::setUpdate($table, $where, ['is_del' => 2]);
        if ($category_small) {
            return jsonResponseSuccess($category_small);
        } else {
            throw new DelMissException();
        }

    }

}