<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/25
 * Time: 12:01.
 */
namespace xltxlm\crontab;

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
    /** @var int 每运行一次暂停的秒数 */
    protected $sleepSecond = 1;
    /** @var int 子进程的id */
    private $childPid = 0;
    /** @var int 运行开启的子进程的个数 */
    private $childNum = 0;
    private $childMaxNum = 1;

    /**
     * 设置每个循环暂停的秒数
     * @return int
     */
    abstract protected function getSleepSecond(): int;

    /**
     * 运行的代码
     * @return mixed
     */
    abstract protected function whileRun();

    protected function log($str)
    {
        fwrite($this->fp, $str."\n");
    }

    /**
     * 运行任务
     */
    public function __invoke()
    {
        pcntl_signal(SIGTERM, function () {
        });
        pcntl_signal(SIGHUP, function () {
        });

        while (true) {
            $pid = pcntl_fork(); //创建子进程
            $this->log("创建了进程id $pid");
            //父进程和子进程都会执行下面代码
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } else {
                if ($pid) {
                    //父进程会得到子进程号，所以这里是父进程执行的逻辑
                    $this->log("父进程:进入父进程id:".(int)posix_getpid());
                    $this->log("父进程:进入父进程,监听进程id:$pid");
                    $res = pcntl_wait($status);
                    if ($res == -1 || $res > 0) {
                        //回调重新生成子进程
                        $this->log("父进程:等待到子信号:$res, status:$status");
                        sleep(1);
                    }
                } else {
                    $this->childNum++;
                    if ($this->childNum > $this->childMaxNum) {
                        exit;
                    }
                    $this->childPid = (int)posix_getpid();
                    $this->log("子进程:开始运行,进程id:".(int)posix_getpid());
                    //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                    while (true) {
                        $this->log("子进程:真实代码开始运行");
                        $this->whileRun();
                        sleep($this->getSleepSecond());
                        $this->log("子进程:真实代码运行完毕");
                    }
                    $this->log("子进程:结束");
                    exit;
                }
            }
        }
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
     * 只有父进程才能调用析构函数
     */
    public function __destruct()
    {
        if ((int)posix_getpid() == $this->childPid) {
            return;
        }
        $this->log("析构函数被调用:进程id:".(int)posix_getpid());
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
    }

    public function tick()
    {
        if (!is_file($this->lockFile)) {
            throw new \Exception('锁文件被破坏!'.static::class);
        }
        $this->log(date("Y-m-d H:i:s"));
    }
}
