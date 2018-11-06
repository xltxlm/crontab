<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/25
 * Time: 12:01.
 */

namespace xltxlm\crontab;

use xltxlm\helper\Ctroller\SetExceptionHandler;
use xltxlm\redis\Config\RedisConfig;
use xltxlm\redis\Features\Redis_LockKey;

/**
 * 借助文件锁,独占进程,必须在上级文件第一行php代码写上 declare(ticks = 1);
 * 采用父进程监听,子进程运行的策略
 * Class CrontabLock.
 */
trait CrontabLock
{
    /** @var int 保持活跃的进程数 - 个数 */
    protected $num = 1;
    protected $childlist = [];

    /** @var string 锁文件的路径 */
    private $lockFile = '';

    /**
     * @return string
     */
    public function getLockFile(): string
    {
        return $this->lockFile;
    }

    /**
     * @param string $lockFile
     * @return $this
     */
    public function setLockFile(string $lockFile)
    {
        $this->lockFile = $lockFile;
        return $this;
    }


    /** @var bool 是否开启redis并发锁 */
    protected $rediLock = true;

    /** @var bool */
    protected $记录执行日志 = true;

    /** @var string 业务锁的前缀 */
    protected $hKey = "";

    /** @var int 设置最大的循环次数 */
    protected $maxRuntimes = 0;

    /** @var RedisConfig 的服务 */
    protected $RedisCacheConfigObject;

    /**
     * @return RedisConfig
     */
    abstract public function getRedisCacheConfigObject(): RedisConfig;

    /**
     * @param RedisConfig $RedisCacheConfigObject
     * @return $this
     */
    public function setRedisCacheConfigObject(RedisConfig $RedisCacheConfigObject)
    {
        $this->RedisCacheConfigObject = $RedisCacheConfigObject;
        return $this;
    }


    /**
     * @return int
     */
    public function getMaxRuntimes(): int
    {
        return $this->maxRuntimes;
    }

    /**
     * @param int $maxRuntimes
     * @return CrontabLock
     */
    public function setMaxRuntimes(int $maxRuntimes): CrontabLock
    {
        $this->maxRuntimes = $maxRuntimes;
        return $this;
    }


    /**
     * @return string
     */
    public function getHKey(): string
    {
        return "{$_SERVER['dockername']}Crontab_" . strtr(static::class, ['\\' => '']);
    }

    /**
     * @param string $hKey
     * @return CrontabLock
     */
    public function setHKey(string $hKey)
    {
        $this->hKey = $hKey;
        return $this;
    }


    /**
     * @return int
     */
    public function getNum(): int
    {
        return $this->num;
    }

    /**
     * @param int $num
     * @return CrontabLock
     */
    public function setNum(int $num): CrontabLock
    {
        $this->num = $num;
        return $this;
    }


    /**
     * @return bool
     */
    public function is记录执行日志(): bool
    {
        return $this->记录执行日志;
    }

    /**
     * @param bool $记录执行日志
     * @return CrontabLock
     */
    public function set记录执行日志(bool $记录执行日志)
    {
        $this->记录执行日志 = $记录执行日志;
        return $this;
    }

    /**
     * @param array $childlist
     * @return CrontabLock
     */
    public function setChildlist(array $childlist): CrontabLock
    {
        $this->childlist = $childlist;
        return $this;
    }


    /**
     * 设置每个循环暂停的秒数.
     *
     * @return int
     */
    abstract protected function getSleepSecond(): int;

    /**
     * 运行的代码
     *
     * @return mixed
     */
    abstract protected function whileRun();

    public function log($str)
    {
        if ($this->is记录执行日志()) {
            if (is_object($str)) {
                error_log(date('c') . json_encode(get_object_vars($str), JSON_UNESCAPED_UNICODE) . "\n", 3, $this->getLockFile() . '.lock');
            } elseif (is_array($str)) {
                error_log(date('c') . json_encode($str, JSON_UNESCAPED_UNICODE) . "\n", 3, $this->getLockFile() . '.lock');
            } else {
                error_log(date('c') . $str . "\n", 3, $this->getLockFile() . '.lock');
            }
        }
    }

    /**
     * 返回当前进程所在的序号.如果传递的进程id，注销掉这个id
     */
    protected function 序号注销器(int $pid)
    {
        $lockKeyObject = $this->getRedisCacheConfigObject()->__invoke();
        $num = $lockKeyObject->hGet($this->getHKey() . 'list', $pid);
        $lockKeyObject->hDel($this->getHKey() . 'list', $pid);
    }

