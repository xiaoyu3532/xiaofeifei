<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/10 0010
 * Time: 15:56
 */

namespace app\nxzback\controller\v1;
use app\common\model\Crud;
use app\lib\exception\OrderMissExceptionFind;

class Order extends Base
{
    public static function getContrarianBackorder(){
        $data = input();
        $where = [
            'o.mid' => $data['mem_id'],
            'o.cou_status' => 6,
            'o.is_del' => 1,
            'st.is_del' => 1,
        ];
        isset($data['status']) && !empty($data['status']) && $where['o.status'] = $data['status'];
        $table = request()->controller();
        $join = [
            ['yx_contrarian_course co', 'o.cid = co.id', 'left'],
            ['yx_contrarian_classification cc', 'co.classification_id = cc.id', 'left'],
            ['yx_student st', 'o.student_id = st.id', 'left'],
            ['yx_user u', 'o.uid = u.id', 'left'],
        ];
        $alias = 'o';
        $field = ['o.name cou_name,u.img,co.title,st.name,st.age,o.create_time,cc.name ccname,st.phone'];
        $order = 'o.create_time';
        $page = max(input('param.page/d', 1), 1);
        $info = Crud::getRelationData($table, 2, $where, $join, $alias, $order, $field, $page);
        if ($info) {
            foreach ($info as $k=>$v){
                $info[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            }
            return jsonResponseSuccess($info);
        } else {
            throw new OrderMissExceptionFind();
        }

    }

}