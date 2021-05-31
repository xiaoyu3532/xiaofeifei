<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/16 0016
 * Time: 15:28
 */

namespace app\nxzback\controller\v1;
use OSS\OssClient;
use think\Loader;

class UpdateImg extends Base
{
    public static function getimageDoAliyunOss($imgBase64)
    {
        #转化base64编码图片
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgBase64, $res)) {
            //获取图片类型
            $type = $res[2];
            //图片名字
            $fileName = md5(time()) . '.' . $type;
            // 临时文件
            $tmpfname = tempnam("/image/", "FOO");
            //保存图片
            $handle = fopen($tmpfname, "w");
            //阿里云oss上传的文件目录
            $dst = 'images/nixingzhe/';
            if (fwrite($handle, base64_decode(str_replace($res[1], '', $imgBase64)))) {
                #上传图片至阿里云OSS
                $url = self::uploadImage($dst . $fileName, $tmpfname);
                #关闭缓存
                fclose($handle);
                #删除本地该图片
                unlink($tmpfname);
                #返回图片链接
                $returnUrl = 'https://yinxuejiaoyu.oss-cn-hangzhou.aliyuncs.com/'.$dst . $fileName;
                return jsonResponseSuccess($returnUrl);
//                return $returnUrl;
            } else {
                return '';
            }
        } else {
            return '';
        }

    }


    public static function uploadImage($dst, $getFile)
    {
        vendor('OSS.OssClient');
        vendor('OSS.Result.Result');
        vendor('OSS.Core.OssUtil');
        vendor('OSS.Core.MimeTypes');
        vendor('OSS.Http.RequestCore');
        vendor('OSS.Http.ResponseCore');
        vendor('OSS.Result.PutSetDeleteResult');

        #配置OSS基本配置
        $config = array(
            'KeyId' => 'LTAIqsjrmLGFSokh',
            'KeySecret' => 'QEeocXFxmEPBu9f6Orrw8insYk5IW2',
            'Endpoint' => 'http://oss-cn-hangzhou.aliyuncs.com',
            'Bucket' => 'yinxuejiaoyu',
        );
        $ossClient = new OssClient($config['KeyId'], $config['KeySecret'],
            $config['Endpoint']);
        #执行阿里云上传
        $result = $ossClient->uploadFile($config['Bucket'], $dst, $getFile);
        #返回
        return $result;
    }

}