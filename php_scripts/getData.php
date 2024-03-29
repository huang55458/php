<?php
require_once './../Application/Home/Common/function.php';

function lmh_curl($type='get',$param=[],$return='php'){
    $ch = curl_init();
    $url = $param['url']??'';
    $headers = $param['headers']??[];
    $cookie = $param['cookie']??'';
    $data = $param['data']??[];
    if (empty($url)){
        return [];
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($type=='post'){//echo json_encode($data,true);die();
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,true));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 部分接口超过4s,暂未确定问题,临时改为10s
    $res = curl_exec($ch);
    curl_close($ch);
    //echo $res."\n";
    if ($return=='php'){
        return json_decode($res,true);
    }elseif($return='str'){
        return $res;
    }
}
//由于 明细账的本期余额和资金流水的总额对不上 可能是凭证日期的问题
// 该方法去对比资金流水的凭证日期的是否正确是否跟结算日期的区间是否一致 不一致的输出来
diffDate();
function diffDate(){
    ini_set('memory_limit','4000M');
    $param['url'] = 'http://yundan.vkj56.cn/api/Table/Search/settleList?logid=12456601699082600687&gid=1000&btnLoadingTag=off';
    $param['cookie'] = 'Order_tr_down_loading_list_124566=false; Order_tr_up_loading_list_124566=false; PHPSESSID=a8ea0d67f33dc9c11c7462187bcf0901; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1698798757,1698885197,1698973854,1699058019; user_id=124566; group_id=1000; company_id=26751; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1699079258; 124566%7C62044%7C1000%7ClastHandleTime=1699080053784';
    $err_data = [];
    for ($i=1;$i<=31;$i++){

        $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 10, $i, 2023));
        $end_time = date('Y-m-d H:i:s', mktime(23, 59, 59, 10, $i, 2023));
        $param['data']=[
//            'req' =>'{"category":"Settle","tab":"list","sort":{"create_time":"desc","id":"desc"},"page_num":1,"page_size":2000,"cid":"73067e693dac1586773c349fa93a5d65","query":{"pay_mode":"平台账户"},"filter":{"settle_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}'
            'req' =>'{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":1,"page_size":2000,"cid":"73067e693dac1586773c349fa93a5d65","query":{"settle_category":[80]},"filter":{"settle_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}'
//            'req' =>'{"category":"Accounts","tab":"doc","sort":{"doc_date":"desc","create_time":"desc"},"page_num":1,"page_size":10000,"cid":"73067e693dac1586773c349fa93a5d65","query":{},"filter":{"doc_date":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}'
//            'req' =>''
        ];
        $data = lmh_curl('post',$param);
        $count = $data['res']['total']['count'];
        echo $count."\n";
        $data = $data['res']['data'];
        if ($count > 2000) {
            $ff = getData($count,$param,$start_time,$end_time);
            $data = array_merge($data,$ff);
        }
        echo count($data)."ffff\n";
        foreach ($data as $v){
//                jdd($v);
            if (!empty($v['Accounts|doc_date'])) {
                if (strtotime($v['Accounts|doc_date']) > strtotime('2023-11-01 00:00:00') || strtotime($v['Accounts|doc_date']) < strtotime('2023-10-01 00:00:00')) {
                    echo $v['cert_no'] . "\n";
                    $err_data[] = $v['cert_no'];
                }
            }
        }
        echo $i."\n";
//        foreach ($data as $v){
//            if(strtotime($v['Accounts|doc_date']) > strtotime('2023-07-01 00:00:00') || strtotime($v['Accounts|doc_date']) < strtotime('2023-06-01 00:00:00')){
//                $err_data[]=$v;
//            }
//        }
//        jdd($data);
//        $err_data[] = array_column($data, 'cert_no');
//        $err_data = array_map('intval', $err_data);
//        jdd($err_data);
    }
//    $arr = array_merge(...$err_data);
    file_put_contents(__DIR__.'\tmp.txt',implode(',',$err_data));
//    $ids = file_get_contents(__DIR__.'\tmp.txt');
//    $ids = explode(',',$ids);
//    jdd(array_diff($ids,$arr));
}

//如何上方法凭证日期没有错误 则通过自己没有去资金流水明细和 分录列表
function sum_settle_amount(){
    $param['url'] = 'http://yundan.sanzhi56.cn/api/Table/Search/settleList?logid=12465101688544282065&gid=1000&btnLoadingTag=off';
    $param['cookie'] = 'PHPSESSID=21195192657f45c30b1bb8d7d90c34b0; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1687678318; user_id=124651; group_id=1000; 124651%7C53031%7C1000%7ClastHandleTime=1687745412604; 124651%7C53031%7C1000%7CisTimeoutLock=1; 124651%7C62044%7C1000%7ClastHandleTime=1688457753950; 124651%7C62044%7C1000%7CisTimeoutLock=1; company_id=59997; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1688543985';
    $err_data = [];
    $re_data = [];
    for ($i=1;$i<=77;$i++) {
        $param['data'] = [
            'req' => '{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":1,"page_size":1000,"cid":"","query":{"settle_category":[80]},"filter":{"settle_time":[[">=","2023-06-01 00:00:00"],["<=","2023-06-30 23:59:59"]]},"fetch_mode":"body"}'
        ];
        $data = lmh_curl('post', $param);
        $data = $data['res']['data'];
        echo $i . "\n";
        foreach ($data as $v) {
            if (array_key_exists($v['cert_no_id'], $err_data)) {
                $re_data[] = $v['cert_no'];
                $err_data[$v['cert_no_id']]['settle_amount'] = $err_data[$v['cert_no_id']]['settle_amount'] + $v['settle_amount'];
            } else {
                $e = [
                    'doc_id' => $v['cert_no_id'],
                    'cert_no' => $v['cert_no'],
                    'bill_no' => $v['bill_no'],
                    'settle_amount' => $v['settle_amount'],
                ];
                $err_data[$v['cert_no_id']] = $e;
            }
        }
        //echo json_encode($re_data,true)."\n";
    }
    return $re_data;
}
//$data = sum_settle_amount();
//echo json_encode($data,true)."\n";
//$id1 = explode(',',file_get_contents(__DIR__.'\tmp.txt'));
//$id2 = explode(',',file_get_contents(__DIR__.'\tmp2.txt'));
//jdd(array_filter(array_values(array_diff($id2,$id1))));

function getData($count,$param,$start_time,$end_time) {
    $d = array();
    $i = ceil($count / 2000);
    for ($j = 2; $j <= $i; $j++){
        $param['data']['req'] = '{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":'.$j.',"page_size":2000,"cid":"73067e693dac1586773c349fa93a5d65","query":{"settle_category":[80]},"filter":{"settle_time":[[">=","'.$start_time.'"],["<=","'.$end_time.'"]]},"fetch_mode":"body"}';

        $data = lmh_curl('post',$param);
        $data = $data['res']['data'];
        $d[] = $data;
    }
    return array_merge(...$d);
}

