<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

include __DIR__ . '/vendor/autoload.php';

// 全局数组保存uid在线数据
$uidConnectionMap = [];
// 记录最后一次广播的在线用户数
$last_online_count = 0;
// 记录最后一次广播的在线页面数
$last_online_page_count = 0;


$socket_list = [];

function genSocketIO($id)
{
    global $socket_list;
// PHPSocketIO服务
    $socket_list[$id] = new SocketIO(SERVICE_PORTS[$id]['fe']);
// 客户端发起连接事件时，设置连接socket的各种事件回调
    $socket_list[$id]->on(
        'connection',
        function (\PHPSocketIO\Socket $socket) use ($id) {
            // 当客户端发来登录事件时触发
            $socket->on(
                'login',
                function ($uid) use ($socket, $id) {
                    global $uidConnectionMap;
                    login_log(SERVICE_PORTS[$id]['fe'], $uid, $socket->buildHandshake());
                    // 已经登录过了
                    if (isset($socket->uid)) {
                        return;
                    }
                    // 更新对应uid的在线数据
                    $uid = (string)$uid;
                    if (!isset($uidConnectionMap[$uid])) {
                        $uidConnectionMap[$uid] = 0;
                    }
                    // 这个uid有++$uidConnectionMap[$uid]个socket连接
                    ++$uidConnectionMap[$uid];
                    // 将这个连接加入到uid分组，方便针对uid推送数据
                    $socket->join($uid);
                    $socket->uid = $uid;
                }
            );

            // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
            $socket->on(
                'disconnect',
                function () use ($socket) {
                    if (!isset($socket->uid)) {
                        return;
                    }
                    global $uidConnectionMap;
                    // 将uid的在线socket数减一
                    if (--$uidConnectionMap[$socket->uid] <= 0) {
                        unset($uidConnectionMap[$socket->uid]);
                    }
                }
            );
        }
    );

// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
    $socket_list[$id]->on(
        'workerStart',
        function () use ($id) {
            // 监听一个http端口
            $inner_http_worker = new Worker('http://' . BE_BIND_IP . ':' . SERVICE_PORTS[$id]['be']);
            // 当http客户端发来数据时触发
            $inner_http_worker->onMessage = function ($http_connection, $data) use ($id) {
                global $uidConnectionMap;
                $_POST = $_POST ? $_POST : $_GET;
                // 推送数据的url格式 type=publish&to=uid&content=xxxx
                switch (@$_POST['type']) {
                    case 'publish':
                        global $socket_list;
                        $to      = @$_POST['to'];
                        $message = @$_POST['content'];
                        if ($to) {
                            // 有指定uid则向uid所在socket组发送数据
                            $to = explode(',', $to);
                            foreach ($to as $i) {
                                $socket_list[$id]->to($i)->emit('new_msg', $message);
                                message_log(SERVICE_PORTS[$id]['fe'], $i, $message);
                            }
                            $ids = array_intersect($to, array_keys($uidConnectionMap));

                            return $http_connection->send(json_encode($ids));
                        } else {
                            // 否则向所有uid推送数据
                            $socket_list[$id]->emit('new_msg', $message);
                            message_log(SERVICE_PORTS[$id]['fe'], '_all_', $message);

                            return $http_connection->send(json_encode(array_keys($uidConnectionMap)));
                        }
                }

                return $http_connection->send('fail');
            };
            // 执行监听
            $inner_http_worker->listen();

            // 一个定时器，定时向所有uid推送当前uid在线数及在线页面数
            Timer::add(
                1,
                function () {
                    global $uidConnectionMap, $last_online_count, $last_online_page_count;
                    $online_count_now      = count($uidConnectionMap);
                    $online_page_count_now = array_sum($uidConnectionMap);
                    // 只有在客户端在线数变化了才广播，减少不必要的客户端通讯
                    if ($last_online_count != $online_count_now || $last_online_page_count != $online_page_count_now) {
                        $last_online_count      = $online_count_now;
                        $last_online_page_count = $online_page_count_now;
                    }
                }
            );
        }
    );
}

foreach (SERVICE_PORTS as $id => $port) {
    genSocketIO($id);
}

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}


