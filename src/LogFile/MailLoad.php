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
            $str_array[$key] = $current . "&" . http_build_query($str_array);
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
