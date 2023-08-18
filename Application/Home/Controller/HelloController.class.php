<?php
namespace Home\Controller;

use Think\Controller;

class HelloController extends Controller
{
    public function hello($name='hello',$pass='hello')
    {
        ini_set("max_execution_time", 60*60*1.5);
        ini_set("memory_limit", "1024M");
//        var_dump(ini_get('memory_limit'));
//        exit;
//        $User = M('User');
//        var_dump( $User->getDbFields());
//        var_dump($name.' '.$pass);
//        $arr = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '_', '~', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '+', ',', '.', '/', '?', '>', '<', '[', ']', '}', '{', '|', '\\', ':', ';', '"', '\'', '=', '`'];
//        $arr = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9' , 'a', 'b', 'c', 'd', 'e'];
        $arr = ['', '', '', '', '', '', '', '', '', '' , '', '', '', '', ''];
//        $arr = [ '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
//        $arr = [ 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
//        $arr = [ 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        $start = time();
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
        foreach ($arr as $g) {
            foreach ($arr as $f) {
                foreach ($arr as $e) {
                    foreach ($arr as $d) {
                        foreach ($arr as $c) {
                            foreach ($arr as $b) {
                                foreach ($arr as $a) {
                                    $t[] = ['md5' => md5($g . $f . $e . $d . $c . $b . $a), 'value' => $g . $f . $e . $d . $c . $b . $a];
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
        }


        // id  78346447 - 78397071
//        foreach ($arr as $d) {
//            foreach ($arr as $c) {
//                foreach ($arr as $b) {
//                    foreach ($arr as $a) {
//                        $value = json_decode(sprintf('"%s"', '\u' . $d.$c.$b.$a));
//                        $t[] = ['md5' => md5($value), 'value' => $value. ' | '.$d.$c.$b.$a];
//                        if (count($t) > 500) {
//                            $User->addAll($t);
//                            unset($t);
//                            $t = [];
//                        }
//                    }
//                }
//            }
//        }

//        while (true) {
//            $value = json_decode(sprintf('"%s"', '\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)] . '\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)]. '\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)].'\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)].'\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)].'\u'. $arr[random_int(0,14)] . $arr[random_int(0,14)]. $arr[random_int(0,14)]. $arr[random_int(0,14)]));
////            $value = json_decode(sprintf('"%s"', '\u'. $arr[random_int(0,14)]));
//            $t[] = ['md5' => md5($value), 'value' => $value];
//            if (count($t) > 500) {
//                $User->addAll($t);
//                unset($t);
//                $t = [];
//            }
//        }
        if (!empty($t)) {
            $User->addAll($t);
        }
        var_dump(['耗时：', time() - $start]);
    }

    public function hello1($name='hello',$pass='hello'){
//        jdd(json_decode(sprintf('"%s"', '\uffff\u597d\u5b66\u4e60\u5929\u5929\u5411\u4e0a')));
//        jdd(smarty_mb_from_unicode('huang','utf8'));
//        jdd(random_int(0,15));

    }

    public function test() {
        echo "this is a test";
    }
}