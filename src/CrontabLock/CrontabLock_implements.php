<?php
namespace xltxlm\crontab\CrontabLock;

/**
 * :Trait;
 * 让一个类，具备开子进程的能力，用于定时任务;
*/
Trait CrontabLock_implements
{


/* @var array  子进程的记录数组 */
    protected $childlist = [];





    /**
    * 子进程的记录数组;
    * @return array;
    */
            public function getchildlist():array        {
                return $this->childlist;
        }

    
    




/**
* @param array $childlist;
* @return $this
*/
    protected function setchildlist(array $childlist  = [])
    {
    $this->childlist = $childlist;
    return $this;
    }


    /**
    * @param  $childlist;
    * @return $this
    */
    public function setchildlist_Row( $childlist){
    $this->childlist[] = $childlist;
    return $this;
    }

/* @var int  允许开启的子进程个数 */
    protected $num = 1;
    




    /**
    * 允许开启的子进程个数;
    * @return int;
    */
            abstract public function getnum():int;
    
    




/**
* @param int $num;
* @return $this
*/
    public function setnum(int $num  = 1)
    {
    $this->num = $num;
    return $this;
    }



/* @var int  起下一个进程，需要间隔的时间（秒） */
    protected $sleepSecond = 0;
    




    /**
    * 起下一个进程，需要间隔的时间（秒）;
    * @return int;
    */
            abstract public function getsleepSecond():int;
    
    




/**
* @param int $sleepSecond;
* @return $this
*/
    public function setsleepSecond(int $sleepSecond  = 0)
    {
    $this->sleepSecond = $sleepSecond;
    return $this;
    }



/* @var int  有的时候，进程吃太多内存，又泄露了，这个时候需要释放主进程，这个最大限制循环启动次数排上用场。比如在pack项目中 */
    protected $MaxRuntimes = 0;
    




    /**
    * 有的时候，进程吃太多内存，又泄露了，这个时候需要释放主进程，这个最大限制循环启动次数排上用场。比如在pack项目中;
    * @return int;
    */
            public function getMaxRuntimes():int        {
                return $this->MaxRuntimes;
        }

    
    




/**
* @param int $MaxRuntimes;
* @return $this
*/
    public function setMaxRuntimes(int $MaxRuntimes  = 0)
    {
    $this->MaxRuntimes = $MaxRuntimes;
    return $this;
    }



/**
* @var int $mod  标识是第几个子进程，0开始数起
*  每个子进程执行部分的代码;
*  @return ;
*/
abstract protected function whileRun(int $mod = 0);
}