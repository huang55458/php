<?php
/**
 * Created by PhpStorm.
 * User: donvan
 * Date: 9/21/16
 * Time: 14:06
 */

function common_log($content, $level = 'info', $file = null)
{
    if (!is_string($content)) {
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);
    }
    if (is_null($file)) {
        $file = date('Y-m-d') . '.log';
    }
    $content = date('Y-m-d H:i:s') . ' ' . $level . ' ' . $content . PHP_EOL;
    if (\Workerman\Worker::$daemonize) {
        $log_file = LOG_PATH . '/' . $file;
        if (file_exists($log_file) === false) {
            touch($log_file);
            chmod($log_file, 0622);
        }
        file_put_contents($log_file, $content, FILE_APPEND | LOCK_EX);
    } else {
        echo $content;
    }
}

function login_log($worker, $uid, $request)
{
    $content = 'worker@' . $worker . ' uid@' . $uid . ' request:' . json_encode($request, JSON_UNESCAPED_UNICODE);
    common_log($content);
}

function message_log($worker, $uid, $message)
{
    $content = 'worker@' . $worker . ' uid@' . $uid . ' message:' . $message;
    common_log($content);
}
