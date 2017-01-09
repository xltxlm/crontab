<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 18:40.
 */

namespace xltxlm\crontab\LogFile;

use xltxlm\mail\MailSmtp;
use xltxlm\mail\Util\MailUserInfo;
use xltxlm\redis\Config\RedisConfig;
use xltxlm\redis\LockKey;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/../../../../autoload.php';

/**
 * 凡是文件出现一行内容,就是错误
 * Class ErrorLog.
 */
final class ErrorLog
{
    use MailLoad;

    /**
     * @return bool
     */
    public function __invoke()
    {
        //为了方式写错误,导致邮件高并发.加上redis锁
        $redisConfig = new class() extends RedisConfig
        {
            protected $host = 'redis';
        };
        $locked = (new LockKey())
            ->setRedisConfig($redisConfig)
            ->setKey(__FILE__)
            ->setValue(date('Y-m-d H:i:s'))
            ->setExpire(5)
            ->__invoke();
        if (!$locked) {
            return false;
        }

        $fp = fopen('php://stdin', 'r');
        if ($fp) {
            while ($line = fgets($fp, 4096 * 10)) {
                if ($this->getErrorstr()) {
                    if (strpos($line, $this->getErrorstr()) === false) {
                        continue;
                    }
                }
                if ($line[0] == '{') {
                    $body = var_export(json_decode($line, true), true);
                } else {
                    $body = $line;
                }
                (new MailSmtp())
                    ->setMailConfig($this->getMailConfig())
                    ->setTitle($_SERVER['HOSTNAME'].'的错误邮件:'.$this->getFilepath())
                    ->setBody($body)
                    ->setTo($this->getMailUserInfo())
                    ->__invoke();
            }
            fclose($fp);
        }

        return true;
    }
}

(new ErrorLog())();
