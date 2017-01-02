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
    /** @var string Inotifywait 监控脚本的位置 */
    protected $InotifywaitSHPath = '';

    /**
     * @return string
     */
    public function getInotifywaitSHPath(): string
    {
        return $this->InotifywaitSHPath;
    }

    /**
     * @param string $InotifywaitSHPath
     *
     * @return CrontabMaker
     */
    public function setInotifywaitSHPath(string $InotifywaitSHPath): CrontabMaker
    {
        $this->InotifywaitSHPath = $InotifywaitSHPath;

        return $this;
    }

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
        echo "cd `dirname $0`\n";
        echo "while test \"1\" = \"1\"\ndo\n";
        $RecursiveDirectoryIterator = new \RecursiveIteratorIterator((new \RecursiveDirectoryIterator($this->getDir())));
        /** @var \SplFileInfo $item */
        foreach ($RecursiveDirectoryIterator as $item) {
            $classNameFromFile = (new ClassNameFromFile())
                ->setFilePath($item->getPathname());
            if (!in_array(CrontabLock::class, $classNameFromFile->getTraits())) {
                continue;
            }
            $path = strtr($item->getPathname(), [$this->getDir() => '', '\\' => '/']);
            echo "flock -xn .$path.flcok -c \" php .$path 2>&1 >>entrypoint.log &\" \n";
        }
        $inotifywaitSHPath = strtr(realpath($this->getInotifywaitSHPath()), [$this->getDir() => '', '\\' => '/']);
        if ($inotifywaitSHPath) {
            //得到Inotifywait的相对路径
            $getInotifywaitSHPaths = explode("/", strtr(realpath($this->getInotifywaitSHPath()), ['\\' => '/']));
            $getDirs = explode("/", strtr($this->getDir(), ['\\' => '/']));
            $array_intersect=array_intersect($getInotifywaitSHPaths,$getDirs);
            $array_intersect1=array_diff($getDirs,$getInotifywaitSHPaths);
            $array_intersect2=array_diff($getInotifywaitSHPaths,$array_intersect);
            $relatePath=str_repeat('../',count($array_intersect1)).join("/",$array_intersect2);
            echo "flock -xn $relatePath.flcok -c \"$relatePath 2>&1 >>entrypoint.log &\" \n";
        }
        echo "sleep 1\n";
        echo "echo -n .\n";
        echo "done\n";
        file_put_contents($this->getDir() . '/entrypoint.sh', ob_get_clean());
    }
}
