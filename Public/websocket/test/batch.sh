#!/bin/bash

ps axu|grep python|awk '{print $2}'|xargs -i kill {}

for i in 4 8; do
    echo -n $i
    python client.py ws.chemanman.com 5000 $i &
    sleep 0.01
done

for i in 1 5 9; do
    echo -n $i
    python client.py ws.chemanman.com 5001 $i &
    sleep 0.01
done

for i in 2 6 10; do
    echo -n $i
    python client.py ws.chemanman.com 5002 $i &
    sleep 0.01
done

for i in 3 7; do
    echo -n $i
    python client.py ws.chemanman.com 5003 $i &
    sleep 0.01
done

