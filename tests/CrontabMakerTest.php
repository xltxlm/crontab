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

class CrontabMakerTest extends TestCase
{

    public function test()
    {
        (new CrontabMaker())
            ->setDir(__DIR__)
            ->__invoke();
    }
}