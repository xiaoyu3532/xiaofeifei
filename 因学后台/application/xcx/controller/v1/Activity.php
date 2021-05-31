<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 16:15
 */

namespace app\xcx\controller\v1;


use app\common\model\Crud;
use app\lib\exception\ActivityMissException;
use think\Db;

class Activity
{
    //获取活动Banner
    public function getActivity()
    {
        $where = [
            'is_del' => 1,
            'type' => 1,
        ];
        $table = request()->controller();
        $info = Crud::getData($table, $type = 1, $where, $field = 'id,name,img,status,content');
        if (!$info) {
            throw new ActivityMissException();
        } else {
            return jsonResponse('1000', '成功获取活动图', $info);
        }
    }


    public function getindeximg()
    {
        $array = [
            ['icon' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/xcx/2.png',
                'text' => '热门推荐',
                'id' => '1',],
            ['icon' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/xcx/3.png',
                'text' => '附近机构',
                'id' => '2',],
            ['icon' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/xcx/4.png',
                'text' => '附近课程',
                'id' => '3',],
            ['icon' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/xcx/1.png',
                'text' => '成长中心',
                'id' => '4',]
        ];
//        $array =  [];
        return jsonResponseSuccess($array);



        $array = [
            '0' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/lunbo/20190412/b1.png',
            '1' => 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/lunbo/20190412/b2.png',
//            '2'=>'http://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/course/20191010/15706868592373.jpg',
        ];
//        $array = [
//            '0'=>'特权一内容',
//            '1'=>'特权二内容',
//            '2'=>'特权三内容',
//        ];
        $array = serialize($array);
        dump($array);
        $array = unserialize($array);


    }


}