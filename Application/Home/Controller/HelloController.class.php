<?php
namespace Home\Controller;

use Think\Controller;

class HelloController extends Controller
{
    public function hello($name='hello',$pass='hello')
    {
        ini_set("max_execution_time", "1800");
        ini_set("memory_limit", "1024M");
//        var_dump(ini_get('memory_limit'));
//        exit;
//        $User = M('User');
//        var_dump( $User->getDbFields());
//        var_dump($name.' '.$pass);
//        $arr = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '_', '~', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '+', ',', '.', '/', '?', '>', '<', '[', ']', '}', '{', '|', '\\', ':', ';', '"', '\'', '=', '`'];
//        $arr = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arr = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
//        $count = 1;
//        $t = [];
//        for ($i = 0; $i < $count; $i++) {
//            $t[] = $arr;
//        }
//        $tttt = [];
//        foreach ($t as $f) {
//            foreach ($f as $n) {
//
//
//            }
//        }
        $User = M('md5');
        $t = [];
        foreach ($arr as $f) {
            foreach ($arr as $e) {
                foreach ($arr as $d) {
                    foreach ($arr as $c) {
                        foreach ($arr as $b) {
                            foreach ($arr as $a) {
                                $t[] = ['md5' => md5($a . $b . $c . $d . $e.$f), 'value' => $a . $b . $c . $d . $e.$f];
                                if (count($t) > 500) {
                                    $User->addAll($t);
                                    unset($t);
                                    $t = [];
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($t)) {
            $User->addAll($t);
        }
    }

    public function hello1($name='hello',$pass='hello'){
        var_dump(ini_get_all());
    }
}