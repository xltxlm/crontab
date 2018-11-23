<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2018/4/27
 * Time: 19:23
 */

namespace xltxlm\crontab;

/**
 * 在用当前文件的路径.lock记录日志
 * Trait CrontabLog
 * @package xltxlm\crontab
 */
trait CrontabLog
{
    final protected function log($message)
    {
        $filepath = (new \ReflectionClass(static::class))
            ->getFileName();
        if (is_string($message)) {
            error_log(date('c|') . $message . "\n", 3, $filepath . '.lock');
        } else {
            error_log(date('c|') . json_encode($message, JSON_UNESCAPED_UNICODE) . "\n", 3, $filepath . '.lock');
        }
    }
}