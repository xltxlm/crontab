#安装

#用法

+ use  \xltxlm\crontab\tests\CrontabLockDemo
+ 在上级文件第一行php代码写上 declare(ticks = 1);

+ 需要安装 pcntl 扩展 ,所以只能在linux下运行
+ 实现安全监控,如果目录下有文件的变动,立即邮件报警