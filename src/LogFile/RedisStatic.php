<?php
/**
 * 统计应用的各项日志
 * 结果实时写入到redis里面，远程从redis取数据
 */

use xltxlm\crontab\Config\RedisCacheConfig;
use xltxlm\logger\Operation\Action\LoadClassLog;
use xltxlm\redis\RedisClient;

eval("include_once '/var/www/html/vendor/autoload.php';");

//$today 代表运行时候的日期，如果日期改变了，需要退出程序
static $runrimes = 0, $i = 0, $infos = [], $RedisClientObject, $lastLogTime, $today;
$runrimes++;
if ($runrimes == 1) {
    $RedisClientObject = (new RedisClient())
        ->setRedisConfig(new RedisCacheConfig());
}
$today = date('Y-m-d');
while ($line = fgets(STDIN)) {
    //换日期了程序退出去之后，会丢失一部分的数据统计.
    if ($today != date('Y-m-d')) {
        exit;
    }
    if ($line[0] == '{') {
        $line = json_decode($line, true);
        $datetime0 = date('Y-m-d');
        if ($today != $datetime0) {
            exit;
        }
        $datetime = $datetime0.date(' H:', strtotime($line['timestamp'])) . (date('i', strtotime($line['timestamp'])) / 10 % 10) . '0:00';
        //记录应用的调用次数
        if (strpos($line['logClassName'], 'xltxlm\\logger') !== false) {
            $i++;
            $project_name = explode('_', $_SERVER['HOSTNAME'])[0];
            $key = [
                'project_name' => $project_name,
                'datetime' => $datetime,
                'callClass' => $line['callClass'],
                'reource' => $line['reource'],
                'action' => $line['action'],
            ];
            $keyjson = json_encode($key, JSON_UNESCAPED_UNICODE);
            $infos[$keyjson]['runTime'] += $line['runTime'];
            $infos[$keyjson]['call_times']++;
            $lastLogTime = time();
        }

        if ($infos && ($i && $i % 100 == 0 || time() - $lastLogTime > 60)) {
            foreach ($infos as $keyjson => $info) {
                $key = json_decode($keyjson, true);
                $RedisClientObject->hincrby($key['project_name'] . "@call_times@{$key['datetime']}", $keyjson, $info['call_times']);
                //记录应用的耗时总时间
                $RedisClientObject->hincrbyfloat($key['project_name'] . "@take_times@{$key['datetime']}", $keyjson, $info['runTime']);
            }
            $infos = [];
        }
    }
}