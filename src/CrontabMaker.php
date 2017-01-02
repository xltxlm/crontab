<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 下午 1:38.
 */

namespace xltxlm\crontab\src;

use xltxlm\crontab\CrontabLock;
use xltxlm\helper\Hclass\ClassNameFromFile;

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
     * 生成执行的脚本.
     */
    public function __invoke()
    {
        ob_start();
        echo "#!/usr/bin/env bash\n";
        echo "cd " . $this->getDir() . "\n";
        $RecursiveDirectoryIterator = new \RecursiveIteratorIterator((new \RecursiveDirectoryIterator($this->getDir())));
        /** @var \SplFileInfo $item */
        foreach ($RecursiveDirectoryIterator as $item) {
            $classNameFromFile = (new ClassNameFromFile())
                ->setFilePath($item->getPathname());
            if (!in_array(CrontabLock::class, $classNameFromFile->getTraits())) {
                continue;
            }
            $path = strtr($item->getPathname(), [$this->getDir() => "", '\\' => '/']);
            echo "php .$path 2>&1 >>entrypoint.log &\n";
        }
        file_put_contents($this->getDir() . '/entrypoint.sh', ob_get_clean());
    }
}
