<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 上午 11:16.
 */
declare(ticks = 1);

namespace xltxlm\crontab\tests;

use xltxlm\crontab\CrontabLock;

require __DIR__ . '/../vendor/autoload.php';

class CrontabLockDemo
{
    use CrontabLock;

    /**
     * {@inheritdoc}
     */
    protected function getSleepSecond(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function whileRun()
    {
        $this->log("实际代码:good!" . date("Y-m-d H:i:s"));
    }
}

(new CrontabLockDemo())();
