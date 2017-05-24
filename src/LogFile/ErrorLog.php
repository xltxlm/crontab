<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 18:40.
 */

namespace xltxlm\crontab\LogFile;


use xltxlm\helper\Util;

eval("include_once '/var/www/html/vendor/autoload.php';");

$ErrorLog = (new MailLoadRequest);
$fp = fopen('php://stdin', 'r');
if ($fp) {
    while ($line = fgets($fp, 4096 * 10)) {
        //在早上8点之前,不要发送任何错误信息,-没人处理
        if (date('H') < '08') {
            Util::error_log("早上8点前不要发送通知邮件");
        }
        if ($ErrorLog->getErrorstr()) {
            if (strpos(strtolower($line), strtolower($ErrorLog->getErrorstr())) === false) {
                Util::error_log("没有包含关键词:".$ErrorLog->getErrorstr().":$line");
                return;
            }
        }
        if ($line[0] == '{') {
            Util::error_log("json格式");
            $body = var_export(json_decode($line, true), true);
        } else {
            Util::error_log("普通字符串");
            $body = $line;
        }
        $MailModel = $ErrorLog->getNamespace().'Thrift\mail\mailModel';
        $mailModelObject = (new $MailModel);
        $mailModelObject
            ->setTitle($ErrorLog->getFilepath())
            ->setBody($body)
            ->setTo('me@xialintai.com')
            ->setHosttype($_SERVER['HOST_TYPE'])
            ->setProjectname($_SERVER['HOSTNAME'])
            ->setFromUserName('错误提示');


        $SsoThrift = $ErrorLog->getNamespace().'Config\\SsoThrift';
        $mail = $ErrorLog->getNamespace().'Thrift\mail\Client\Mail';
        $mailObject = (new $mail);
        $mailObject
            ->setThriftConfig(new $SsoThrift)
            ->setmailModel($mailModelObject)
            ->__invoke();
    }
}
fclose($fp);

