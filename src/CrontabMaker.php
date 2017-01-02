<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 下午 1:38.
 */

namespace xltxlm\crontab\src;

/**
 * 把集成 CrontabLock 的类集合在一起生成运行脚本
 * Class CrontabMaker.
 */
final class CrontabMaker
{
    /** @var string 定时任务类集合所在的目录 */
    protected $dir = '';

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     *
     * @return CrontabMaker
     */
    public function setDir(string $dir): CrontabMaker
    {
        $this->dir = $dir;

        return $this;
    }

    /**
     * 生成执行的脚本
     */
    public function __invoke()
    {
        file_put_contents($this->getDir() . '/entrypoint.sh', "");
    }
}
