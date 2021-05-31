<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/21 0021
 * Time: 14:00
 */

namespace app\jg\controller\v2;

use think\Controller;

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST');
header('Access-Control-Allow-Headers:x-requested-with,content-type');
header('Access-Control-Allow-Headers:Origin, Content-Type, Cookie,X-CSRF-TOKEN, Accept,Authorization');

class UpdataImg extends Controller
{
    public function setUpdataImg()
    {
        if ($this->request->ispost()) {
            $imgsa = $this->request->file('file');
            if (!empty($imgsa)) {
                $infos = uploadOss($this->request->file('file'), '/zht');//路径上传到/course
                if (false === $infos['status']) return json_encode(['code' => 0, 'msg' => $infos['msg']]);
                $img = 'http://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/images' . $infos['data']['savepath'];
                return $img;
            }
        }
    }


}

