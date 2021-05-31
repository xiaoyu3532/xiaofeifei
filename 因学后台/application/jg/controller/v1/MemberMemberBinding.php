<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/8 0008
 * Time: 12:02
 */

namespace app\jg\controller\v1;

use app\common\model\Crud;
use app\lib\exception\AddMissException;
use app\lib\exception\DelMissException;
use app\lib\exception\ErrorMissException;
use app\lib\exception\ISUserMissException;
use app\lib\exception\NothingMissException;
use app\lib\exception\UpdateMissException;

class MemberMemberBinding extends BaseController
{
    //机构绑定机构
    public static function bindingjgMember()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $data = input();
            //获取要绑定的机构ID
            $account_where = [
                'username' => $data['username'],
                'is_del' => 1,
                'type' => 2, //1用户，2机构，4综合体，5社区，6总平台，7管理人员
            ];
            $mem_Binding_data = Crud::getData('login_account', 1, $account_where, 'mem_id');
            if (!$mem_Binding_data) {
                return jsonResponse('2000', '绑定机构信息有误');
            }
            //先判断接收到的参数
            $table = 'member';
            $where = [
//                'uid' => $data['binding_mem_id'],
                'uid' => $mem_Binding_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];

            $join = [
                ['yx_member m', 'aur.mem_id = m.uid', 'left'], //right
            ];
            $alias = 'm';
            $table = 'member';
            $admin_user_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'aur.create_time desc', $field = 'a.id,a.real_member_name,a.mem_id,a.staff_identifier,a.staff_phone,a.id_card,a.sex,a.urgent_name,a.urgent_phone,a.email,a.qq,a.we_chat,a.province,a.city,a.area,a.address,a.role_name,a.role_id,a.teacher_id,a.certificate_img,a.remarks,a.contract_img,a.contract_start_time,a.contract_end_time,a.contract_type,a.create_time,m.cname,m.province,m.city,m.area,m.address,t.teacher_nickname relation_teacher_name,zr.role_name role_name_exhibition,m.cname,m.province mprovince,m.city mcity,m.area marea,m.address msaddress,la.username,aur.id admin_user_role_id', $page, $pageSize);
            $memder_data = Crud::getData($table, 1, $where, 'uid,cname');
            if (!$memder_data) {
                return jsonResponse('2000', '绑定机构信息有误');
            }
            //不能重复添加
            $table1 = 'member_member_binding';
            $where1 = [
                'binding_mem_id' => $mem_Binding_data['mem_id'],
                'mem_id' => $mem_data['mem_id'],
                'status' => 2,
                'is_del' => 1,
            ];
            $member_binding_find = Crud::getData($table1, 1, $where1, 'id');
            if ($member_binding_find) {
                return jsonResponse('2001', '你已绑定此机构');
            }
            //进行绑定
            $indata = [
                'mem_id' => $mem_data['mem_id'],
                'binding_mem_id' => $mem_Binding_data['mem_id'],
            ];

            $member_binding = Crud::setAdd($table1, $indata);
            if (!$member_binding) {
                throw new AddMissException();
            }
            //添加通知信息
            $notice_data = [
                'mem_id' => $mem_Binding_data['mem_id'],
                'title' => $memder_data['cname'] . '邀请你绑定',
                'content' => '您将被' . $memder_data['cname'] . '绑定',
            ];
            $table2 = 'notice';
            $notice_info = Crud::setAdd($table2, $notice_data);
            if (!$notice_info) {
                throw new AddMissException();
            } else {
                return jsonResponse('1000', '添加成功等待绑定机构确认');
            }
        }
    }

    //本机构绑定的机构
    public static function Memberjgbinding()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'b.mem_id' => $mem_data['mem_id'],
                'b.is_del' => 1,
                'b.status' => ['in', [1, 2]],
            ];
            $join = [
                ['yx_member m', 'b.binding_mem_id = m.uid ', 'left'],  //机构信息
                ['yx_login_account la', 'b.binding_mem_id = la.mem_id ', 'left'],  //机构信息
            ];
            $alias = 'b';
            $table = request()->controller();
            $binding_member = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'b.create_time desc', $field = 'b.*,m.cname,m.uid mem_id,la.username', 1, 100000);
            $info = [
                'binding_member' => $binding_member,
                'type' => $mem_data['type'],
                'username' => $mem_data['username'],
            ];
            if ($binding_member) {
                return jsonResponseSuccess($info);
            } else {
                return jsonResponse(3000, '无数据', $info);
            }
        }
    }

    //本机构被其他机构绑定
    public static function coverjgMemberbinding()
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            $where = [
                'b.binding_mem_id' => $mem_data['mem_id'],
                'b.is_del' => 1,
                'b.status' => ['in', [1, 2]],
            ];
            $join = [
                ['yx_member m', 'b.mem_id =m.uid ', 'left'],  //机构信息
                ['yx_login_account la', 'b.binding_mem_id = la.mem_id ', 'left'],  //机构信息
            ];
            $alias = 'b';
            $table = request()->controller();
            $binding_member = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = 'b.create_time desc', $field = 'b.*,m.cname,m.uid mem_id,la.username', 1, 100000);
            if ($binding_member) {
                return jsonResponseSuccess($binding_member);
            } else {
                throw new NothingMissException();
            }
        }
    }

    //修改同意拒绝
    public static function bindingjgMemberStatus()
    {
        $data = input();
        //验证用户
        $table = request()->controller();
        $where = [
            'id' => $data['id'],
            'status' => ['neq', 3],
            'is_del' => 1,
        ];
        $binding_memder_data = Crud::getData($table, 1, $where, 'id');
        if (!$binding_memder_data) {
            throw new ErrorMissException();
        }
        //修改状态
        $update = [
            'status' => $data['status']
        ];
        $binding_memder_update = Crud::setUpdate($table, $where, $update);
        if (!$binding_memder_update) {
            throw new UpdateMissException();
        } else {
            return jsonResponseSuccess($binding_memder_update);
        }
    }

    //删除关联
    public static function delbindingjgMember($id)
    {
        $table = request()->controller();
        $where = [
            'id' => $id,
        ];
        $binding_memder_data = Crud::setUpdate($table, $where, ['is_del' => 2]);
        if ($binding_memder_data) {
            return jsonResponseSuccess($binding_memder_data);
        } else {
            throw new DelMissException();
        }
    }

    //获取绑定机构列表
    public static function getbindingjgMember()
    {
        $mem_data = self::isuserData();
//        if ($mem_data['type'] == 2) {
        $where = [
            'b.mem_id' => $mem_data['mem_id'],
            'b.is_del' => 1,
            'b.status' => 1,
            'm.is_del' => 1,
            'la.is_del' => 1,
        ];
        $join = [
            ['yx_member m', 'b.mem_id = m.uid', 'left'],  //机构 获取机构名称
            ['yx_login_account la', 'b.mem_id = la.mem_id', 'left'],  //机构 获取机构名称
        ];
        $alias = 'b';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.cname,la.token,b.mem_id', 1, 100);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            throw new NothingMissException();
        }
