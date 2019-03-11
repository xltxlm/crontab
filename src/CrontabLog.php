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
    /**
     * @param mixed $message
     * @param int $mod 如果mod>0 那么会写入新的.lock$mod文件里面
     * @throws \ReflectionException
     */
    protected function log($message, $mod = 0)
    {
        $filepath = (new \ReflectionClass(static::class))
            ->getShortName();
        $newfilepath = "/opt/logs/" . $filepath . date('Ymd') . '.lock' . ($mod ?: '');
        if (is_object($message)) {
            error_log(date('c|') . json_encode(get_object_vars($message), JSON_UNESCAPED_UNICODE) . "\n", 3, $newfilepath);
        } elseif (is_string($message)) {
            error_log(date('c|') . $message . "\n", 3, $newfilepath);
        } else {
            error_log(date('c|') . json_encode($message, JSON_UNESCAPED_UNICODE) . "\n", 3, $newfilepath);
        }
    }
}