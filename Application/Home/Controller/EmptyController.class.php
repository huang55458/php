<?php
namespace Home\Controller;

use Think\Controller;

class EmptyController extends Controller
{
    public function index()
    {
        $str = CONTROLLER_NAME;
        $this -> f($str);
    }

    private function f($str) {
        var_dump($str);
    }
}