//        }
    }

    //获取绑定机构下拉列表
    public static function getbindingjgMemberList()
    {
        $mem_data = self::isuserData();
        //查看本机构是否绑定别的机构
        $is_binding_where = [
            'mem_id' => $mem_data['mem_id'],
            'is_del' => 1,
            'status' => 1,
        ];
        $table = request()->controller();
        $binding_mem_ids = Crud::getData($table, 2, $is_binding_where, 'binding_mem_id', '', 1, 100);
        if ($binding_mem_ids) {
            $data = [];
            foreach ($binding_mem_ids as $k => $v) {
                $binding_data_cname = Crud::getData('member', 1, ['uid' => $v['binding_mem_id'], 'is_del' => 1, 'status' => 1], 'cname,uid');
                $binding_data_token = Crud::getData('login_account', 1, ['mem_id' => $v['binding_mem_id'], 'is_del' => 1, 'type' => 2], 'token,mem_id,username');
                $data[] = [
                    'cname' => $binding_data_cname['cname'],
                    'mem_id' => $binding_data_cname['uid'],
                    'username' => $binding_data_token['username'],
                    'token' => $binding_data_token['token'],
                ];
            }

            //添加自己
            $joinm = [
                ['yx_login_account la', 'm.uid = la.mem_id', 'left'],  //获取token
            ];
            $aliasm = 'm';
            $mem_data = Crud::getRelationData('member', 2, ['m.uid' => $mem_data['mem_id'], 'm.is_del' => 1, 'm.status' => 1, 'la.type' => 2], $joinm, $aliasm, '', 'm.cname,la.token,la.mem_id');

            $data = array_merge($mem_data, $data);
            if ($data) {
                return jsonResponseSuccess($data);
            }

        } else {
            $joinm = [
                ['yx_login_account la', 'm.uid = la.mem_id', 'left'],  //获取token
            ];
            $aliasm = 'm';
            $mem_data = Crud::getRelationData('member', 2, ['m.uid' => $mem_data['mem_id'], 'm.is_del' => 1, 'm.status' => 1, 'la.type' => 2], $joinm, $aliasm, '', 'm.cname,la.token,la.mem_id');
            if ($mem_data) {
                return jsonResponseSuccess($mem_data);
            } else {
                throw new NothingMissException();
            }
        }


//        if ($mem_data) {
//            return jsonResponseSuccess($mem_data);
//        } else {
//            throw new ISUserMissException();
//        }

    }

    public static function getbindingjgMemberLists()
    {
        $mem_data = self::isuserData();
        dump($mem_data);
        //查看本机构是否绑定别的机构
        $is_binding_where = [
            'mem_id' => $mem_data['mem_id'],
            'is_del' => 1,
            'status' => 1,
        ];
        $table = request()->controller();
        $binding_mem_ids = Crud::getData($table, 2, $is_binding_where, 'binding_mem_id', '', 1, 100);
        if ($binding_mem_ids) {
            $data = [];
            foreach ($binding_mem_ids as $k => $v) {
                $binding_data_cname = Crud::getData('member', 1, ['uid' => $v['binding_mem_id'], 'is_del' => 1, 'status' => 1], 'cname');
                $binding_data_token = Crud::getData('login_account', 1, ['mem_id' => $v['binding_mem_id'], 'is_del' => 1, 'type' => 2], 'token,mem_id');
                $data[] = [
                    'cname' => $binding_data_cname['cname'],
                    'mem_id' => $binding_data_token['mem_id'],
                    'token' => $binding_data_token['token'],
                ];
            }

            //添加自己
            $joinm = [
                ['yx_login_account la', 'm.uid = la.mem_id', 'left'],  //获取token
            ];
            $aliasm = 'm';
            $mem_data = Crud::getRelationData('member', 2, ['m.uid' => $mem_data['mem_id'], 'm.is_del' => 1, 'm.status' => 1], $joinm, $aliasm, '', 'm.cname,la.token,la.mem_id');
            dump($mem_data);
            exit;


        } else {
            $joinm = [
                ['yx_login_account la', 'm.uid = la.mem_id', 'left'],  //获取token
            ];
            $aliasm = 'm';
            $mem_data = Crud::getRelationData('member', 2, ['m.uid' => $mem_data['mem_id'], 'is_del' => 1, 'status' => 1], $joinm, $aliasm, '', 'm.cname,la.token,la.mem_id');
            if ($mem_data) {
                return jsonResponseSuccess($mem_data);
            } else {
                throw new NothingMissException();
            }
        }


//        if ($mem_data['type'] == 2) {
        $where = [
            'b.mem_id' => $mem_data['mem_id'],
            'b.is_del' => 1,
            'b.status' => 1,
            'm.is_del' => 1,
            'la.is_del' => 1,
        ];
        $join = [
            ['yx_member m', 'b.mem_id = m.uid', 'left'],  //机构 获取机构名称
            ['yx_login_account la', 'b.mem_id = la.mem_id', 'left'],  //机构 获取机构名称
        ];
        $alias = 'b';
        $table = request()->controller();
        $cname_data = Crud::getRelationData($table, $type = 2, $where, $join, $alias, $order = '', $field = 'm.cname,la.token,b.mem_id', 1, 100);
        if ($cname_data) {
            return jsonResponseSuccess($cname_data);
        } else {
            $joinm = [
                ['yx_member m', 'la.mem_id = m.uid', 'left'],  //机构 获取机构名称
            ];
            $aliasm = 'la';
            $mem_data = Crud::getRelationData('login_account', 2, ['la.mem_id' => $mem_data['mem_id']], $joinm, $aliasm, '', 'm.uid,m.cname');
            if ($mem_data) {
                return jsonResponseSuccess($mem_data);
            } else {
                throw new ISUserMissException();
            }
        }
    }

    //获取绑定机构ID返回  要研究
    public static function getbindingjgMemberId()
    {
        $mem_data = self::isuserData();
//        if ($mem_data['type'] == 2 || $mem_data['type'] == 7) {
            $where = [
                'mem_id' => $mem_data['mem_id'],
                'is_del' => 1,
                'status' => 1,
            ];
            $table1 = 'member_member_binding';
            $cname_data = Crud::getData($table1, $type = 2, $where, $field = 'binding_mem_id', $order = '', 1, 10000);
            if ($cname_data) {
                $data = [];
                foreach ($cname_data as $k => $v) {
                    $data[] = $v['binding_mem_id'];
                }
                $onw = ['0' => $mem_data['mem_id']];
                $lsit_array = array_merge($data, $onw);
            } else {
                $lsit_array = ['0' => $mem_data['mem_id']];
            }
            return $lsit_array;
//        }
    }

    //验证绑定机构数据
    public static function isbindingjgMember($mem_id)
    {
        $mem_data = self::isuserData();
        if ($mem_data['type'] == 2) {
            if ($mem_id == $mem_data['mem_id']) {
                return 1000;
            }
            $table1 = 'member_member_binding';
            $where1 = [
                'binding_mem_id' => $mem_id,
                'mem_id' => $mem_data['mem_id'],
                'status' => 1,
                'is_del' => 1,
            ];
            $member_binding_find = Crud::getData($table1, 1, $where1, 'id');
            if ($member_binding_find) {
                return 1000;
            } else {
                return jsonResponse('2000', '机构信息有误');
            }
        }
    }
}