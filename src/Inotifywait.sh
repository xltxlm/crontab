#!/usr/bin/env bash
cd `dirname $0`

inotifywait -mr  --excludei '(.log|.lock|.flock)' --timefmt '%Y-%m-%d %H:%M:%S' --format '%T %w%f %e' -e close_write,delete ../ | while read date time dir event; do
       php  Inotifywait.php ps $date $time $dir $event
done