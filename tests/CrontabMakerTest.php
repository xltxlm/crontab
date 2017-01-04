<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 下午 1:48
 */

namespace xltxlm\crontab\tests;


use PHPUnit\Framework\TestCase;
use xltxlm\crontab\src\CrontabMaker;
use xltxlm\crontab\Unit\Tail;

class CrontabMakerTest extends TestCase
{

    public function test()
    {
        (new CrontabMaker())
            ->setCrontabDir(__DIR__)
            ->setInotifywaitSHPath(__DIR__."/../src/Inotifywait.sh")
            ->setConfigDir(__DIR__)
            ->setTails(
                (new Tail())
                    ->setClassFilePath(__DIR__."/../src/LogFile/PhpErrorReport.php")
                    ->setFile('/opt/log/php_error.log')
            )
            ->__invoke();
    }
}