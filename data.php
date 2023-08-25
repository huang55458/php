<?php

require_once './Application/Home/Common/function.php';

$txt = file_get_contents('C:\Users\Administrator\Documents\1.json');
$json = json_decode($txt, true);
//echo count($json['RECORDS'][0]);
$arr = [];
foreach ($json['RECORDS'] as $rec) {
    $error = json_decode($rec['error'], true);
    if (!empty($error['error']['pk_groups']['od_link'])) {
        $arr = array_merge($error['error']['pk_groups']['od_link'], $arr);
//        $ids = implode(',', $error['error']['pk_groups']['customer']) . "\n";
//        file_put_contents('C:\Users\Administrator\Documents\customer.txt', $ids, FILE_APPEND);
    }
//    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error['error']['pk_groups']['customer'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
//    exit();
    $ids = implode(',', array_unique($arr));
    file_put_contents('C:\Users\Administrator\Documents\od_link.txt', $ids);
}

