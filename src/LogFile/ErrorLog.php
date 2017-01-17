<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 18:40.
 */

namespace xltxlm\crontab\LogFile;

use xltxlm\mail\MailSmtp;
use xltxlm\redis\Config\RedisConfig;
use xltxlm\redis\LockKey;

eval("include_once '/var/www/html/vendor/autoload.php';");

final class ErrorLog
{
    use MailLoad;
}

$ErrorLog = (new ErrorLog);
//为了方式写错误,导致邮件高并发.加上redis锁
$redisConfig = new class() extends RedisConfig
{
    protected $host = 'redis';
};

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
        $locked = (new LockKey())
            ->setRedisConfig($redisConfig)
            ->setKey(__FILE__)
            ->setExpire(30)
            ->__invoke();
        if (!$locked) {
            log("redis锁跳过:$line");
            continue;
        }
        log("正常发送:".$body);
        (new MailSmtp())
            ->setMailConfig($ErrorLog->getMailConfig())
            ->setTitle($_SERVER['HOSTNAME'].'的错误邮件:'.basename($ErrorLog->getFilepath()))
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
