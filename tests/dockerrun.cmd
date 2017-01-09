cd %~pd0
docker run  -v %cd%/../:/var/www/html/  --name p1 -d php bash -c "tail -f /etc/issue"
docker exec -it p1 bash -c "apt-get -y update && apt-get install inotify-tools"
docker exec -it p1 cp -f /var/www/html/tests/php.ini /usr/local/etc/php/conf.d/php.ini
docker exec -it p1 bash  docker-php-ext-install pcntl
docker ps -a
rem docker logs  p1
rem docker exec -it p1 bash
rem docker exec -it p1 bash -c "ps aux | grep php | grep -v grep"
rem docker exec -it p1 bash -c "ls -al /var/www/html/ "
rem docker exec -d p1 bash -c "/var/www/html/tests/entrypoint.sh >/dev/null &"
rem docker rm -f p1


rem docker exec -it p1 bash -c "ps aux | grep php | grep -v grep"
rem docker exec -it p1 bash -c "ps aux | grep php | grep -v grep | awk '{print $2}' | xargs kill -9 "
