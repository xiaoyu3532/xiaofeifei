<?php

namespace app\xcx\controller\v2;

use app\lib\exception\NothingMissException;
use Ramsey\Uuid\Uuid;
use think\Controller;
use app\common\model\Crud;
use think\Db;
use think\Exception;


/**
 * 课表
 */
class Lesson extends Base
{
    protected $exceptTicket = [""];

    // protected $allowTourist = ['access_token'];

    /**
     * @Notes: 获取课表排课
     * @Author: asus
     * @Date: 2020/6/2
     * @Time: 16:08
     * @Interface getLessonList
     * @return string
     */
    public function getLessonList()
    {
        $studentId = input('post.student_id/d');
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [];
        $status = input('post.student_course_type/d', 0);
        if ($status > 1) {
            $where['scl.student_course_type'] = ['=', $status];
        }
        if ($studentId > 0) {
            //获取某位学员
            //判断学生绑定关系是否存在
            $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
            if (empty($student)) {
                return returnResponse('1002', '学员选择错误');
            }
            $where['scl.student_id'] = ['=', $studentId];
        } else {
            //获取全部学员id
            $studengIds = Crud::getDataunpage("user_student", 2, ['user_id' => $this->userId, 'is_del' => 1], 'student_id');

            if (count($studengIds) == 0) {
                return returnResponse('1002', '暂无学员');
            }
            $ids = array_column($studengIds, 'student_id');

            $where['scl.student_id'] = ['in', $ids];
        }
        $search = input('post.search/s');
        if (!empty(trim($search))) {
            //搜索课程名称
            $where['zc.course_name'] = ['like', "%" . $search . "%"];
        }
        $where['zct.day_time_start'] = ['>=', time()];

        //获取课程表
        $join = [
            ['yx_zht_course_timetable zct', 'scl.course_timetable_id = zct.id', 'left'],
            ['yx_zht_course zc', 'zct.course_id = zc.id', 'left'],
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'],
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'],
            ['yx_lmport_student ls', 'scl.student_id = ls.id', 'left'],
            ['yx_zht_arrange_course zac', 'scl.arrange_course_id = zac.id', 'left']
        ];
        $week = ['日', '一', '二', '三', '四', '五', '六'];
        $field = "scl.id,scl.student_course_type,zc.course_img,zc.course_name,zct.mem_id,zct.week,zct.day_time_start,zct.time_slot,zct.attend_class_num,zct.course_hour,t.teacher_nickname,c.province,c.city,c.area,c.address,ls.student_name,zac.arrange_course_name";
        $studengClass = Crud::getRelationData("zht_student_class_list", 2, $where, $join, 'scl', 'zct.day_time_start ASC', $field, $page, $pageSize);
        //halt(Db::name("zht_student_class_list")->getLastSql());
        $day = 0;
        if (count($studengClass) > 0) {
            foreach ($studengClass as &$item) {
                if (date("Y-m-d", $item['day_time_start']) == date('Y-m-d')) {
                    $item['time'] = "今天";
                    $day += 1;
                } else {
                    $item['time'] = date("m月d日", $item['day_time_start']);
                }
                //unset($item['day_time_start']);
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
                $item['week'] = "周" . $week[$item['week']];
            }
        }
        $result['day'] = $day;
        $result['studentClass'] = $studengClass;
        return returnResponse('1000', '', $result);
    }

    /**
     * @Notes: 获取历史课表
     * @Author: asus
     * @Date: 2020/6/2
     * @Time: 16:47
     * @Interface getHistoryLessonList
     * @return string
     */
    public function getHistoryLessonList()
    {
        $studentId = input('post.student_id/d');
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 16);
        $where = [];
        $status = input('post.student_course_type/d', 0);
        if ($status > 1) {
            $where['scl.student_course_type'] = ['=', $status];
        }
        $where['zct.day_time_start'] = ['<', time()];
        if ($studentId > 0) {
            //获取某位学员
            //判断学生绑定关系是否存在
            $student = Crud::getData('user_student', 1, ['user_id' => $this->userId, 'student_id' => $studentId, 'is_del' => 1], 'id');
            if (empty($student)) {
                return returnResponse('1002', '学员选择错误');
            }
            $where['scl.student_id'] = ['=', $studentId];
        } else {
            //获取全部学员id
            $studengIds = Crud::getDataunpage("user_student", 2, ['user_id' => $this->userId, 'is_del' => 1], 'student_id');

            if (count($studengIds) == 0) {
                return returnResponse('1002', '暂无学员');
            }
            $ids = array_column($studengIds, 'student_id');

            $where['scl.student_id'] = ['in', $ids];
        }
        $search = input('post.search/s');
        if (!empty(trim($search))) {
            //搜索课程名称
            $where['zc.course_name'] = ['like', "%" . $search . "%"];
        }

        //获取课程表
        $join = [
            ['yx_zht_course_timetable zct', 'scl.course_timetable_id = zct.id', 'left'],
            ['yx_zht_course zc', 'zct.course_id = zc.id', 'left'],
            ['yx_teacher t', 'zct.teacher_id = t.id', 'left'],
            ['yx_classroom c', 'zct.classroom_id = c.id', 'left'],
            ['yx_lmport_student ls', 'scl.student_id = ls.id', 'left'],
            ['yx_zht_arrange_course zac', 'scl.arrange_course_id = zac.id', 'left']
        ];
        $week = ['日', '一', '二', '三', '四', '五', '六'];
        $field = "scl.student_course_type,zc.course_img,zc.course_name,zct.week,zct.mem_id,zct.day_time_start,zct.time_slot,zct.attend_class_num,zct.course_hour,t.teacher_nickname,c.province,c.city,c.area,c.address,ls.student_name,zac.arrange_course_name";
        $studengClass = Crud::getRelationData("zht_student_class_list", 2, $where, $join, 'scl', 'zct.day_time_start ASC', $field, $page, $pageSize);
        //halt(Db::name("zht_student_class_list")->getLastSql());
        if (count($studengClass) > 0) {
            foreach ($studengClass as &$item) {
                if (date("Y-m-d", $item['day_time_start']) == date('Y-m-d')) {
                    $item['time'] = "今天";
                } else {
                    $item['time'] = date("m月d日", $item['day_time_start']);
                }
                //unset($item['day_time_start']);
                $item['addr'] = $item['province'] . $item['city'] . $item['area'] . $item['address'];
                unset($item['province']);
                unset($item['city']);
                unset($item['area']);
                unset($item['address']);
                $item['week'] = "周" . $week[$item['week']];
            }
        }
        return returnResponse('1000', '', $studengClass);
    }
}