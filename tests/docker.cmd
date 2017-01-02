cd %~pd0
docker run  -v %cd%/../:/var/www/html/  --name p1 -d php bash -c "tail -f /etc/issue"
docker exec -it p1 bash  docker-php-ext-install pcntl
docker ps -a
rem docker logs  p1
rem docker exec -it p1 bash
rem docker exec -it p1 bash -c "ls -al /var/www/html/ "
rem docker exec -it p1 bash -c "php /var/www/html/tests/CrontabLockDemo.php"
rem docker rm -f p1