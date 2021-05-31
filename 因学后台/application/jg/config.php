<?php
//index模块配置文件
return [
    //阿里云OSS
    'oss' => [
        'accessKeyId' => 'LTAIqsjrmLGFSokh',//1jwMuGMQhytQ7mtR
        'accessKeySecret' => 'QEeocXFxmEPBu9f6Orrw8insYk5IW2',//wzaGVWCfU7fNjnFd9zvtzHfHAJ4MXx
        'endpoint' => 'http://oss-cn-hangzhou.aliyuncs.com',
        'bucket' => 'yinxuejiaoyu'
    ],
    'wechat' => [
        $appid = "wxf47f6fd95e94366e",
        $secret = "1b1b6e96026a679dd159de4408361fb1",
        'notify_url' => 'https://qipei.ytqpsw.com/phone/Wechat/wechat_notify', //支付回调地址
        'wx_key' => 'zxy11111111111111111111111111111', //商户平台密钥
        'mch_id' => '1493177642',   //商户号
    ],
//    'Wechat' => [
//        'appid' => 'wx61e28502ff791d90',
//        'mch_id' => '1493177642',
//        'RETURN_URL' => 'http://zht.insooner.com/jg/v1/Wxpay/wachatCallbackWebpay', //回调地址
//    ],
//    'WechatH5' => [
//        'appid' => 'wx61e28502ff791d90',
//        'mch_id' => '1493177642',
//        'RETURN_URL' => 'http://zht.insooner.com/jg/v1/Wxpay/wachatCallbackWebpay', //回调地址
//    ],
    'AlipayH5'=>[
        //应用ID,您的APPID。
        'app_id' => "2017091808802313",

        //商户私钥，您的原始格式RSA私钥
        'merchant_private_key' => "MIIEowIBAAKCAQEAzOB6o3IDuQp4puem5wrLYHLOZldMQc97YEdxz9Fj8Bzy5/Ncc5qYZfGZanJAeMkrGn0r8z/8jO9dC4Q4ui4x4yRW33ZqmdI7fNmucNOnh/7G4cWUjqxJKXXmQQGxN/+GOnuwGoZirVtLwwRRxj0uDRHqYH/7EoNt+KvEuFoThZLHycnwLoxt+7FX5T0GrzhSkbQh4i8M/lF/bQcohkLWvIwyKmJWDYVXLwBmv2GNBwX54GkpjlOX4lo3l+aJsKiVL/6+3YPGI9Yuoaw8GVOiPM60C2Vr4HnU+Qmn8/m2QNQ2pQtz15HbbAePxh6qyQSgBMjrt2s6O9j7LZ41SAna2wIDAQABAoIBAQCQSH7V4JOymydBE9880yNLd07YUB6KMl6G/Ymve51QGnMO2xp+557wHGeYyYGSDspmS0TKeIOZlXEHjUSOCb5kYtEzaqfEUIRIdt0c5FIVul3B3m2y1K5pnnhby59M+o1DXpw08fNIdwUyADa+z5NA7R8MetUMwraN7BoMYpNg+y0Oj/11LpYlXrKhQyR+c42fWn1L2yIGTmLXzK716PcTLsjDYDoStDpgqooPORpopo3ADNQUdDFgjdriiJUOQMiownEiKwpP858yvG0OwB/w5jq5WR7/pmvIeKUczDXJkolD5BwwyP7phdJ6CdL8CtZB21WaYgA6uTXjyeKZLb1BAoGBAO8HbSOf+JcqO1EA3NlemRy0gECF1b2Iqqscih8pJkL6UmVwcBjQb+7zZvpvPvM5OiEHg2x/1f+G0c6Zo6rHMtHjVc3Tzl6Q2I7RvmnsYx1tU/3k2oAL7M7zL2HkDmLIKCGNe63zNvf1lT0odLiV7hUmfBjOMPFq2PsxFKJ3rVrRAoGBANtsTlVR12mBTqr7/pzh6Ro5w7ZfTvMtdEgr+M+1ZHp8wqWcSdLneZiHLxb+7QAB/vlNiku1k4njQ3rAXRNNC6DVygW5Oczn695dhqHeaiRQtx3aYH+fOoachPfyP5k9Aro8Tlr7H3xQeFuDct+QRdIHK4kvwtQT351+oTYOlO3rAoGADK5zLuGs2bBG51xJW0r2ipxU9ZdkKKMYku13soGHYyROvM0DVX2xgpbtTroaN+NAX0I7ycTagK0RcomaMlRRMOuDwODM4R2EL8eW952wAH6tZxn+Ma7wSGaEjAgCb2E5J9aOykLOFsezvEPqNWTW9c5N5S8DT7ugeWs4MgpxaxECgYBBVZx1dysG9UOxUdtcZz/7WRvXX8WoTu6C1uT9I+vJNQDYQxMQQ3BHZGk3Fa0IBZAgN2BobqaBtjPPhxuvtY8y0rWWwrJdOulWis6dwBYmvgnoT6/QEF9i2ZQWKAGb5Ti8r1w9ZuzXHTbZOOipfNHtWckyzg/bChfZU205JVpfBQKBgGBKEHhZABc5BLTQGrLWOszsDco7uqw8pwBvJFwrySLNXU4tmeMcNerr5WN1+Ezi/YDa1Fi5YQUKCsNz6/e8EYN8VizTWiFAzXGkOTi+1tlYv7TSKrXQUWu0agZWL3cO/oHvEkWzEP0+bAFlXu61oB5cnIlDJ9uIGp6ufYO0Qqoh",

        //异步通知地址
        'notify_url' => "https://zht.insooner.com/jg/v2/alipay_wap_callback",

        //同步跳转
        'return_url' => "https://zht.insooner.com/INGowthpay/?INGowthpay=",

        //编码格式
        'charset' => "UTF-8",

        //签名方式
        'sign_type'=>"RSA2",

        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB",
    ],




];