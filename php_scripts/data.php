<?php

require_once '../Application/Home/Common/function.php';

$txt = file_get_contents('C:\Users\Administrator\Documents\demo.json');
$ids = json_decode(file_get_contents(__DIR__.'/tmp.txt'),true);
$ids['59646'] = 0;
$ids['14800'] = 0;
$ids['60165'] = 0;
$ids['60230'] = 0;
$ids['65186'] = 0;
$json = json_decode($txt, true);
//echo count($json['RECORDS']);die();
//jdd($ids);
$arr = [];
foreach ($json as $rec) {
    $point_center_a_pt = [];
    $arr1 = json_decode($rec['point_center_a_pt'],true);
    foreach ($arr1 as $id) {
        $point_center_a_pt[] = $ids[$id];
    }
    $point_center_a_pt = array_values(array_unique(array_filter($point_center_a_pt)));
    $point_center_a_pt = json_encode($point_center_a_pt,256);
    $start_point_a_pt = [];
    $arr2 = json_decode($rec['start_point_a_pt'],true);
    foreach ($arr2 as $id) {
        $start_point_a_pt[] = $ids[$id];
    }
    $start_point_a_pt = array_values(array_unique(array_filter($start_point_a_pt)));
    $start_point_a_pt = json_encode($start_point_a_pt,256);
    $com_id = $ids[$rec['company_id']];
    $date = date('Y-m-d H:i:s');
    $ext = json_decode($rec['ext'],true);
    foreach ($ext['downstream'] as &$v) {
        $v['ids'] = fun($v['ids'],$ids);
    }
    unset($v);
    $ext['start_infos']['ids'] = fun($ext['start_infos']['ids'],$ids);
    $ext['end_infos']['ids'] = fun($ext['end_infos']['ids'],$ids);
    $ext = json_encode($ext,256);
    $rec['group_id'] = 2024;
    $rec['company_id'] = $com_id;
    $rec['start_point_a_pt'] = $start_point_a_pt;
    $rec['point_center_a_pt'] = $point_center_a_pt;
    $rec['ext'] = $ext;
    $rec['create_time'] = $date;
    $rec['update_time'] = $date;
    $rec['create_by'] = 61954;
    $rec['create_by_uid'] = 128863;
    $rec['update_by'] = 61954;
    $rec['update_by_uid'] = 128863;
//    $sql = "INSERT INTO `cmm_pro`.`p_info` (`group_id`, `company_id`, `use_corp_type`, `price_name`, `pm_id`, `price_type`, `price_mode`, `fee_name`, `product_a_pt`, `cor_a_pt`, `cor_unit_a_pt`, `cee_a_pt`, `cee_unit_a_pt`, `delivery_mode_a_pt`, `transport_mode_a_pt`, `carrier_a_pt`, `goods_type_a_pt`, `goods_name_a_pt`, `spe_goods_a_pt`, `point_center_a_pt`, `start_point_a_pt`, `route_s_point_a_pt`, `route_e_point_a_pt`, `order_arr_a_pt`, `trans_outer_a_pt`, `the_carrier_a_pt`, `weight_a_pt_start`, `weight_a_pt_end`, `price_weight_a_pt_start`, `price_weight_a_pt_end`, `volume_a_pt_start`, `volume_a_pt_end`, `is_car`, `service_a_pt`, `wv_ratio_a_pt_start`, `wv_ratio_a_pt_end`, `good_pkg_a_pt`, `num_a_pt_start`, `num_a_pt_end`, `declared_a_pt_start`, `declared_a_pt_end`, `tao_a_pt_start`, `tao_a_pt_end`, `discount_info`, `fee_ave_type`, `cor_type`, `state`, `enable_time`, `remark`, `fee_attr_com`, `ext`, `status`, `create_by`, `create_by_uid`, `create_time`, `update_by`, `update_by_uid`, `update_time`, `effect_a_pt_start`, `effect_a_pt_end`, `billing_date_a_pt_start`, `billing_date_a_pt_end`, `through_arr_point_a_pt`, `first_transit_shed_a_pt`, `float_info`, `user_type_a_pt`, `department_a_pt`, `position_a_pt`, `end_zone_join_route`) VALUES (2024, {$com_id}, {$rec['use_corp_type']}, {$rec['price_name']}, {$rec['pm_id']}, {$rec['price_type']}, {$rec['price_mode']}, {$rec['fee_name']}, {$rec['product_a_pt']}, {$rec['cor_a_pt']}, {$rec['cor_unit_a_pt']}, {$rec['cee_a_pt']}, {$rec['cee_unit_a_pt']}, {$rec['delivery_mode_a_pt']}, {$rec['transport_mode_a_pt']}, {$rec['carrier_a_pt']}, {$rec['goods_type_a_pt']}, {$rec['goods_name_a_pt']}, {$rec['spe_goods_a_pt']}, {$point_center_a_pt}, {$start_point_a_pt}, {$rec['route_s_point_a_pt']}, {$rec['route_e_point_a_pt']}, {$rec['order_arr_a_pt']}, {$rec['trans_outer_a_pt']}, {$rec['the_carrier_a_pt']}, {$rec['weight_a_pt_start']}, {$rec['weight_a_pt_end']}, {$rec['price_weight_a_pt_start']}, {$rec['price_weight_a_pt_end']}, {$rec['volume_a_pt_start']}, {$rec['volume_a_pt_end']}, {$rec['is_car']}, {$rec['service_a_pt']}, {$rec['wv_ratio_a_pt_start']}, {$rec['wv_ratio_a_pt_end']}, {$rec['good_pkg_a_pt']}, {$rec['num_a_pt_start']}, {$rec['num_a_pt_end']}, {$rec['declared_a_pt_start']}, {$rec['declared_a_pt_end']}, {$rec['tao_a_pt_start']}, {$rec['tao_a_pt_end']}, {$rec['discount_info']}, {$rec['fee_ave_type']}, {$rec['cor_type']}, {$rec['state']}, {$rec['enable_time']}, {$rec['remark']}, {$rec['fee_attr_com']}, {$ext}, {$rec['status']}, 61954, 128863, {$date}, 61954, 128863, {$date}, {$rec['effect_a_pt_start']}, {$rec['effect_a_pt_end']}, {$rec['billing_date_a_pt_start']}, {$rec['billing_date_a_pt_end']}, {$rec['through_arr_point_a_pt']}, {$rec['first_transit_shed_a_pt']}, {$rec['float_info']}, {$rec['user_type_a_pt']}, {$rec['department_a_pt']}, {$rec['position_a_pt']}, {$rec['end_zone_join_route']});";
//    $sql = "INSERT INTO `cmm_pro`.`p_info` (`group_id`, `company_id`, `use_corp_type`, `price_name`, `pm_id`, `price_type`, `price_mode`, `fee_name`, `product_a_pt`, `cor_a_pt`, `cor_unit_a_pt`, `cee_a_pt`, `cee_unit_a_pt`, `delivery_mode_a_pt`, `transport_mode_a_pt`, `carrier_a_pt`, `goods_type_a_pt`, `goods_name_a_pt`, `spe_goods_a_pt`, `point_center_a_pt`, `start_point_a_pt`, `route_s_point_a_pt`, `route_e_point_a_pt`, `order_arr_a_pt`, `trans_outer_a_pt`, `the_carrier_a_pt`, `weight_a_pt_start`, `weight_a_pt_end`, `price_weight_a_pt_start`, `price_weight_a_pt_end`, `volume_a_pt_start`, `volume_a_pt_end`, `is_car`, `service_a_pt`, `wv_ratio_a_pt_start`, `wv_ratio_a_pt_end`, `good_pkg_a_pt`, `num_a_pt_start`, `num_a_pt_end`, `declared_a_pt_start`, `declared_a_pt_end`, `tao_a_pt_start`, `tao_a_pt_end`, `discount_info`, `fee_ave_type`, `cor_type`, `state`, `enable_time`, `remark`, `fee_attr_com`, `ext`, `status`, `create_by`, `create_by_uid`, `create_time`, `update_by`, `update_by_uid`, `update_time`, `effect_a_pt_start`, `effect_a_pt_end`, `billing_date_a_pt_start`, `billing_date_a_pt_end`, `through_arr_point_a_pt`, `first_transit_shed_a_pt`, `float_info`, `user_type_a_pt`, `department_a_pt`, `position_a_pt`, `end_zone_join_route`) VALUES (2024, {$com_id}, {$rec['use_corp_type']}, {$rec['price_name']}, {$rec['pm_id']}, {$rec['price_type']}, {$rec['price_mode']}, {$rec['fee_name']}, {$rec['product_a_pt']}, {$rec['cor_a_pt']}, {$rec['cor_unit_a_pt']}, {$rec['cee_a_pt']}, {$rec['cee_unit_a_pt']}, {$rec['delivery_mode_a_pt']}, {$rec['transport_mode_a_pt']}, {$rec['carrier_a_pt']}, {$rec['goods_type_a_pt']}, {$rec['goods_name_a_pt']}, {$rec['spe_goods_a_pt']}, {$point_center_a_pt}, {$start_point_a_pt}, {$rec['route_s_point_a_pt']}, {$rec['route_e_point_a_pt']}, {$rec['order_arr_a_pt']}, {$rec['trans_outer_a_pt']}, {$rec['the_carrier_a_pt']}, {$rec['weight_a_pt_start']}, {$rec['weight_a_pt_end']}, {$rec['price_weight_a_pt_start']}, {$rec['price_weight_a_pt_end']}, {$rec['volume_a_pt_start']}, {$rec['volume_a_pt_end']}, {$rec['is_car']}, {$rec['service_a_pt']}, {$rec['wv_ratio_a_pt_start']}, {$rec['wv_ratio_a_pt_end']}, {$rec['good_pkg_a_pt']}, {$rec['num_a_pt_start']}, {$rec['num_a_pt_end']}, {$rec['declared_a_pt_start']}, {$rec['declared_a_pt_end']}, {$rec['tao_a_pt_start']}, {$rec['tao_a_pt_end']}, {$rec['discount_info']}, {$rec['fee_ave_type']}, {$rec['cor_type']}, {$rec['state']}, {$rec['enable_time']}, {$rec['remark']}, {$rec['fee_attr_com']}, {$ext}, {$rec['status']}, 61954, 128863, {$date}, 61954, 128863, {$date}, {$rec['effect_a_pt_start']}, {$rec['effect_a_pt_end']}, {$rec['billing_date_a_pt_start']}, {$rec['billing_date_a_pt_end']}, {$rec['through_arr_point_a_pt']}, {$rec['first_transit_shed_a_pt']}, {$rec['float_info']}, {$rec['user_type_a_pt']}, {$rec['department_a_pt']}, {$rec['position_a_pt']}, {$rec['end_zone_join_route']});";
    $sql = create_sql(' `cmm_pro`.`p_info` ',$rec);
//    json_encode($sql);
//    jdd($sql);

//    $arr[$rec['group_id'].'_'.$rec['company_name']] = $rec;
//    $arr[] = $rec['company_id'];
//    $point_center_a_pt = array_map('intval', json_decode($rec['point_center_a_pt'], true));
//    $arr = array_merge($arr, $point_center_a_pt);
//    $start_point_a_pt = array_map('intval', json_decode($rec['start_point_a_pt'], true));
//    $arr = array_merge($arr, $start_point_a_pt);
////    jdd($point_center_a_pt);
//    $ext = json_decode($rec['ext'], true);
//    foreach ($ext['downstream'] as $v) {
//        if (isset($v['ids']) && !empty($v['ids'])) {
//            $ids = array_map('intval', $v['ids']);
//            $arr = array_merge($arr, $ids);
//        }
//    }
//
//    $start_infos_ids = array_map('intval', $ext['start_infos']['ids']);
//    $arr = array_merge($arr, $start_infos_ids);
//    $end_infos_ids = array_map('intval', $ext['end_infos']['ids']);
//    $arr = array_merge($arr, $end_infos_ids);
//
//    $arr = array_unique($arr);
//    if (!empty($error['error']['pk_groups']['od_link'])) {
//        $arr = array_merge($error['error']['pk_groups']['od_link'], $arr);
//        $ids = implode(',', $error['error']['pk_groups']['customer']) . "\n";
//        file_put_contents('C:\Users\Administrator\Documents\customer.txt', $ids, FILE_APPEND);
//    }
//    header('Content-Type: application/json; charset=utf-8');
//    echo json_encode($error['error']['pk_groups']['customer'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
//    exit();
//    $ids = implode(',', array_unique($arr));
//    jdd($rec);
    file_put_contents(__DIR__.'/tmp.sql', $sql.PHP_EOL, FILE_APPEND);
//    jdd(1);

}
function fun($arr,$ids) {
    $a = [];
    foreach ($arr as $v) {
        $a[] = $ids[$v];
    }
    return array_values(array_unique(array_filter($a)));
}
function create_sql($table,$arr)
{
    foreach ($arr as $k => $v)
    {
        if ($k == 'id'){
            continue;
        }

        $f[] = '`'.$k.'`';
        if (is_int($v)) {
            $val[] =  $v ;
        } elseif(is_null($v)) {
            $val[] = 'null';
        }else {
        $val[] = "'" . $v . "'";
    }

    }
    $f = implode(',',$f);
    $val = implode(',',$val);
    return "insert into ".$table."(".$f.") values (".$val.");";
}
//jdd($arr);
//$res = [];
//foreach ($arr as $v) {
//    if ($v['group_id'] == 1000) {
//        $index = '2024'.'_'.$v['company_name'];
//        if (isset($arr[$index])) {
//            $res[$v['id']] = $arr['2024'.'_'.$v['company_name']]['id'];
//        } else {
//            $res[$v['id']] = 0;
//        }
//    }
//}

//jdd(count($res));

