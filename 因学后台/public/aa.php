<?php

include_once './../vendor/aliyun-openapi-php-sdk-master/aliyun-php-sdk-core/Config.php';

use Sts\Request\V20150401 as Sts;

define("REGION_ID", "cn-shanghai");
define("ENDPOINT", "sts.cn-shanghai.aliyuncs.com");

// 只允许 RAM 用户使用角色
DefaultProfile::addEndpoint(REGION_ID, REGION_ID, "Sts", ENDPOINT);
$iClientProfile = DefaultProfile::getProfile(REGION_ID, "LTAI4FcZ8T69QuNjT2QJ2wV2", "yXhJp7TlRL8PYpoNJ36KCgF45e1fTx");
$client = new DefaultAcsClient($iClientProfile);
// 指定角色 ARN
$roleArn = "acs:ram::1896301315304732:role/oss-zi-jueseming";
// 在扮演角色时，添加一个权限策略，进一步限制角色的权限
// 以下权限策略表示拥有可以读取所有 OSS 的只读权限
$policy = <<<POLICY
{
    "Statement": [
        {
            "Action": "sts:AssumeRole",
            "Effect": "Allow",
            "Resource": "acs:ram::1896301315304732:role/oss-zi-jueseming"
        }
    ],
    "Version": "1"
}


POLICY;
$request = new Sts\AssumeRoleRequest();
// RoleSessionName 即临时身份的会话名称，用于区分不同的临时身份
$request->setRoleSessionName("client_name");
$request->setRoleArn($roleArn);
$request->setPolicy($policy);
$request->setDurationSeconds(3600);
try {
    $response = $client->getAcsResponse($request);
    print_r(json_encode($response));
//    print_r($response);
} catch (ServerException $e) {
    print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
} catch (ClientException $e) {
    print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
}
?>