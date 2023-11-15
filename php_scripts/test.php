<?php
//$command = 'sh C:\Users\Administrator\Documents\test.sh';
//$descriptorspec = array(
//    0 => array('pipe', 'r'),  // 标准输入
//    1 => array('pipe', 'w'),  // 标准输出
//    2 => array('pipe', 'w')   // 标准错误输出
//);
//$process = proc_open($command, $descriptorspec, $pipes);
//
//if (is_resource($process)) {
//    fwrite($pipes[0], "input1\n");
//    fwrite($pipes[0], "input2\n");
//    fclose($pipes[0]);
//
//    $output = stream_get_contents($pipes[1]);
//    echo $output;
//    fclose($pipes[1]);
//
//    $error = stream_get_contents($pipes[2]);
//    fclose($pipes[2]);
//
//    $return = proc_close($process);
//}
file_put_contents(__DIR__.'/tmp.json',json_encode(json_decode(file_get_contents(__DIR__.'/tmp.txt'),256),true));