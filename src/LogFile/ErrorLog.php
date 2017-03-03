<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 18:40.
 */

namespace xltxlm\crontab\LogFile;

use xltxlm\mail\MailSmtp;

eval("include_once '/var/www/html/vendor/autoload.php';");

$ErrorLog = (new MailLoadRequest);
$fp = fopen('php://stdin', 'r');
if ($fp) {
    while ($line = fgets($fp, 4096 * 10)) {
        if ($ErrorLog->getErrorstr()) {
            if (strpos(strtolower($line), strtolower($ErrorLog->getErrorstr())) === false) {
                log("没有包含关键词:".$ErrorLog->getErrorstr().":$line");
                continue;
            }
        }
        if ($line[0] == '{') {
            $body = var_export(json_decode($line, true), true);
        } else {
            $body = $line;
        }
        //保存最后一次运行的时间
        $time = file_get_contents(__DIR__.'/tmp');
        if (time() - $time < 10) {
            log("距离上一次时间间隔太小,跳过");
            continue;
        }
        file_put_contents(__DIR__.'/tmp', time());
        log("正常发送:".$body);
        (new MailSmtp())
            ->setMailConfig($ErrorLog->getMailConfig())
            ->setTitle($_SERVER['HOSTNAME'].':'.basename($ErrorLog->getFilepath()))
            ->setBody($body)
            ->setTo($ErrorLog->getMailUserInfo())
            ->__invoke();
    }
}
fclose($fp);

function log($str)
{
    file_put_contents(getcwd().'/'.basename(__FILE__).'.lock', '['.date('Y-m-d H:i:s').']'.$str."\n", FILE_APPEND);
}
