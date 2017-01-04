<?php
namespace xltxlm\crontab\tests\Config;

use xltxlm\config\TestConfig;

/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2017/1/4
 * Time: 18:38
 */
class ConfigTest implements TestConfig
{
    /**
     * @inheritDoc
     */
    public function test()
    {
        echo "<pre>-->";
        print_r(__FILE__);
        echo "<--@in ".__FILE__." on line ".__LINE__."\n";
    }

}