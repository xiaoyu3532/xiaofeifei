<?php

namespace app\xcx\controller\v3;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;
use app\xcx\controller\v2\Base;


/**
 * 课表
 */
class Lesson extends Base
{
    protected $exceptTicket = [""];

    public function getOnlineCourseLesson()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $studentId = input('post.student_id/d');
        if ($studentId > 0) {
            //获取某位学员
            //判断学生绑定关系是否存在
            $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
            if (empty($student)) {
                return returnResponse('1002', '学员选择错误');
            }
            $where['zo.student_id'] = ['=', $studentId];
        } else {
            //获取全部学员id
            $studengIds = Crud::getDataunpage("user_student", 2, ['user_id' => $this->userId, 'is_del' => 1], 'student_id');

            if (count($studengIds) == 0) {
                return returnResponse('1002', '暂无学员');
            }
            $ids = array_column($studengIds, 'student_id');

            $where['zo.student_id'] = ['in', $ids];
        }
        $where['zo.user_id'] = ["=", $this->userId];
        $where['zo.course_category'] = ["=", 1];
        $where['zo.status'] = ["=", 2];
        $where['zo.is_del'] = ["=", 1];
        $where['zoc.expiration_time'] = ['>=', time()];
        $join = [
            ["yx_zht_online_course zoc", "zo.course_id = zoc.id"],
            ['yx_zht_category zy', 'zoc.category_id = zy.id', 'left'],
        ];
        $field = "zoc.course_img,zoc.course_name,zoc.title,zo.course_id,zy.name,zoc.course_type";
        $result = Crud::getRelationData("zht_order", 2, $where, $join, "zo", 'zo.id DESC', $field, $page, $pageSize);
        if (count($result) > 0) {
            foreach ($result as &$item) {
                //查询观看百分比
                $total = Crud::getData("zht_video_online_course", 1, ['online_course_id' => $item['course_id'], 'is_del' => 1], 'count(id) as count');

                $id = Crud::getDataunpage("zht_video_online_course", 2, ['online_course_id' => $item['course_id'], 'is_del' => 1], 'id');
                $ids = array_column($id, 'id');

                $w['user_id'] = ['=',$this->userId];
                $w['is_del'] = ['=', 1];
                $w['online_course_id'] = ['=',$item['course_id']];
                $w['video_online_course_id'] = ['in',$ids];
                $see = Crud::getData("zht_video_online_course_record", 1, $w, "count(id) as count");
                $item['percentage'] = $total['count'] == 0 ? "0%" : round($see["count"] / $total["count"] * 100) . "%";
                // if($item['course_id'] == 119){
                //     print_r($total) ;
                //     halt($see);
                // }
            }
        }
        return returnResponse("1000", '', $result);
    }
}