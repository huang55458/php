<?php


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
    $param['url'] = 'http://yundan.sanzhi56.cn/api/Table/Search/settleList?logid=12465101688544282065&gid=1000&btnLoadingTag=off';
    $param['cookie'] = 'PHPSESSID=21195192657f45c30b1bb8d7d90c34b0; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1687678318; user_id=124651; group_id=1000; 124651%7C53031%7C1000%7ClastHandleTime=1687745412604; 124651%7C53031%7C1000%7CisTimeoutLock=1; 124651%7C62044%7C1000%7ClastHandleTime=1688457753950; 124651%7C62044%7C1000%7CisTimeoutLock=1; company_id=59997; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1688543985';
    $err_data = [];
    for ($i=1;$i<=77;$i++){
        $param['data']=[
            'req' =>'{"category":"Settle","tab":"detail","sort":{"create_time":"desc","serial_no":"desc","id":"desc"},"page_num":'.$i.',"page_size":1000,"cid":"","query":{"settle_category":[80]},"filter":{"settle_time":[[">=","2023-06-01 00:00:00"],["<=","2023-06-30 23:59:59"]]},"fetch_mode":"body"}'
        ];
        $data = lmh_curl('post',$param);
        $data = $data['res']['data'];
        echo $i."\n";
        foreach ($data as $v){
            if(strtotime($v['Accounts|doc_date']) > strtotime('2023-07-01 00:00:00') || strtotime($v['Accounts|doc_date']) < strtotime('2023-06-01 00:00:00')){
                $err_data[]=$v;
            }
        }
        echo json_encode($err_data,true)."\n";
    }
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