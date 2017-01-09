<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/27
 * Time: 10:27.
 */

namespace xltxlm\crontab\LogFile;

use xltxlm\mail\Config\MailConfig;
use xltxlm\mail\MailSmtp;
use xltxlm\mail\Util\MailUserInfo;

eval("include_once '/var/www/html/vendor/autoload.php';");

/**
 * 服务器心跳,一小时推送一次
 * Class Heartbeat.
 */
final class Heartbeat
{
    /** @var MailConfig MailConfig */
    protected $mailConfig;
    /** @var  MailUserInfo 账户信息 */
    protected $MailUserInfo;

    /**
     * @return MailConfig
     */
    public function getMailConfig(): MailConfig
    {
        return $this->mailConfig;
    }

    /**
     * @param MailConfig $mailConfig
     * @return Heartbeat
     */
    public function setMailConfig(MailConfig $mailConfig): Heartbeat
    {
        $this->mailConfig = $mailConfig;
        return $this;
    }

    /**
     * @return MailUserInfo
     */
    public function getMailUserInfo(): MailUserInfo
    {
        return $this->MailUserInfo;
    }

    /**
     * @param MailUserInfo $MailUserInfo
     * @return Heartbeat
     */
    public function setMailUserInfo(MailUserInfo $MailUserInfo): Heartbeat
    {
        $this->MailUserInfo = $MailUserInfo;
        return $this;
    }


    public function __invoke()
    {
        $shell = shell_exec('ps aux | grep php | grep -v grep | grep -v php-fpm');
        (new MailSmtp())
            ->setMailConfig($this->getMailConfig())
            ->setTitle($_SERVER['HOSTNAME'].'-服务器心跳')
            ->setBody(date('Y-m-d H:i:s').$shell)
            ->setTo($this->getMailUserInfo())
            ->__invoke();
    }
}
