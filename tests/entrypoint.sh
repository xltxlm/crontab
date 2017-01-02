#!/usr/bin/env bash
cd `dirname $0`
while test "1" = "1"
do
flock -xn ./CrontabLockDemo.php.flcok -c " php ./CrontabLockDemo.php 2>&1 >>entrypoint.log &" 
flock -xn ../src/Inotifywait.sh.flcok -c "../src/Inotifywait.sh 2>&1 >>entrypoint.log &" 
sleep 1
echo -n .
done
