<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/6 0006
 * Time: 19:09
 */

namespace app\pc\controller\v1;
use think\Db;
use think\Request;
use think\Response;
use app\common\model\Crud;

class A
{
    public  function aA(){

        $aa =Db::name('member')->where(['type'=>3,'user_type'=>2,'is_del'=>1])->field('uid')->select();
        foreach ($aa as $k=>$v){
            $where = [
                'mem_id'=>$v['uid'],
                'is_del'=>1
            ];
            $memder_data=Db::name('login_account')->where($where)->field('mem_id')->find();
            if(!$memder_data){
                $data = [
                    'password'=>'b7f60f75c797ac346adac77b493ed1f2',
                    'salt'=>'733a',
                    'create_time'=>time(),
                    'mem_id'=>$v['uid'],
                    'username'=>$v['uid'],
                    'token'=> md5(time() . rand(111111, 999999)),
                    'type'=> 2,
                ];
                $ac=Db::name('login_account')->insert($data);
                dump($ac);
            }



        }







//        $data = input();
//        $imgsa = $this->request->file('img');
//        if (!empty($imgsa)) {
//            $infos = uploadOss($this->request->file('img'), '/course');//路径上传到/course
//            if (false === $infos['status']) return json_encode(['code' => 0, 'msg' => $infos['msg']]);
//            $data['url'] = 'http://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images' . $infos['data']['savepath'];
//        }
//        dump($data);
    }

    //获取机构经纬度
    public static function getpcLongitudeLatitudes(){
        $where = [
            'is_del' => 1,
            'status' => 1,//1开启，2禁用
            'type' => ['neq',4]
        ];
        $table = 'member';
        $info = Crud::getData($table, $type = 2, $where, $field = 'uid,cname,longitude,latitude,logo', '', 1, 100000);
        if($info){
            foreach ($info as $k=>$v){
                if($v['logo']){
                    $logo = get_take_img($v['logo']);
                    if($logo){
                        $info[$k]['logo'] = $logo;
                    }
                }else{
                    $info[$k]['logo'] = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images/logo01.png';
                }
            }
            return jsonResponseSuccess($info);
        }else{
            throw new NothingMissException();
        }

    }

}