    /**
     * 返回当前进程所在的序号.
     */
    protected function 序号分发器(int $pid)
    {
        //等到本进程可以排他设置锁
        $lockKey = (new Redis_LockKey())
            ->setRedisConfig($this->getRedisCacheConfigObject())
            ->setExpire(1)
            ->setKey($this->getHKey() . 'Lockwait')
            ->setValue($pid);
        $获取不到锁 = !$lockKey
            ->__invoke();

        if ($获取不到锁) {
            die;
        }

        $lockKeyObject = $this->getRedisCacheConfigObject()->__invoke();
        //遍历现在的集合，找出空的位置
        $harrays = $lockKeyObject->hGetAll($this->getHKey() . 'list');
        $num = -1;
        for ($i = 0; $i < $this->getNum(); $i++) {
            $index = array_search($i, $harrays);
            if ($index === false) {
                $num = $i;
                break;
            } else {
                continue;
            }
        }
        $还有坑 = $num != -1;
        if ($还有坑) {
            if ($lockKeyObject->hSetNx($this->getHKey() . 'list', $pid, $num)) {
                //搞定之后,释放掉锁
                $lockKey->free();
                return $num;
            }
        }
        $lockKey->free();
        die;
    }

    /**
     * 确认当前进程排序的序号，用来做求摸多进程并发
     * @return array
     * @throws \Exception
     */
    protected function 确认序号()
    {
        $pid = (int)posix_getpid();
        //需要等上级把序号写进去了，再查。否则如果子进程运行比父进程快，那么就查询不到内容了
        $lockKeyObject = $this->getRedisCacheConfigObject()->__invoke();
        //遍历现在的集合，找出空的位置 ，
        $harrays = $lockKeyObject->hGetAll($this->getHKey() . 'list');
        if ($harrays[$pid] !== null) {
            return [$pid => $harrays[$pid]];
        }
        throw new \Exception(date('Y-m-d H:i:s') . "该进程【{$pid}】没有分发过序号:" . json_encode($harrays, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 运行任务
     */
    public function __invoke()
    {
        $mypid = (int)posix_getpid();
        $_SERVER['REQUEST_URI'] = static::class;
        $this->log('父进程:开始运行,进程id:' . $mypid);
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。

        //启动总任务的时候，清空掉redis队列
        $this->getRedisCacheConfigObject()->__invoke()->del($this->getHKey() . 'list');
        $Runtimes = 0;
        while (true) {
            $Runtimes++;
            //取消掉已经不存在的进程
            foreach ($this->childlist as $key => $pid) {
                //$this->log("监听pid：{$pid}");
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    unset($this->childlist[$key]);
                    $this->序号注销器($pid);
                }
            }

            $子进程数已经满了 = count($this->childlist) >= $this->getNum();
            if ($子进程数已经满了) {
                //$this->log("子进程已经满【" . count($this->childlist) . " >= {$this->getNum()}】：父进程休眠：{$this->getSleepSecond()}秒");
                sleep($this->getSleepSecond());
                continue;
            }

            $子进程全部退出 = count($this->childlist) == 0;
            if ($子进程全部退出) {
                $this->getRedisCacheConfigObject()->__invoke()->del($this->getHKey() . 'list');
            }


            //生成指定数量的进程数目
            for ($i = count($this->childlist); $i < $this->getNum(); $i++) {
                $pid = pcntl_fork(); //创建子进程
                if ($pid == 0) {
                    $pid = (int)posix_getpid();
                    //获取进程的序号
                    $num = $this->序号分发器($pid);
                    SetExceptionHandler::instance();
                    $this->log("生成进程【{$pid}】： $num");
                    //运行真实的代码
                    $this->whileRun($num);
                    $this->log("结束进程【{$pid}】： $num");
                    exit;
                } else {
                    $this->childlist[] = $pid;
                }
            }

            sleep($this->getSleepSecond());

            if ($this->getMaxRuntimes() && $Runtimes > $this->getMaxRuntimes()) {
                break;
            }
        }
        exit;
    }

    /**
     * 启动代码的时候,开启互斥. 设定每运行一次代码周期检测锁文件是不是被破坏了,破坏就退出
     * CrontabLock constructor.
     */
    final public function __construct()
    {
        $this->setLockFile((new \ReflectionClass(static::class))->getFileName());
    }

    /**
     * 只有父进程才能调用析构函数.
     */
    public function __destruct()
    {
        $pid = (int)posix_getpid();
        $this->序号注销器($pid);
    }

}
