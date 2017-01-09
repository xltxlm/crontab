#!/usr/bin/env bash
cd `dirname $0`
while test "1" = "1"
do


#========1:定时任务类===========

flock -xn ./CrontabLockDemo.php.flcok -c " php ./CrontabLockDemo.php 2>&1 >>entrypoint.log" & 


#========2:项目文件变化监控===========

flock -xn ../src/Inotifywait.sh.flcok -c "../src/Inotifywait.sh 2>&1 >>entrypoint.log" & 


#========3:日志文件变化跟踪===========

tail -f  /opt/log/php_error.log -n0  | php ../src/LogFile/ErrorLog.php mailConfig=xltxlm\crontab\Config\Mail &


#========4:资源链接测试===========

php -r 'include "/var/www/html/vendor/autoload.php";include "./Config/ConfigTest.php";(new xltxlm\crontab\tests\Config\ConfigTest)->test(); ' & 


#========[END]===========

sleep 1
echo -n .
done
