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
    /** @var resource 锁文件的句柄 */
    private $fp;
    /** @var string 锁文件的路径 */
    private $lockFile = '';
    /** @var bool 是否开启redis并发锁 */
    protected $rediLock = true;

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

    protected function log($str)
    {
        fwrite($this->fp, '['.date('Y-m-d H:i:s').']'.$str."\n");
    }

    /**
     * 运行任务
     */
    public function __invoke()
    {
        $_SERVER['REQUEST_URI'] = static::class;
        $this->log('父级进程:开始运行,进程id:'.(int)posix_getpid());
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
        while (true) {
            $pid = pcntl_fork(); //创建子进程
            if ($pid == 0) {
                if ($this->rediLock) {
                    //如果存在多个实例服务,那么锁住,只能一个实例运行任务
                    $locked = (new LockKey())
                        ->setKey('CrontabLock'.static::class)
                        ->setValue(date('c'))
                        ->setExpire($this->getSleepSecond())
                        ->setRedisConfig(new RedisCacheConfig())
                        ->__invoke();
                    if (!$locked) {
                        $this->log('取不到锁,退出运行');
                        exit;
                    }
                } else {
                }

                SetExceptionHandler::instance();
                $this->log('子进程:真实代码开始运行.id:'.(int)posix_getpid());
                $this->whileRun();
                $this->log('子进程:真实代码运行完毕');
                exit;
            }
            pcntl_wait($status);
            if (pcntl_wexitstatus($status)) {
                $this->log('子进程不正常退出,父进程也跟随退出');
                exit;
            }

            sleep($this->getSleepSecond());
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
        $this->lockFile = (new \ReflectionClass(static::class))->getFileName().'.lock';
        if (!$this->fp = fopen($this->lockFile, 'a+')) {
            throw new \Exception('无法打开文件,文件加锁失败,是不是已经存在启动进程?.'.$this->lockFile);
        }
        if (!$this->lock = flock($this->fp, LOCK_EX | LOCK_NB)) {
            throw new \Exception('文件:'.$this->lockFile.'加锁失败,是不是已经存在启动进程? id:'.(int)posix_getpid());
        }
        register_tick_function([$this, 'tick']);
    }

    /**
     * 只有父进程才能调用析构函数.
     */
    public function __destruct()
    {
        $this->log('析构函数被调用:进程id:'.(int)posix_getpid());
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
    }

    public function tick()
    {
        if (!is_file($this->lockFile)) {
            throw new \Exception('锁文件被破坏!'.static::class);
        }
    }
}
