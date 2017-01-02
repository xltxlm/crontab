<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 下午 3:18.
 */

namespace xltxlm\crontab\src;

include_once __DIR__ . '/../vendor/autoload.php';

/**
 * 监听文件的变化
 * Class Inotifywait.
 */
class Inotifywait
{
    public function __invoke()
    {
        $fp = fopen('php://stdin', 'r');
        while ($line = fgets($fp, 4096 * 10)) {
            file_put_contents(__DIR__ . '/Inotifywait.log', $line, FILE_APPEND);
        }
    }
}

(new Inotifywait())();
