<?php
namespace Home\Behavior;


use Think\Behavior;

class TestBehavior extends Behavior
{
    // 一秒请求一次
    public function run(&$params)
    {
        $ip = get_ip(0,true);
        if (S($ip) === false) {
            S($ip, 'true', 2);
        } else {
            header('Content-type: application/json');
            exit(json_encode(['errno'=>0, 'errmsg'=>'过于频繁'], 256));
        }
    }
}


