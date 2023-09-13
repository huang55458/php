<?php
return array(
    'db_type' => 'mysql',
    'db_user' => 'root',
    'db_pwd' => '123456',
    'db_host' => '127.0.0.1', // localhost 出错
    'db_port' => '3306',
    'db_name' => 'thinkphp3',
    'db_charset' => 'utf8',

    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => 6379,
    'REDIS_PASSWORD' => '',

    'LOAD_EXT_CONFIG' => [
        'mq',
    ],

    // cache存储方式
    'DATA_CACHE_TYPE' => 'Redis',
    // 防刷cache存储方式
    'PR_CACHE_TYPE' => 'Redis',
//    'DEFAULT_ACTION'         => 'login',

//    'LOG_RECORD' => true, // 开启日志记录
//    'LOG_FORMAT' => '[%s][%s] %s', // 设置日志格式
    'LOG_FILE_SIZE' => 209715200,
);