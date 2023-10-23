<?php

$arr = [];
function get_current_context($arr,$path) {
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {
                $file = $path.DIRECTORY_SEPARATOR.$file;
                if (!is_dir($file) && str_ends_with($file, '.txt')) {
                    echo "正在操作文件名：".$file."  文件大小：".round(filesize($file)/(1024*1024),2) ."mb";
                    $arr = array_merge(explode("\n", file_get_contents($file)), $arr);
                    echo "       加载后密码总数为：".count($arr).PHP_EOL;
                } elseif (is_dir($file)) {
                    $arr = get_current_context($arr,$file);
                }
            }
        }
        closedir($handle);
    }
    return $arr;
}
$arr = get_current_context($arr,'.');
file_put_contents('total.txt',implode("\n",$arr));
//fwrite(STDOUT, '请输入用户名：');
//$argv
//getopt()