<?php

namespace xltxlm\crontab\test\CrontabContinue;

use xltxlm\crontab\CrontabLock;
use xltxlm\redis\Config\RedisConfig;

include __DIR__ . '/../../vendor/autoload.php';

class myRedisConfig extends RedisConfig
{
    //redis-cli -h redis -p 6379 -a redispasswd123
    protected $Tns = 'redis';
    protected $Password = 'redispasswd123';
    protected $Port = 6379;
}

;

/**
 *
 */
class 修改主进程的名称_163_0
{
    use CrontabLock;

    /**
     * @inheritDoc
     */
    public function getNum(): int
    {
        return 3;
    }


    /**
     * @inheritDoc
     */
    public function getRedisCacheConfigObject(): RedisConfig
    {
        return new myRedisConfig;
    }

    /**
     * @inheritDoc
     */
    protected function getSleepSecond(): int
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    protected function whileRun()
    {
        p(cli_get_process_title());
        p('ok');
        sleep(50);
    }


}

(new 修改主进程的名称_163_0)();