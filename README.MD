#安装

#用法

+ use  \xltxlm\crontab\tests\CrontabLockDemo
+ 在上级文件第一行php代码写上 declare(ticks = 1);
+ 依赖linux工具 apt-get install inotify-tools (监听文件改变,防止入侵)


+ 需要安装 pcntl 扩展 ,所以只能在linux下运行
+ 实现安全监控,如果目录下有文件的变动,立即邮件报警