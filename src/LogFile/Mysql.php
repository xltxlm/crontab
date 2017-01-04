#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 15:38.
 */

namespace xltxlm\crontab\LogFile;

use kuaigeng\review\Config\Mail;
use xltxlm\mail\MailSmtp;
use xltxlm\crontab\CrontabLock;
use xltxlm\mail\Util\MailUserInfo;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/../../../vendor/autoload.php';

class Mysql
{
    public function __invoke()
    {
        $fp = fopen('php://stdin', 'r');
        if ($fp) {
            while ($line = fgets($fp, 4096 * 10)) {
                $json = json_decode($line, true);
                if (empty($json) || $json['times'] < 10) {
                    continue;
                }
                (new MailSmtp())
                    ->setMailConfig(new Mail())
                    ->setTitle('来自'.$_SERVER['HOSTNAME'].'的SQL邮件')
                    ->setBody(var_export($json, true))
                    ->setTo((new MailUserInfo())->setEmail('xltxlm@qq.com')->setNickname('夏琳泰'))
                    ->__invoke();
            }
            fclose($fp);
        }
    }
}

(new Mysql())();
