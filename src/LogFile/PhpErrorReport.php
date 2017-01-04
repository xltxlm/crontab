<?php
namespace xltxlm\crontab\LogFile;

use kuaigeng\review\Config\Mail;
use xltxlm\mail\MailSmtp;
use xltxlm\crontab\CrontabLock;
use xltxlm\mail\Util\MailUserInfo;

include_once __DIR__.'/../../vendor/autoload.php';
include_once __DIR__.'/../../../vendor/autoload.php';

/**
 * Class Report.
 */
class PhpErrorReport
{
    public function __invoke()
    {
        $fp = fopen('php://stdin', 'r');
        if ($fp) {
            while ($line = fgets($fp, 4096)) {
                if (strpos($line, 'Fatal error') === false) {
                    continue;
                }
                (new MailSmtp())
                    ->setMailConfig(new Mail())
                    ->setTitle('来自'.$_SERVER['HOSTNAME'].'的报警邮件')
                    ->setBody($line)
                    ->setTo((new MailUserInfo())->setEmail('xltxlm@qq.com')->setNickname('夏琳泰'))
                    ->__invoke();
            }
            fclose($fp);
        }
    }
}

(new PhpErrorReport())();
