<?php
/**
 * Created by PhpStorm.
 * User: xialintai
 * Date: 2016/12/25
 * Time: 12:01.
 */

namespace xltxlm\crontab;

use xltxlm\crontab\CrontabLock\CrontabLock_implements;
use xltxlm\helper\Ctroller\SetExceptionHandler;
use xltxlm\logger\Log\DefineLog;

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
    use CrontabLock_implements;


    /** @var bool */
    protected $记录执行日志 = true;

    /** @var string 业务锁的前缀 */
    protected $hKey = "";


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
     * @return string
     */
    public function getHKey(): string
    {
        return "{$_SERVER['dockername']}Crontab_" . strtr(static::class, ['\\' => '']);
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


    public function log($str)
    {
        if ($this->is记录执行日志()) {
            $this->logOrigin($str, $this->getCurrentMod());
        }
    }

    /**
     * 运行任务
     */
    public function __invoke()
    {
        $mypid = (int)posix_getpid();
        $_SERVER['REQUEST_URI'] = static::class;
        $this->log('父进程:开始运行,进程id:' . $mypid . ",占用内存：" . round(memory_get_usage() / 1024 / 1024, 2) . 'MB');
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。

        //👇👇----修改进程的标题，这个的ps、top命令下能看清楚点
        $crontabclassname = (new \ReflectionClass(static::class))->getFileName();
        cli_set_process_title(basename($crontabclassname) . ".php@Totalx{$this->getNum()}");
        $filemd5 = md5_file($crontabclassname);

        $Runtimes = 0;
        while (true) {
            //每次进来之前先检测下文件的md5值在决定重启
            $filemd5_new = md5_file($crontabclassname);
            if ($filemd5_new != $filemd5) {
                foreach ($this->childlist as $key => $pid) {
                    posix_kill($pid, SIGKILL);
                }
                $this->log("文件的内容发生改变了,重启进程");
                exit;
            }
            $Runtimes++;
            //取消掉已经不存在的进程
            foreach ($this->childlist as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    unset($this->childlist[$key]);
                }
            }

            $子进程数已经满了 = count($this->childlist) >= $this->getNum();
            if ($子进程数已经满了) {
                $this->log(['子进程数已经满了', $this->childlist]);
                sleep($this->getSleepSecond());
                continue;
            }

            //生成指定数量的进程数目
            for ($i = 0; $i < $this->getNum(); $i++) {
                if ($this->childlist[$i]) {
                    continue;
                }

                //👇👇----创建子进程
                $pid = pcntl_fork();
                if ($pid == 0) {
                    //切割php调试日志,按照天进行分组
                    ini_set('error_log', "/opt/logs/php_errors_" . date('Ymd') . ".log");
                    $basename = basename($crontabclassname, '.php');
                    $_SERVER['logid'] = $basename . '_' . DefineLog::getUniqid_static();
                    SetExceptionHandler::instance();
                    //👇👇----修改进程的标题，这个的ps、top命令下能看清楚点
                    cli_set_process_title($basename . ".php@sub-{$i}x{$this->getNum()}");
                    $this->setCurrentMod($i);
                    //运行真实的代码
                    $this->whileRun($i);
                    exit;
                } else {
                    $this->childlist[$i] = $pid;
                }
            }
            //👇👇---- 暂停，等下一个进程启动
            sleep($this->getSleepSecond());
            if ($this->getMaxRuntimes() && $Runtimes > $this->getMaxRuntimes()) {
                break;
            }
        }
        exit;
    }

}
