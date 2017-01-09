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
use xltxlm\mail\Util\MailUserInfo;

//DOS方式下的运行
if (php_sapi_name() == 'cli') {
    unset($_SERVER['argv'][0]);
    foreach ($_SERVER['argv'] as $k1 => $v1) {
        $str_array = [];
        parse_str(
            $v1,
            $str_array
        );
        if (count($str_array) > 1) {
            $key = key($str_array);
            $current = current($str_array);
            array_shift($str_array);
            $str_array[$key] = $current.'&'.http_build_query($str_array);
            $_GET = $str_array + $_GET;
            unset($str_array);
        } else {
            $_GET = $str_array + $_GET;
        }
    }
    $_REQUEST = $_GET;
}

/**
 * 加载邮件配置类
 * Class MailLoad.
 */
trait MailLoad
{
    use Request;
    /** @var string MailConfig */
    protected $mailConfig;
    /** @var MailUserInfo 账户信息 */
    protected $MailUserInfo;
    /** @var string 只查找特定的关键词 */
    protected $errorstr = '';
    /** @var string 监控的文件路径 */
    protected $filepath = '';

    /**
     * @return string
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * @param string $filepath
     *
     * @return $this
     */
    public function setFilepath(string $filepath)
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorstr(): string
    {
        return $this->errorstr;
    }

    /**
     * @param string $errorstr
     *
     * @return $this
     */
    public function setErrorstr(string $errorstr)
    {
        $this->errorstr = $errorstr;

        return $this;
    }

    /**
     * @return MailUserInfo
     */
    public function getMailUserInfo(): MailUserInfo
    {
        return new $this->MailUserInfo();
    }

    /**
     * @param MailUserInfo $MailUserInfo
     *
     * @return MailLoad
     */
    public function setMailUserInfo($MailUserInfo)
    {
        $this->MailUserInfo = $MailUserInfo;

        return $this;
    }

    /**
     * @return MailConfig
     */
    public function getMailConfig()
    {
        return new $this->mailConfig();
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
