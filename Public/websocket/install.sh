#!/bin/bash

base_path=$(cd $(dirname $0); pwd)

ct_name='web-socket-server'

timestamp=$(date +%s)

php=/apollo/env/php/thirdparty.php-5.6/bin/php

if crontab -l|grep -q "$ct_name"; then
    echo "crontab for $ct_name exists."
else
    tmp_file=/tmp/crontab.tmp.$timestamp
    crontab -l > $tmp_file
    cat <<< "
# $ct_name
* * * * * ($php $base_path/start.php status || $php $base_path/start.php start -d) &>> $base_path/logs/ct.log
" >> $tmp_file
    crontab $tmp_file
    rm -f $tmp_file
    if crontab -l|grep -q $ct_name; then
        echo "crontab for $ct_name installed."
    else
        echo "crontab for $ct_name install failed."
    fi
fi
