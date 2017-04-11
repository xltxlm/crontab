<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-01-02
 * Time: 下午 1:38.
 */

namespace xltxlm\crontab;

use xltxlm\config\TestConfig;
use xltxlm\crontab\Unit\Tail;
use xltxlm\helper\Hclass\ClassNameFromFile;

/**
 * 把集成 CrontabLock 的类集合在一起生成运行脚本
 * Class CrontabMaker.
 */
final class CrontabMaker
{
    /** @var string 定时任务类集合所在的目录 */
    protected $crontabDir = '';
    /** @var string 配置文件夹 */
    protected $configDir = '';
    /** @var array 测试依赖的外部环境的 */
    protected $configClass = [];
    /** @var string Inotifywait 监控脚本的位置 */
    protected $InotifywaitSHPath = '';
    /** @var Tail[] 文件跟踪 */
    protected $tails = [];

    /**
     * @return array
     */
    public function getConfigClass(): array
    {
        return $this->configClass;
    }

    /**
     * @param string $configClass
     *
     * @return CrontabMaker
     */
    public function setConfigClass(string $configClass): CrontabMaker
    {
        $this->configClass[] = $configClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    /**
     * @param string $configDir
     *
     * @return CrontabMaker
     */
    public function setConfigDir(string $configDir): CrontabMaker
    {
        $this->configDir = $configDir;

        return $this;
    }

    /**
     * @return Tail[]
     */
    public function getTails(): array
    {
        return $this->tails;
    }

    /**
     * @param Tail $tails
     *
     * @return CrontabMaker
     */
    public function setTails(Tail $tails): CrontabMaker
    {
        $this->tails[] = $tails;

        return $this;
    }

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
    public function getCrontabDir(): string
    {
        return realpath($this->crontabDir);
    }

    /**
     * @param string $crontabDir
     *
     * @return CrontabMaker
     */
    public function setCrontabDir(string $crontabDir): CrontabMaker
    {
        $this->crontabDir = $crontabDir;

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
        echo "\n\n#========1:定时任务类===========\n\n";
        $RecursiveDirectoryIterator = new \RecursiveIteratorIterator((new \RecursiveDirectoryIterator($this->getCrontabDir())));
        /** @var \SplFileInfo $item */
        foreach ($RecursiveDirectoryIterator as $item) {
            //必须是php后缀的
            if (strpos($item, '.php') === false) {
                continue;
            }
            //单元测试的文件不要
            if(strpos(file_get_contents($item->getRealPath()),'PHPUnit\\Framework\\TestCase')!==false)
            {
                continue;
            }
            $classNameFromFile = (new ClassNameFromFile())
                ->setFilePath($item->getPathname());
            if (!$classNameFromFile->getClassName()) {
                continue;
            }
            if (!in_array(CrontabLock::class, $classNameFromFile->getTraits())) {
                continue;
            }
            $path = strtr($item->getPathname(), [$this->getCrontabDir() => '', '\\' => '/']);
            echo 'flock -xn '.md5($path).".flock -c \" php .$path\" & \n";
        }
        echo "\n\n#========2:项目文件变化监控===========\n\n";
        $inotifywaitSHPath = strtr(realpath($this->getInotifywaitSHPath()), [$this->getCrontabDir() => '', '\\' => '/']);
        if ($this->getInotifywaitSHPath() && $inotifywaitSHPath) {
            //得到Inotifywait的相对路径
            $getInotifywaitSHPaths = explode('/', strtr(realpath($this->getInotifywaitSHPath()), ['\\' => '/']));
            $getDirs = explode('/', strtr($this->getCrontabDir(), ['\\' => '/']));
            $array_intersect = array_intersect($getInotifywaitSHPaths, $getDirs);
            $array_intersect1 = array_diff($getDirs, $getInotifywaitSHPaths);
            $array_intersect2 = array_diff($getInotifywaitSHPaths, $array_intersect);
            $relatePath = str_repeat('../', count($array_intersect1)).implode('/', $array_intersect2);
            echo "flock -xn $relatePath.flock -c \"$relatePath 2>&1 >>entrypoint.log\" & \n";
        }
        echo "\n\n#========3:日志文件变化跟踪===========\n\n";
        foreach ($this->getTails() as $tail) {
            $relatePath = md5($tail->getFile());
            echo "flock -xn $relatePath.flock -c \"".'tail -f  '.$tail->getFile().' | php '.$tail->getClassFilePath().
                ' mailConfig='.$tail->getMailClass().' mailUserInfo='.$tail->getMailUserInfo().
                " errorstr='".$tail->getErrorstr()."' filepath=".array_shift(explode(' ', $tail->getFile()))."\" &\n";
        }
        echo "\n\n#========4:资源链接测试===========\n\n";
        echo "sleep 10\n";
        $this->makeResourceTest();
        echo "\n\n#========[END]===========\n\n";
        echo "echo -n .\n";
        file_put_contents($this->getCrontabDir().'/entrypoint.sh', ob_get_clean());
        $this->test();
    }

    public function test()
    {
        ob_start();
        echo "#!/usr/bin/env bash\n";
        echo "cd `dirname $0`\n";
        echo "echo \$HOST_TYPE\n";
        echo "echo \$HOSTNAME\n";
        $this->makeResourceTest();
        file_put_contents($this->getCrontabDir().'/entrypointtest.sh', ob_get_clean());
    }

    protected function makeResourceTest()
    {
        if ($this->getConfigDir()) {
            $RecursiveDirectoryIterator = (new \DirectoryIterator($this->getConfigDir()));
            foreach ($RecursiveDirectoryIterator as $item) {

                //必须是php后缀的
                if (strpos($item, '.php') === false) {
                    continue;
                }
                //单元测试的文件不要
                if(strpos(file_get_contents($item->getRealPath()),'PHPUnit\\Framework\\TestCase')!==false)
                {
                    continue;
                }
                $classNameFromFile = (new ClassNameFromFile())
                    ->setFilePath($item->getPathname());
                if (!$classNameFromFile->getClassName() || in_array(CrontabLock::class, $classNameFromFile->getTraits())) {
                    continue;
                }
                if (strpos(file_get_contents($item->getPathname()), 'autoload.php') !== false) {
                    continue;
                }
                eval('include_once $item->getPathname();');
                $implementsInterface = (new \ReflectionClass($classNameFromFile->getClassName()))
                    ->implementsInterface(TestConfig::class);
                if ($implementsInterface) {
                    $path = strtr($item->getPathname(), [$this->getConfigDir() => '', '\\' => '/']);
                    echo "php -r 'include \"/var/www/html/vendor/autoload.php\";include \".$path\";(new ".$classNameFromFile->getClassName().")->test(); ' & \n";
                }
            }
        }
    }

}
