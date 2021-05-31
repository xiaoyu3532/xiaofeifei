<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 18:38
 */

namespace app\jg\controller\v1;


use app\common\controller\Base;
use think\Controller;
use think\Request;

class UpdatePictures extends Controller
{
    //上传图片
    public  function  getUpdatePictures()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $imgsa = $this->request->file();

//        $file = $request->file();
        if (!empty($imgsa)) {
            $infos = uploadOss($this->request->file('file'), '/course');//路径上传到/course
            if (false === $infos['status']) return json_encode(['code' => 0, 'msg' => $infos['msg']]);
            $img = 'http://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images' . $infos['data']['savepath'];
            if($img){
                return $img;
            }else{
                return jsonResponse(2000,'图片获取失败',$img);
            }
        }
    }

    //上传图片
    public function getUpdateVideo()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST');
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $imgsa = $this->request->file('file');
        if (!empty($imgsa)) {
            $infos = uploadOss($this->request->file('file'), '/video');//路径上传到/course
            if (false === $infos['status']) return json_encode(['code' => 0, 'msg' => $infos['msg']]);
            $img = 'http://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images' . $infos['data']['savepath'];
            if($img){
                return $img;
            }else{
                return jsonResponse(2000,'图片获取失败',$img);
            }
        }
    }
}