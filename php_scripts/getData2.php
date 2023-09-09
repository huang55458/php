<?php
require_once './Application/Home/Common/function.php';

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
    if ($type === 'post'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 部分接口超过4s,暂未确定问题,临时改为10s
    $res = curl_exec($ch);
    curl_close($ch);
    if ($return === 'php') {
        return json_decode($res,true);
    }
    if ($return ==='str') {
        return $res;
    }
}
// 可能需要修改的是这三个和req
$cookie = 'Order_tr_down_loading_list_124566=false; Order_tr_up_loading_list_124566=false; PHPSESSID=8c217da199c197cbf3e31926a40d77e7; Hm_lvt_f59ed1ad07a4a48a248b87fac4f62903=1692664200,1692750598,1692837158,1692923125; user_id=124566; group_id=1000; company_id=62044; Hm_lpvt_f59ed1ad07a4a48a248b87fac4f62903=1692923804; 124566%7C62044%7C1000%7ClastHandleTime=1692923808785';
$expense_type = 'point_trans_f';
$co_type = 'co_trans_f';

function genAmount($od_link_id,$od_basic_id){
    global $co_type;
    global $cookie;
    $param['url'] = 'http://yundan.vkj56.cn/api/Order/Order/oinfo/?logid=12456601692948789539&gid=1000';
    $param['cookie'] = $cookie;
    $param['data'] = [
        'req' => "{\"src\":\"oinfo\",\"od_link_id\":\"{$od_link_id}\",\"b_inner_trans_in_batch_id\":\"\",\"od_basic_id\":\"{$od_basic_id}\",\"order_tp_id\":\"def_ot\"}"
    ];
    $data = lmh_curl('post', $param);
    return $data['res']['order_data'][$co_type]['value'];
}

function genSql(){
    global $expense_type;
    global $cookie;
    $param['url'] = 'http://yundan.vkj56.cn/api/Table/Search/orderList?logid=12456601692923938382&gid=1000&btnLoadingTag=off';
    $param['cookie'] = $cookie;
    $param['data'] = [
        'req' => '{"category":"Order","tab":"od_all","sort":{},"page_num":1,"page_size":100,"fetch_mode":"body","cid":"73067e693dac1586773c349fa93a5d65","query":{"9999":{"_logic":"or","query_num._exact_":["23070631765","23070622483","23070623751","23070724597","23070627348","23070631765","23070630505","23070623576","23070567199","23070573654","23070612848","23070614072","23070622483","23070618937","23070619686","23070620805","23070620913","23070621277","23070621833","23070623931","23070623751","23070614026"],"order_num._exact_":["23070631765","23070622483","23070623751","23070724597","23070627348","23070631765","23070630505","23070623576","23070567199","23070573654","23070612848","23070614072","23070622483","23070618937","23070619686","23070620805","23070620913","23070621277","23070621833","23070623931","23070623751","23070614026"]}},"filter":{},"batch_search_order_by":["query_num","order_num"]}'
    ];
    $data = lmh_curl('post', $param);
    $data = $data['res']['data'];
    foreach ($data as $v) {
        $amount = genAmount($v['od_link_id'], $v['od_basic_id']);
        if (empty($amount)) {
            echo "运单 {$v['od_link_id']} 金额为空".PHP_EOL;
            continue;
        }
        $date = date('Y-m-d H:i:s');
        $sql = "INSERT INTO `cmm_pro`.`expense`(`link_id`, `basic_id`, `od_id`, `link_type`, `group_id`, `com_id`, `trans_mgr_id`, `b_link_id`, `link_com_id`, `expense`, `settle_am_d`, `settle_status`, `pa_re_bill_d`, `pa_re_status`, `cu_ac_ss`, `cu_ac_bill_d`, `bill_info`, `online_pay_info`, `mgr_id`, `last_mgr_id`, `mgr_time`, `em_ac_ss`, `em_ac_bill_d`, `com_ac_ss`, `invoice_status`, `invoice_am_d`, `com_ac_bill_d`, `amount`, `po_ac_am_d`, `po_ac_am_ss`, `direction`, `po_ac_bill_d`, `cash_status`, `cash_time`, `ledger_status`, `ledger_time`, `ledger_id`, `confirm_status`, `confirm_time`, `confirm_id`, `create_by`, `update_by`, `create_time`, `update_time`, `status`, `adp_am_d`, `adp_status`) VALUES ({$v['od_link_id']}, {$v['od_basic_id']}, {$v['od_id']}, 1, 1000, {$v['com_id']}, 0, 0, 0, {$expense_type}, NULL, 10, NULL, 10, 10, NULL, NULL, NULL, 0, 0, NULL, 10, 0.00, 0, 0, NULL, NULL, {$amount}, NULL, 0, 0, NULL, 10, NULL, 10, NULL, NULL, 10, NULL, NULL, 0, 0, {$date}, {$date}, 1, 0.00, 10);";
        echo $sql . "\n";
    }
//        $err_data = array_column($data, 'od_basic_id');
//        $err_data = array_map('intval', $err_data);
//        echo json_encode($err_data,true)."\n";
}
genSql(); //过多运单无法核销费用生成sql语句
// 本地执行记得去除代理 unset http_proxy