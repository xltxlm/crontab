<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/25
 * Time: 12:01.
 */

namespace xltxlm\crontab;

use xltxlm\helper\Ctroller\SetExceptionHandler;
use xltxlm\logger\Log\DefineLog;
use xltxlm\redis\Config\RedisConfig;
use xltxlm\redis\Features\Redis_LockKey;

/**
 * 借助文件锁,独占进程,必须在上级文件第一行php代码写上 declare(ticks = 1);
 * 采用父进程监听,子进程运行的策略
 * Class CrontabLock.
 */
trait CrontabLock
{
    use CrontabLog {
        log as  logOrigin;
    }
    /** @var int 保持活跃的进程数 - 个数 */
    protected $num = 1;
    protected $childlist = [];

    /** @var bool */
    protected $记录执行日志 = true;

    /** @var string 业务锁的前缀 */
    protected $hKey = "";

    /** @var int 设置最大的循环次数 */
    protected $maxRuntimes = 0;

    /** @var RedisConfig 的服务 */
    protected $RedisCacheConfigObject;

    /** @var int 当前的进程号码 */
    protected $currentMod = 0;

    /**
     * @return int
     */
    public function getCurrentMod(): int
    {
        return $this->currentMod;
    }

    /**
     * @param int $currentMod
     * @return $this
     */
    public function setCurrentMod(int $currentMod)
    {
        $this->currentMod = $currentMod;
        return $this;
    }


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
            $this->logOrigin($str, $this->getCurrentMod());
        }
    }

    /**
     * 返回当前进程所在的序号.如果传递的进程id，注销掉这个id
     */
    protected function 序号注销器(int $pid)
    {
        $lockKeyObject = $this->getRedisCacheConfigObject()->__invoke();
        $lockKeyObject->hDel($this->getHKey() . 'list', $this->getCurrentMod());
    }

    /**
     * 返回当前进程所在的序号.
     */
    protected function 序号分发器(int $pid, int $inum)
    {
        $lockKeyObject = $this->getRedisCacheConfigObject()->__invoke();
        $alllistkey = $this->getHKey() . 'list';
        $harrays = $lockKeyObject->hGetAll($alllistkey);
        if ($lockKeyObject->hSetNx($alllistkey, $inum, $pid)) {
            return $inum;
        }
        $this->log("$inum 无法排上号[$alllistkey, $inum, $pid].全部数据:" . json_encode($harrays, true));
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
        $index = array_search($pid, $harrays);
        if ($index !== false) {
            return [$pid => $index];
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

        $crontabclassname = (new \ReflectionClass(static::class))->getFileName();
        cli_set_process_title(basename($crontabclassname) . ".php@Totalx{$this->getNum()}");
        $filemd5 = md5_file($crontabclassname);

        //启动总任务的时候，清空掉redis队列
        $this->getRedisCacheConfigObject()->__invoke()->del($this->getHKey() . 'list');
        $Runtimes = 0;
        while (true) {
            //每次进来之前先检测下文件的md5值在决定重启
            $filemd5_new = md5_file($crontabclassname);
            if ($filemd5_new != $filemd5) {
                foreach ($this->childlist as $key => $pid) {
                    posix_kill($pid,SIGKILL);
                }
                $this->log("文件的内容发生改变了,重启进程");
                exit;
            }
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
                sleep($this->getSleepSecond());
                continue;
            }

            $子进程全部退出 = count($this->childlist) == 0;
            if ($子进程全部退出) {
                $this->getRedisCacheConfigObject()->__invoke()->del($this->getHKey() . 'list');
            }


            //生成指定数量的进程数目
            for ($i = 0; $i < $this->getNum(); $i++) {
                if ($this->childlist[$i]) {
                    continue;
                }

                //等到本进程可以排他设置锁
                $lockKey = (new Redis_LockKey())
                    ->setRedisConfig($this->getRedisCacheConfigObject())
                    ->setExpire(1)
                    ->setKey($this->getHKey() . 'Lockwait')
                    ->setValue($i);
                $获取不到锁 = $lockKey->__invoke() == false;

                if ($获取不到锁) {
                    /** @var \Redis $redisclient */
                    $redisclient = $this->getRedisCacheConfigObject()->__invoke();
                    $this->log("$i 排队失败,前面是:" . $redisclient->get($this->getHKey() . 'Lockwait'));
                    $i--;
                    sleep(1);
                    continue;
                }
                $lockKey->free();

                //创建子进程
                $pid = pcntl_fork();
                if ($pid == 0) {
                    //切割php调试日志,按照小时进行分组
                    ini_set('error_log', "/opt/logs/php_errors_" . date('Ymd') . ".log");
                    $basename = basename($crontabclassname, '.php');
                    $_SERVER['logid'] = $basename . '_' . DefineLog::getUniqid_static();
                    $pid = (int)posix_getpid();
                    //获取进程的序号
                    $this->序号分发器($pid, $i);
                    SetExceptionHandler::instance();
                    cli_set_process_title($basename . ".php@{$i}x{$this->getNum()}");
                    $this->setCurrentMod($i);
                    //运行真实的代码
                    $this->whileRun($i);
                    exit;
                } else {
                    $this->childlist[$i] = $pid;
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
     * 只有父进程才能调用析构函数.
     */
    public function __destruct()
    {
        $pid = (int)posix_getpid();
        $this->序号注销器($pid);
    }

}
