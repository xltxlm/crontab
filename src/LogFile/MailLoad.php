<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2017/1/9
 * Time: 12:41.
 */

namespace xltxlm\crontab\LogFile;

use xltxlm\helper\Ctroller\Request\Request;
use xltxlm\mail\Config\MailConfig;

/**
 * 加载邮件配置类
 * Class MailLoad.
 */
trait MailLoad
{
    use Request;
    /** @var string MailConfig */
    protected $mailConfig;

    /**
     * @return MailConfig
     */
    public function getMailConfig()
    {
        return new $this->mailConfig;
    }

    /**
     * @param string $mailConfig
     *
     * @return MailLoad
     */
    public function setMailConfig($mailConfig)
    {
        $this->mailConfig = $mailConfig;

        return $this;
    }
}
