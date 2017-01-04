<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/27
 * Time: 10:27.
 */

namespace xltxlm\crontab\LogFile;

use kuaigeng\review\Config\Mail;
use xltxlm\mail\MailSmtp;
use xltxlm\mail\Util\MailUserInfo;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/../../../vendor/autoload.php';

/**
 * 服务器心跳,一小时推送一次
 * Class Heartbeat.
 */
class Heartbeat
{
    public function __invoke()
    {
        while (true) {
            $shell = shell_exec('ps aux | grep php | grep -v grep | grep -v php-fpm');
            (new MailSmtp())
                ->setMailConfig(new Mail())
                ->setTitle($_SERVER['HOSTNAME'].'-服务器心跳')
                ->setBody(date('Y-m-d H:i:s').$shell)
                ->setTo((new MailUserInfo())->setEmail('xltxlm@qq.com')->setNickname('夏琳泰'))
                ->__invoke();
            sleep(3600);
        }
    }
}

(new Heartbeat())();
