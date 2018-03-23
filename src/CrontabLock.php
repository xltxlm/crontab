<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/25
 * Time: 12:01.
 */

namespace xltxlm\crontab;

use xltxlm\crontab\Config\RedisCacheConfig;
use xltxlm\helper\Ctroller\SetExceptionHandler;
use xltxlm\redis\LockKey;

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
    /** @var resource 锁文件的句柄 */
    private $fp;
    /** @var string 锁文件的路径 */
    private $lockFile = '';
    /** @var bool 是否开启redis并发锁 */
    protected $rediLock = true;

    /** @var bool */
    protected $记录执行日志 = true;

    /** @var string 业务锁的前缀 */
    protected $hKey = "";

    /** @var int 设置最大的循环次数 */
    protected $maxRuntimes = 0;

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
        return "{$_SERVER['HOSTNAME']}Crontab_" . static::class;
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
     * @return bool
     */
    public function isRediLock(): bool
    {
        return $this->rediLock;
    }

    /**
     * @param bool $rediLock
     * @return $this
     */
    public function setRediLock(bool $rediLock)
    {
        $this->rediLock = $rediLock;
        return $this;
    }

    /**
     * @return array
     */
    public function getChildlist(): array
    {
        return $this->childlist;
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
            fwrite($this->fp, '[' . date('Y-m-d H:i:s') . ']' . $str . "\n");
        }
    }

    /**
     * 返回当前进程所在的序号.如果传递的进程id，注销掉这个id
     */
    protected function 序号注销器(int $pid)
    {
        $lockKeyObject = (new RedisCacheConfig())->__invoke();
        $num = $lockKeyObject->hGet($this->getHKey() . 'list', $pid);
        $lockKeyObject->hDel($this->getHKey() . 'list', $pid);
        $this->log("进程id:[$pid]注销序号:[$num]");
    }

    /**
     * 返回当前进程所在的序号.
     */
    protected function 序号分发器(int $pid)
    {
        $lockKeyObject = (new RedisCacheConfig())->__invoke();
        //遍历现在的集合，找出空的位置 ，

        //防止主进程异常退出，先清理掉已经不在的进程
        $harrays = $lockKeyObject->hGetAll($this->getHKey() . 'list');
        foreach ($harrays as $opid => $num) {
            $index = array_search($opid, $this->childlist);
            if ($index === false) {
                $lockKeyObject->hDel($this->getHKey() . 'list', $opid);
                $this->log("进程id:[$pid]被注销序号:[$num]");
            }
        }

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
        if ($num != -1) {
            $lockKeyObject->hSetNx($this->getHKey() . 'list', $pid, $num);
            $this->log("给进程id:[$pid]分发序号:[$num]");
            return true;
        }
        throw new \Exception(date('Y-m-d H:i:s') . "序号已经分发完了，为什么还有申请的？" . json_encode($harrays, JSON_UNESCAPED_UNICODE));
    }

    protected function 确认序号()
    {
        $pid = (int)posix_getpid();
        //需求等上级把序号写进去了，再查。否则如果子进程运行比父进程快，那么就查询不到内容了
        usleep(1000 * 100);
        $lockKeyObject = (new RedisCacheConfig())->__invoke();
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
        $message = '父进程:开始运行,进程id:' . $mypid;
        $this->log($message);
        echo $message . "\n";
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。

        $Runtimes = 0;
        while (true) {
            $Runtimes++;
            if ($this->rediLock) {
                //如果存在多个实例服务,那么锁住,只能一个实例运行任务
                $lockKeyObject = (new LockKey())->setRedisConfig(new RedisCacheConfig());
                $没有获取到锁 = !$lockKeyObject
                    ->setKey($this->getHKey() . 'lock')
                    ->setValue(date('c'))
                    //提前一秒解锁。防止下一次循环刚好就差一点
                    ->setExpire(max(1, $this->getSleepSecond() - 1))
                    ->__invoke();
                if ($没有获取到锁) {
                    $this->log("父进程[$mypid]取不到锁:[" . $lockKeyObject->getClient()->get($this->getHKey() . 'lock') . " vs " . date('c') . "]");
                    sleep($this->getSleepSecond());
                }
            }

            //取消掉已经不存在的进程
            foreach ($this->childlist as $key => $pid) {
                $this->log("监听pid：{$pid}");
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    $this->log("进程：{$pid}退出");
                    unset($this->childlist[$key]);
                    $this->序号注销器($pid);
                }
            }

            $子进程数已经满了 = count($this->childlist) >= $this->getNum();
            if ($子进程数已经满了) {
                $this->log("子进程已经满【" . count($this->childlist) . " >= {$this->getNum()}】：父进程休眠：{$this->getSleepSecond()}秒");
                sleep($this->getSleepSecond());
                continue;
            }

            $this->log("当前进程数目:" . count($this->childlist) . "，要求进程数：{$this->getNum()}，还需要生成" . ($this->getNum() - count($this->childlist)) . "个");
            //生成指定数量的进程数目
            for ($i = count($this->childlist); $i < $this->getNum(); $i++) {
                $this->log("生成进程： $i");
                $pid = pcntl_fork(); //创建子进程
                if ($pid == 0) {
                    $pid = (int)posix_getpid();
                    SetExceptionHandler::instance();
                    $this->log("子进程:真实代码开始运行.id:$pid");
                    $this->whileRun();
                    $this->log("子进程:真实代码运行完毕:$pid");
                    exit;
                } else {
                    $this->childlist[] = $pid;
                    $this->序号分发器($pid);
                }
            }

            $this->log("父进程休眠：{$this->getSleepSecond()}秒");
            sleep($this->getSleepSecond());

            if ($this->getMaxRuntimes() && $Runtimes > $this->getMaxRuntimes()) {
                break;
            }
        }
        $this->log('父级进程:结束');
        exit;
    }

    /**
     * 启动代码的时候,开启互斥. 设定每运行一次代码周期检测锁文件是不是被破坏了,破坏就退出
     * CrontabLock constructor.
     */
    final public function __construct()
    {
        $mypid = (int)posix_getpid();
        //单机启动的时候，文件锁没法卡住。改用redis锁
        $lockKeyObject = (new LockKey())
            ->setRedisConfig(new RedisCacheConfig());

        $没有锁住 = !$lockKeyObject
            ->setKey($this->getHKey() . 'main')
            ->setValue($mypid)
            ->setExpire($this->getSleepSecond())
            ->__invoke();
        if ($没有锁住) {
            throw new \Exception("无法启动，还有另外一个进程没有释放锁" . $this->getHKey() . "main 锁进程id:" . $lockKeyObject->getClient()->get($this->getHKey() . 'main') . " vs 当前进程id:$mypid");
        }
        $this->lockFile = (new \ReflectionClass(static::class))->getFileName() . '.lock';
        if (!$this->fp = fopen($this->lockFile, 'a+')) {
            throw new \Exception(date('Y-m-d H:i:s') . '无法打开文件,文件加锁失败,是不是已经存在启动进程?.' . $this->lockFile);
        }
        if (!$this->lock = flock($this->fp, LOCK_EX | LOCK_NB)) {
            throw new \Exception(date('Y-m-d H:i:s') . '文件:' . $this->lockFile . '加锁失败,是不是已经存在启动进程? id:' . (int)posix_getpid());
        }
        register_tick_function([$this, 'tick']);
        sleep(1);
        $lockKeyObject->getClient()->del($this->getHKey() . 'main');
    }

    /**
     * 只有父进程才能调用析构函数.
     */
    public function __destruct()
    {
        $this->log('析构函数被调用:进程id:' . (int)posix_getpid());
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
    }

    public function tick()
    {
        if (!is_file($this->lockFile)) {
            throw new \Exception(date('Y-m-d H:i:s') . '锁文件被破坏!' . static::class);
        }
    }
}
