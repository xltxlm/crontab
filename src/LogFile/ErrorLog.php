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
        $locked = (new LockKey())
            ->setRedisConfig($redisConfig)
            ->setKey(__FILE__)
            ->setValue(date('Y-m-d H:i:s'))
            ->setExpire(5)
            ->__invoke();
        if (!$locked) {
            continue;
        }
        if ($ErrorLog->getErrorstr()) {
            if (strpos($line, $ErrorLog->getErrorstr()) === false) {
                continue;
            }
        }
        if ($line[0] == '{') {
            $body = var_export(json_decode($line, true), true);
        } else {
            $body = $line;
        }
        (new MailSmtp())
            ->setMailConfig($ErrorLog->getMailConfig())
            ->setTitle($_SERVER['HOSTNAME'].'的错误邮件:'.$ErrorLog->getFilepath())
            ->setBody($body)
            ->setTo($ErrorLog->getMailUserInfo())
            ->__invoke();
    }
}
fclose($fp);
