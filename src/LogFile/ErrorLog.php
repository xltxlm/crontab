<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/23
 * Time: 18:40
 */
namespace xltxlm\crontab\LogFile;

use xltxlm\mail\MailSmtp;
use xltxlm\mail\Util\MailUserInfo;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/../../../vendor/autoload.php';

/**
 * 凡是文件出现一行内容,就是错误
 * Class ErrorLog
 * @package kuaigeng\review\Crontab\TiggerError
 */
final class ErrorLog
{
    public function __invoke()
    {
        $fp = fopen('php://stdin', 'r');
        if ($fp) {
            while ($line = fgets($fp, 4096 * 10)) {
                (new MailSmtp())
                    ->setMailConfig(new Mail())
                    ->setTitle('来自'.$_SERVER['HOSTNAME'].'的错误资源日志邮件')
                    ->setBody(var_export(json_decode($line, true), true))
                    ->setTo((new MailUserInfo())->setEmail('xltxlm@qq.com')->setNickname('夏琳泰'))
                    ->__invoke();
            }
            fclose($fp);
        }
    }
}

(new ErrorLog())();