<?php

use Home\Cnsts\ERRNO;

/**
 * 获取系统设置
 * @param[in] $setting_key 设置项key，为null会返回全部
 * @param[in] $need_list 是否返回打平结果
 * return
 *  参看系统设置文件
 * */
function getSetting($setting_key = null, $need_list = 1)
{
    return D("Common/CSetting", "Service")->getSetting(
        $setting_key,
        session("group_id"),
        session("company_id"),
        session("user_id"),
        0,
        $need_list
    );
}

function getFieldDict($group_id=null, $company_id=null, $category='', $tab='') {
    return \Common\Service\CSettingService::getFieldDict($group_id, $company_id, $category, $tab);
}

function getDisp($display, $group_id=null, $company_id=null, $category='', $tab='') {
    $dict_set = getFieldDict($group_id, $company_id, $category, $tab);
    $display = empty($dict_set[$display]) ? $display : $dict_set[$display];
    return $display;
}

function needToG7(){
    $g7_setting = getSetting('g7_setting');
    $key    = $g7_setting['app_key']['value'];
    $secret = $g7_setting['app_secret']['value'];
    if (!empty($key) && !empty($secret)){
        return true;
    }else{
        return false;
    }
}

/**
 * 是否是哈勃账号
 *
 * @return bool
 */
function isOMS() {
    return sysVersion() == \Basic\Cnsts\CMM_SYSTEM::OMS_SYS;
}

/**
 * 是否是麦哲伦账号
 *
 * @return bool
 */
function isWMS() {
    return sysVersion() == \Basic\Cnsts\CMM_SYSTEM::WMS_SYS;
}

/**
 * 是否是瓦特账号
 *
 * @return bool
 */
function isVMS() {
    return sysVersion() == \Basic\Cnsts\CMM_SYSTEM::VMS_SYS;
}

function sysVersion(){
    if (empty(session('sys_version'))){
        session('sys_version', \Basic\Cnsts\CMM_SYSTEM::TMS_SYS);
        cmm_log(['sys_version_error' => true]);
    }
    return session('sys_version');
}

/**
 * 不同子系统对应的特殊字段取值
 * 只支持以下字段的输入
 * psn_perm kv中集团权限对应的key
 * version_id 对应的不同子系统在company_user_pro中对应的version_id保存字段
 * dft_type 默认的用户组类型
 * dft_type_ex 用户组列表显示规则
 * role_ids  user表中存的字段，不同子系统对应字段不同
 *
 * @return array|string
 */
function getSysVersionInfo($sys_field = null){
    $sys_version = sysVersion();
    $sys_enums = \Basic\Cnsts\CMM_SYSTEM::CMM_SYSTEM_ENUMS;
    if ($sys_field){
        return $sys_enums[$sys_version][$sys_field];
    }else{
        return $sys_enums[$sys_version];
    }
}

/**
 * 根据group_id所在的集团版本 确认BMS是否有"查看凭证列表"权限
 * @param[in] int $group_id 集团id
 * @param string $perm_str 权限字符串，只支持单个权限判断，兼容叶子节点及父节点
 * @return bool 勾选权限true 没有勾选权限false
 * */
function hasBmsAuth($group_id, $perm_str) {
    if (empty($perm_str) || $group_id <= 0) {
        return false;
    }
    // BMS 勾选哪些权限
    $prefix = getSysVersionInfo('psn_perm');
    $psn_perm = D("Common/CKV", "Service")->getKey($prefix, $group_id);
    $psn_perm = json_decode($psn_perm, true);
    return (array_key_exists($perm_str, array_flip($psn_perm["node_ids"])) ||
        array_key_exists($perm_str, array_flip($psn_perm["node_ids_ex"]))) ? true : false;
}

/**
 * 兼容老版本权限使用
 * */
function isPK($access = null, $target_id = null, $need_op_range = 0, $user_id = null)
{
    if (IS_CLI) {
        return true;
    }
    /** @var \Common\Service\CPermissionService $srv_perm */
    $srv_perm = D("Common/CPermission", "Service");
    return $srv_perm->isPK($access, $target_id, $need_op_range, $user_id);
}

function getFiletInfo($getL=false){
    $nc = [];
    $nc_ld = [];
    $nc_zgs = [];
    $nc_up = [];
    $ot = [];
    $ot_ld=[];
    $kv_srv     = D("Common/CKV", "Service");
    $hidden_info = jsonDecodeAsArray($kv_srv->getKey("hidden_info" ,session("group_id")));
    $ot_zgs = $hidden_info['hidden_filter']['company_ids'];
    $hidden_time = $hidden_info['hidden_filter']['hidden_time'];
    $ot_up = [];
    $orgService = new \Common\Service\COrgService(1000, 2, null);
    if (!empty($nc_zgs)){
        foreach ($nc_zgs as $item){
            $com_list = $orgService->getOrgList($item);
            $tmp_point_ids = array_column($com_list,'id')??[];
            $up_com_list = $orgService->getOrgList($item,'sup');
            $tmp_up_ids = array_column($up_com_list,'id')??[];
            $nc = array_merge($tmp_point_ids,$nc);
            $nc_up = array_merge($tmp_up_ids,$nc_up);
            $nc_up = array_diff($nc_up,$nc_zgs);
            if ($getL){
                $ledger_id_sub_list = LedgerService::getLedgerListByFilter([1000],null,null, $tmp_point_ids);
                $tmp_ledger_ids = array_column($ledger_id_sub_list,'id');
                $nc_ld = array_merge($tmp_ledger_ids,$nc_ld);
            }
        }
    }

    if (!empty($ot_zgs)){
        foreach ($ot_zgs as $item){
            $com_list = $orgService->getOrgList($item);
            $tmp_point_ids = array_column($com_list,'id')??[];
            $up_com_list = $orgService->getOrgList($item,'sup');
            $tmp_up_ids = array_column($up_com_list,'id')??[];
            $ot = array_merge($tmp_point_ids,$ot);
            $ot_up = array_merge($tmp_up_ids,$ot_up);
            $ot_up = array_diff($ot_up,$ot_zgs);
            if ($getL){
                $ledger_id_sub_list = LedgerService::getLedgerListByFilter([1000],null,null, $tmp_point_ids);
                $tmp_ledger_ids = array_column($ledger_id_sub_list,'id');
                $ot_ld = array_merge($tmp_ledger_ids,$ot_ld);
            }
        }
    }

    $ot_date_range = [
        ['<','2022-01-01 00:00:00'],
    ];
    $ot_date_range = !empty($hidden_time) ? [['<',$hidden_time]] : $ot_date_range;

    return [$nc,$nc_ld,$ot,$ot_ld,$ot_date_range,$nc_up,$ot_up,$nc_zgs,$ot_zgs];
}

/**
 * 降级的权限集合
 * @param string $permission
 * @param bool   $clear
 *
 * @return array
 */
function permission_collector($permission = '', $clear = true)
{
    static $data = [];
    if (!empty($permission)) {
        $data[] = $permission;
        $data = array_values(array_unique($data));
        return $data;
    } else if (!$clear) {
        return $data;
    } else {
        $tmp  = $data;
        $data = [];
        return $tmp;
    }
}

/**
 * 抽取csv格式文件
 * @param[in] $file文件名
 * return 返回文件内容
 * */
function extract_csv($file,$type = '') {

    // 读取导出文件内容，并进行转码
    $file_cnt = iconv('GBK', 'UTF-8', file_get_contents($file));
    // 如读取或者转码失败，返回false
    if (!$file_cnt) {
        return false;
    }
    // 删除末尾的空行
    $file_cnt = rtrim($file_cnt);
    // 文件切分为行
    $file_lines = explode(PHP_EOL, $file_cnt);
    // 存放最后结果
    $ret_data = [];
    // 逐行解析
    foreach ($file_lines as $line_cnt) {
        if($type == 'import_bills'){
            $ret_data []= explode(",", $line_cnt);
        }else{
            $ret_data []= explode("\t", $line_cnt);
        }


    }
    return $ret_data;
}

/**
 * 从Excel读取数据
 * @param $file string 文件名
 * @param int $need_shift_col 是否转变列数
 * @param int $need_header 是否需要表头，默认不需要
 * @param int $max_row 获取数据的最大行数
 * return 获取excel文件内容
 */
function extractExcel($file, $need_shift_col = 1, $sheet_index=0, $need_header = false, $max_row=10000)
{

    Vendor('PHPExcel.IOFactory');
    if (!file_exists($file)) {
        return ERRNO::FILE_NOT_EXISTS;
    }
    $PHPReader = new \PHPExcel_Reader_Excel2007();
    if (!$PHPReader->canRead($file)) {
        $PHPReader = new \PHPExcel_Reader_Excel5();
        if (!$PHPReader->canRead($file)) {
            return ERRNO::FILE_NOT_EXISTS;
        }
    }

    $PHPExcel      = $PHPReader->load($file);
    $currentSheet  = $PHPExcel->getSheet($sheet_index);
    $objWorksheet  = $currentSheet;
    $allRow        = $objWorksheet->getHighestDataRow();
    $highestColumn = $objWorksheet->getHighestDataColumn();
    $allColumn     = \PHPExcel_Cell::columnIndexFromString($highestColumn);
    $ret           = [];
    $rows          = $need_header ? 1 : 2;
    // 默认取得的最大行数加上表头 例如：取3000行数据，$allRow需要为3002，以便判断是否超3000行数据
    $allRow = $allRow > ($max_row + 1) ? ($max_row + 2) : $allRow;

    if ($need_shift_col) {
        if ($allColumn > 10) {
            $allColumn = $allColumn - 3;
        }
    }
    $allColumn = ($allColumn <= 200) ? $allColumn : 200;// 只取excel的前200列
    for ($currentRow = $rows; $currentRow <= $allRow; $currentRow++) {
        $row = [];
        for ($currentColumn = 0; $currentColumn < $allColumn; $currentColumn++) {
            $col     = \PHPExcel_Cell::stringFromColumnIndex($currentColumn);
            $address = $col . $currentRow;
            // 这里要判断单元格的属性
            $type = $objWorksheet->getCell($address)->getStyle()->getNumberFormat()->getFormatCode();
            $val = $objWorksheet->getCell($address)->getValue();
            if ($type == PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14) {      // 日期格式
                $value = !empty($val) ? gmdate("Y-m-d H:i:s", PHPExcel_Shared_Date::ExcelToPHP($val)) : '';
            } elseif ($type == PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME3) {  // 时间格式
                $value = !empty($val) ? gmdate("H:i:s", PHPExcel_Shared_Date::ExcelToPHP($val)) : '';
            } else {
                $value = $val;
            }
            if (is_object($value)) {
                $value = $value->__toString();
            }
            // fix BUG #52971 preg_replace会导致浮点数精度问题
            if (!is_numeric($value)) {
                // 去掉excel单元格中多余的换行符（否则会导致数据错行，fix bug27016）
                $value = preg_replace('/\r|\n|\t/', '', $value);
            } else {
//                cmm_log(['>>>', __CLASS__, __FUNCTION__, '#' . __LINE__, $value, is_float($value) ? 'is float' : 'not float',
//                    is_int($value) ? 'is int' : 'not int', is_string($value) ? 'is string' : 'not string', (string)$value]);
                $str_value = (string)$value;
                // 只有浮点数才进行处理。对于excel加载出来的整数也会是浮点数类型,即is_int会是false，is_float是true
                if (!is_string($value) && is_float($value) && false !== strpos($str_value, '.')) {
                    // fix BUG #52971 为了防止浮点数精度问题，因为系统中目前最大的进度是小数点后6位，所以这里保留6位精度
                    $value = number_format($value, 6);
                    $value = rtrim($value, '0');
                    // 如果输入的是整数，那么trim了 0 之后，会是这样 40.
                    // 多一个点，所以这里得trim掉那个点
                    $dot_pos = strpos($value, '.');
                    if ($dot_pos !== false && $dot_pos == (strlen($value) - 1)) {
                        $value = rtrim($value, '.');
                    }
                    // 处理number_format之后的逗号
                    $value = str_replace(',', '', $value);
                }
            }
            $row[$currentColumn] = $value;
        }
        $ret[] = $row;
    }
    unset($objWorksheet);
    unset($PHPReader);
    unset($PHPExcel);
    unlink($file);

    // 去掉最后的空行，避免因为最后空行提示行数超过
    $last_row = array_filter($ret[count($ret) - 1]);
    while (empty($last_row)) {
        array_pop($ret);
        $last_row = empty($ret) ? 1 : array_filter($ret[count($ret) - 1]);
    }

    return $ret;
}

/**
 * 价格导出导出excel
 * @param[in] $file_name 导出文件名称
 * @param[in] $header Excel 列表头
 * @param[in] $body list数据
 * @param[in] $footer 合计数据
 * @param[in] $need_fields_title 必填字段中文名称
 * return null
 * */
function expExcelPrice(
    $file_name,
    $header_all,
    $body,
    $footer,
    $need_fields_title = [],
    $ext = [])
{
    ini_set('memory_limit', '512M');
    // 登录info信息
    $info = array_key_exists("info", $ext) ? $ext["info"] : [];
    $format = array_key_exists("info", $ext) ? $ext["format"] : 'csv';
    $is_download_fmt = array_key_exists("is_download_fmt", $ext) ? $ext["is_download_fmt"] : 0;
    $truck_type = array_key_exists("truck_type", $ext) ? $ext["truck_type"] : [];
    $truck_length = array_key_exists("truck_length", $ext) ? $ext["truck_length"] : [];
    // 兼容之前表头
    $header = array_column($header_all, "title");
    $dy_h = array_column($header_all, null, "title");
    // 初始化excel信息
    $cell_offset      = 0;
    $row_num          = 1;
    $max_column_key   = 'A';
    $column_key_start = 'A';
    $column_key_count = count($header);

    // Create new PHPExcel object
    vendor("PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $act_sheet = $objPHPExcel->getActiveSheet();

    // 表头格式
    $head_style = [
        'borders'   => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ],
        'font'      => [
            'bold' => true,
            'size' => 10,
        ],
    ];
    // body格式
    $body_style = [
        'borders'   => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ],
        'font'      => [
            'size' => 10,
        ],
    ];
    // 单元格格式长度，12表示4个汉字,以此为标准
    $standard = 12;
    // <12 长度校验
    $adjust_s = 1;
    // >=12 长度校验
    $adjust_l = 2;
    // 同时设置单元格格式
    $key = $column_key_start;
    $shift_k = [];
    $col_num = 1;
    for ($i = 0; $i < $column_key_count; $i++) {
        //// 设置单元格长度
        //$l = strlen($header[$i]);
        //if ($l >= $standard) {
        //    $s = $l - $adjust_l;
        //} else {
        //    $s = $l - $adjust_s;
        //}
        //$act_sheet->getColumnDimension($key)->setWidth($s);
        $act_sheet->getRowDimension('1')->setRowHeight('20');
        // 必填字段用红色字体
        if (in_array($header[$i], $need_fields_title)) {
            $objPHPExcel->getActiveSheet()->getStyle($key . '1')
                ->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        }
        $cell_offset = 3;
        if ($dy_h[$header[$i]]["is_dh"]) {
            $t = $key;
            $shift_k[$key] = "1";
            $act_sheet->setCellValue($key.'1', $header[$i]);
            if ($dy_h[$header[$i]]["type"] == 'car') {
                $cell_offset = count($truck_type);
                foreach ($truck_type  as $_k => $_v) {
                    $act_sheet->setCellValue($key.'2', $_v);
                    if ($_k != $cell_offset - 1)
                        $key++;
                    $shift_k[$key] = "1";
                }
            } else {
                $un = '首方';
                switch ($dy_h[$header[$i]]["type"]) {
                    case 'weight':  // no break
                    case 'wv_ratio':  // no break
                        $un = '首重';
                        break;
                    case 'co_delivery':
                        $un = '首元';
                        break;
                }
                $first_price = '首价';
                if ($dy_h[$header[$i]]["type"] == "num") {
                    $un = '起步件数';
                    $first_price = '起步价';
                }
                if (in_array($ext['tabs'][0], ["上浮比例", "直达起步价格", "中转起步价格", "件数差额"]) || $ext['p_mode'] == 18){
                    $act_sheet->setCellValue($key.'2', "续价");
                }else{
                    $act_sheet->setCellValue($key++.'2', $un);
                    $shift_k[$key] = "1";
                    $act_sheet->setCellValue($key++.'2', $first_price);
                    $shift_k[$key] = "1";
                    $act_sheet->setCellValue($key.'2', "续价");
                }
            }
            $act_sheet->mergeCells("{$t}1:{$key}1");
            $col_num += $cell_offset - 1;
        } else {
            $act_sheet->setCellValue($key . '1', $header[$i]);
        }
        $max_column_key = $key;
        $col_num++;
        $key++;
    }
    $key = $column_key_start;
    for ($i = 0; $i < $column_key_count; $i++) {
        if (!array_key_exists($key, $shift_k)) {
            $act_sheet->mergeCells("{$key}1:{$key}2");
        } else {
            $key++;
            $key++;
        }
        $key++;
    }
    $objPHPExcel->getActiveSheet()->getStyle('A1:' . $max_column_key . '2')
        ->applyFromArray($head_style);
    $row_num++;
    // 导出body
    $body = array_values($body);
    $body_count = count($body);
    // 组织架构枚举
    $group_info = D("Basic/Org", "Service")->getGroupAll($info["group_id"], false);
    //员工类型
    $user_type_info = \Basic\Cnsts\PRICE_SYSTEM_V2::USER_TYPE_A_PT_ENUM;
    /** @var \Basic\Model\OrgModel $org_model */
    $org_model = D('Basic/Org', 'Model');
    //职位枚举
    $position_info = $org_model->getPosition(
        ["group_id" => $info["group_id"]],
        "id, position_name as name"
    );
    $position_info = array_column($position_info,null,'id');
    //部门枚举
    $department_info = $org_model->getDepartment(
        ['group_id'=>$info["group_id"]], 'id, department_name as name');
    $department_info = array_column($department_info,null,'id');
    // 收费方枚举
    $charge_id = \Basic\Cnsts\PRICE_SYSTEM_V2::CHARGE_ID;
    for ($i = 0; $i < $body_count; $i++) {
        $key = $column_key_start;
        if ($is_download_fmt) {
            for ($j = 0; $j < $col_num; $j++) {
                $act_sheet->setCellValue($key++ . (3 + $i), $body[$i][$j]);
            }
            continue;
        }
        // 动态价格，都需要获取
        $it_ext = $body[$i]["ext"];
        // 产品线路下载模板会带出产品线路数据
        if ($ext["is_product"]) {
            foreach ($header_all as $__k => $it) {
                $__it = $body[$i][$__k];
                switch ($__k) {
                    case "start_province":
                    case "start_city":
                    case "start_area":
                    case "start_town":
                    case "end_province":
                    case "end_city":
                    case "end_area":
                    case "end_town":
                        if ($__k == "start_province")
                            $__it = $body[$i]["line_start_addr"];
                        elseif ($__k == "end_province")
                            $__it = $body[$i]["line_end_addr"];
                        else
                            break;
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it["province"]);
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it["city"]);
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it["district"]);
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it["street"]);
                        break;
                    case "line_no":
                        $__it = sprintf("%06d", $__it);
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it);
                        break;
                    case "line_type":
                    case "product_type":
                        $act_sheet->setCellValue($key++ . (3 + $i), $__it);
                        break;
                    default:
                        if ($format == 'csv_export') {
                            if ($it["is_dh"]) {
                                // 处理动态标价
                                if ($it["type"] == 'car') {
                                    foreach ($truck_type as $k_ => $v_) {
                                        $act_sheet->setCellValue($key++.($cell_offset + $i), $it_ext[$it["type"]][$it["title"]][$v_]);
                                    }
                                } else {
                                    $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][0]);
                                    $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][1]);
                                    $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][2]);
                                }
                            } else {
                                $show_val = $body[$i][$__k];
                                $act_sheet->setCellValue($key++ . (3 + $i), $show_val);
                            }
                        }
                        break;
                }
            }
        } elseif ($ext["is_zone"]) {
            foreach ($header_all as $__k => $it) {
                if ($format == 'csv') {
                    if (in_array($__k, ["zone_no", "zone_name"])) {
                        $show_val = $body[$i][$__k];
                        $act_sheet->setCellValue($key++ . (3 + $i), $show_val);
                    }
                } else {
                    if ($it["is_dh"]) {
                        // 处理动态标价
                        if ($it["type"] == 'car') {
                            foreach ($truck_type as $k_ => $v_) {
                                $act_sheet->setCellValue($key++.($cell_offset + $i), $it_ext[$it["type"]][$it["title"]][$v_]);
                            }
                        } else {
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][0]);
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][1]);
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][2]);
                        }
                    } else {
                        if ($__k == "dispatch_zone" && (!empty($body[$i]['addr_zone'])||!empty($body[$i]['point_zone'])||!empty($body[$i]['distribute_zone'])||(!empty($body[$i]['mile_min'])||!empty($body[$i]['mile_max'])))) {
                            $addr_zone = $body[$i]["addr_zone"] ?? [];
                            $point_zone = $body[$i]["point_zone"] ?? [];
                            $distribute_zone = $body[$i]["distribute_zone"] ?? [];
                            $mile_min = $body[$i]['mile_min']??0;
                            $mile_max = $body[$i]['mile_max']??0;
                            $show_val = '';
                            foreach ($addr_zone as $zone) {
                                $show_val .= $zone['province'] . $zone['city'] . $zone['district'] . $zone['street'];
                                if ($zone != end($addr_zone)) {
                                    $show_val .= ",";
                                }
                            }

                            $company_ids = array_merge($point_zone, $distribute_zone);
                            foreach ($company_ids as $company_id) {
                                $show_val .= $group_info[$company_id]["name"];;
                                if ($company_id != end($company_ids)) {
                                    $show_val .= ",";
                                }
                            }

                            if (!empty($mile_max)||!empty($mile_min)){
                                $show_val.="$mile_min<里程<=$mile_max";
                            }

                            $act_sheet->setCellValue($key++ . (3 + $i), $show_val);
                        }else{
                            $show_val = $body[$i][$__k];
                            $act_sheet->setCellValue($key++ . (3 + $i), $show_val);
                        }
                    }
                }
            }
            //$zone_no = $body[$i]["zone_no"];
            //$zone_name = $body[$i]["zone_name"];
            //foreach ([$zone_no, $zone_name] as $__k => $__v) {
            //    if ((is_int($__v) && $__v > 999999999999) ||
            //        (strlen($__v) > 12 && (strpos($__v, '.') == false))
            //    ) {
            //        $act_sheet->setCellValueExplicit($key++ . (3 + $i), $__v);
            //    } else {
            //        $act_sheet->setCellValue($key++ . (3 + $i), $__v);
            //    }
            //}
        } else {
            foreach ($header_all as $__k => $it) {
                if ($it["is_dh"]) {
                    // 处理动态标价
                    if ($it["type"] == "car") {
                        foreach ($truck_type as $k_ => $v_) {
                            $act_sheet->setCellValue($key++.($cell_offset + $i), $it_ext[$it["type"]][$it["title"]][$v_]);
                        }
                    } else {
                        if (in_array($ext['tabs'][0], ["上浮比例", "直达起步价格", "中转起步价格", "件数差额"]) || $ext['p_mode'] == 18){
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][0]);
                        }else{
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][0]);
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][1]);
                            $act_sheet->setCellValue($key++.(3 + $i), $it_ext[$it["type"]][$it["title"]][2]);
                        }
                    }
                } else {
                    $show_val = $body[$i][$__k];
                    // 处理显示字段
                    switch ($__k) {
                        case "start_pid":
                        case "end_pid":
                        case "point_id":
                        case "trans_org":
                            $show_val = $group_info[$show_val]["name"];
                            break;
                        case "charge_id":
                            $show_val = $charge_id[$show_val]["display"];
                            break;
                        case "user_type":
                            $show_val = $user_type_info[$show_val]["display"];
                            break;
                        case "department":
                            $show_val = $department_info[$show_val]["name"];
                            break;
                        case "position":
                            $show_val = $position_info[$show_val]["name"];
                            break;
                        case "user_id":
                            $names = array_column($show_val,"name");
                            $show_val = implode(",",$names);
                            break;
                        case "formula":
                            $formal_text = "";
                            foreach ($show_val as $index=>$item){
                                $tmp_ratio = $item['ratio'];
                                $tmp_operator = $item['operator']=='add'?'+':'-';
                                $tmp_fee = implode("+",$item['itemList']);
                                if ($index==0){
                                    $tmp_formula = "($tmp_fee)x$tmp_ratio";
                                }else{
                                    $tmp_formula = "$tmp_operator($tmp_fee)x$tmp_ratio";
                                }
                                $formal_text.=$tmp_formula;
                            }
                            $show_val = $formal_text;
                            break;
                        case "arr_zone":
                        case "order_zone":
                        case "p_zone":
                            $show_val = $show_val["show_val"];
                            break;
                        case "trans_hour":
                            $show_val = $it_ext['trans_hour'] ;
                            break;
                        default:
                            break;
                    }
                    $act_sheet->setCellValue($key++ . ($cell_offset + $i), $show_val);
                }
            }
        }
        $act_sheet->getRowDimension($row_num)->setRowHeight('18');
        $row_num++;
    }
    // 设置数据格式
    if ($body_count <= 2000) {
        $objPHPExcel->getActiveSheet()->getStyle('A3:' . $max_column_key . ($row_num))
            ->applyFromArray($body_style);
    }

    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
}


/**
 * 导出excel
 * @param[in] $file_name 导出文件名称
 * @param[in] $header Excel 列表头
 * @param[in] $body list数据
 * @param[in] $footer 合计数据
 * @param[in] $need_fields_title 必填字段中文名称
 * return null
 * */
function expExcel($file_name, $header, $body, $footer, $need_fields_title = [])
{
    // 初始化excel信息
    $row_num          = 1;
    $max_column_key   = 'A';
    $column_key_start = 'A';
    $column_key_count = count($header);

    // Create new PHPExcel object
    vendor("PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $act_sheet = $objPHPExcel->getActiveSheet();

    // 表头格式
    $head_style = [
        'borders'   => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ],
        'font'      => [
            'bold' => true,
            'size' => 10,
        ],
    ];
    // body格式
    $body_style = [
        'borders'   => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ],
        'font'      => [
            'size' => 10,
        ],
    ];
    // 单元格格式长度，12表示4个汉字,以此为标准
    $standard = 12;
    // <12 长度校验
    $adjust_s = 1;
    // >=12 长度校验
    $adjust_l = 2;
    // 同时设置单元格格式
    $key = $column_key_start;
    for ($i = 0; $i < $column_key_count; $i++) {
        // 设置单元格长度
        $l = strlen($header[$i]);
        if ($l >= $standard) {
            $s = $l - $adjust_l;
        } else {
            $s = $l - $adjust_s;
        }
        $act_sheet->getColumnDimension($key)->setWidth($s);
        $act_sheet->getRowDimension('1')->setRowHeight('20');
        // 必填字段用红色字体
        if (in_array($header[$i], $need_fields_title)) {
            $objPHPExcel->getActiveSheet()->getStyle($key . '1')
                ->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        }
        $max_column_key = $key;
        // content
        $act_sheet->setCellValue($key++ . '1', $header[$i]);
    }
    $objPHPExcel->getActiveSheet()->getStyle('A1:' . $max_column_key . '1')
        ->applyFromArray($head_style);
    $row_num++;
    // 导出body
    $body_count = 0;
    if (is_callable($body)) {
        foreach ($body() as $row) {
            $key = $column_key_start;
            for ($j = 0; $j < $column_key_count; $j++) {
                if ((is_int($row[$j]) && $row[$j] > 999999999999) ||
                    (!empty($row[$j]) && strpos($row[$j],'=') === 0) ||
                    (strlen($row[$j]) > 12 && (strpos($row[$j], '.') == false))
                ) {
                    $act_sheet->setCellValueExplicit($key++ . (2 + $body_count), $row[$j]);
                } else {
                    $act_sheet->setCellValue($key++ . (2 + $body_count), $row[$j]);
                }
            }
            $act_sheet->getRowDimension($row_num)->setRowHeight('18');
            $row_num++;
            $body_count++;
        }
    } else {
        $body_count = count($body);
        for ($i = 0; $i < $body_count + 1; $i++) {
            $key = $column_key_start;
            for ($j = 0; $j < $column_key_count; $j++) {
                if ((is_int($body[$i][$j]) && $body[$i][$j] > 999999999999) ||
                    (!empty($body[$i][$j]) && strpos($body[$i][$j],'=') === 0) ||
                    (strlen($body[$i][$j]) > 12 && (strpos($body[$i][$j], '.') == false))
                ) {
                    $act_sheet->setCellValueExplicit($key++ . (2 + $i), $body[$i][$j]);
                } else {
                    $act_sheet->setCellValue($key++ . (2 + $i), $body[$i][$j]);
                }
            }
            $act_sheet->getRowDimension($row_num)->setRowHeight('18');
            $row_num++;
        }
    }
    // 导出footer
    if ($footer) {
        $key = $column_key_start;
        for ($i = 0; $i < $column_key_count; $i++) {
            if ((is_int($footer[$i]) && $footer[$i] > 999999999999) ||
                (!empty($footer[$i]) && strpos($footer[$i],'=') === 0) ||
                (strlen($footer[$i]) > 12 && (strpos($footer[$i], '.') == false))
            ) {
                $act_sheet->setCellValueExplicit($key++ . (2 + $body_count), $footer[$i]);
            } else {
                $act_sheet->setCellValue($key++ . (2 + $body_count), $footer[$i]);
            }
        }
        $objPHPExcel->getActiveSheet()->getStyle(
            $column_key_start . (2 + $body_count) . ':' . $max_column_key . (2 + $body_count)
        )->applyFromArray($head_style);
    } else {
        $row_num--;
    }

    // 设置数据格式
    $objPHPExcel->getActiveSheet()->getStyle('A2:' . $max_column_key . $row_num)
        ->applyFromArray($body_style);

    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
}

/**
 * 是否需要对更新的db表进行扩展更新相关的es
 * @param null $need
 * @remark 有些处理不需要更新关联的es，例如：运单审核时，不需要更新SettleRecord
 *
 * @return bool
 */
function needExtendPkGroup($need=null) {
    static $data = true;
    if ($need !== null) {
        $data = $need;
    }
    return $data;
}

/** for retriever sync
 * @param string $table
 * @param $ids
 * @param string $sql
 * @param boolean $clear 读id后是否清空
 * @return array
 */
function pk_collector($table = '', $ids = [], $sql = '', $clear = true)
{
    static $data = [];
    if (is_string($table) and $table) {
        if (is_int($ids) or is_string($ids)) {
            $ids = [(int)$ids];
        }
        if (is_array($ids)) {
            $ids = array_keys(array_flip(array_filter(array_map('intval', $ids))));
            if ($ids) {
                if (array_key_exists($table, $data)) {
                    $data[$table] = array_keys(array_flip((array_merge($data[$table], $ids))));
                } else {
                    $data[$table] = $ids;
                }
            }
        }
//        danger_sql_monitor($table, $ids, $sql);

        return $data;
    } else if (!$clear) {
        return $data;
    } else {
        $tmp  = $data;
        $data = [];

        return $tmp;
    }
}

/**
 * @param $url
 * @param null $params
 * @param string $method
 * @param int $max_retry
 * @return array|mixed
 */
function request($url, $params = null, $method = 'GET', $max_retry = 3)
{
    if (!function_exists('urlGetContents')) {
        function urlGetContents($url, $params, $method)
        {
            if (!in_array($method, ['GET', 'POST'])) {
                return [
                    'errno'   => 1001,
                    'message' => __FUNCTION__ . ": Unknown method '$method'",
                ];
            }
            if ($method == 'GET') {
                if (is_array($params) && count($params) > 0) {
                    if ($params === array_values($params)) {
                        return [
                            'errno'   => 1002,
                            'message' => __FUNCTION__ . ": Numerical array recieved for argument '\$params' (assoc array expected)",
                        ];
                    } else {
                        $url .= '?' . http_build_query($params);
                    }
                } elseif (!is_null($params)) {
                    return [
                        'errno'   => 1003,
                        'message' => __FUNCTION__ . ": If you're making a GET request, argument \$params must be null or assoc array.",
                    ];
                }
            }
            $ch = curl_init($url);
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_HEADER         => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_TIMEOUT        => 4,
                ]
            );
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (is_string($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                } elseif (is_array($params)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                } else {
                    return [
                        'errno'   => 1004,
                        'message' => __FUNCTION__ . ": Argument \$params should be an array of parameters or (if you want to send raw data) a string",
                    ];
                }
            }
            $t1 = microtime(true);
            $contents  = curl_exec($ch);
            $errno     = curl_errno($ch);
            $message   = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // $http_info = curl_getinfo($ch);
            curl_close($ch);
            // $http_code = $http_info['http_code'];
            $t2 = microtime(true);
            $t  = round(($t2 - $t1) * 1000);
            profile_log('cost', 'curl请求耗时', [$url => $t]);
            //profile_log('cost', 'curl请求耗时', [$url => $t, 'http_info' => $http_info]);

            if (!empty($errno) || 200 != $http_code) {
                cmm_log(
                    [
                        'request_error',
                        'cost'      => $t . 'ms',
                        'url'       => $url,
                        'params'    => $params,
                        'errno'     => $errno,
                        'message'   => $message,
                        'http_code' => $http_code,
                    ],
                    'ERROR_TRACE'
                );
            }

            if (!$errno) {
                if ($http_code >= 400) {
                    $errno = 1000 + $http_code;
                } else {
                    //dump($contents);
                    $rspns = @json_decode($contents, true);
                    if ($rspns === null && json_last_error() !== JSON_ERROR_NONE) {
                        return [
                            'errno'   => 1015,
                            //                        'message' => 'json decode error.',
                            'message' => $contents,
                        ];
                    } else {
                        return $rspns;
                    }
                }
            }

            return [
                'errno'   => $errno,
                'message' => $message,
                'data'    => $contents,
            ];
        }
    }

    $retry = 1;
    $rspns = urlGetContents($url, $params, $method);
    while ($retry < $max_retry && $rspns['errno']) {
        $retry += 1;
        $rspns = urlGetContents($url, $params, $method);
    }

    return $rspns;
}

/** web socket notify
 * @param $targets
 * @param $content
 * @param $type
 * @param $target_type
 * @return array|mixed
 */
function ws_notify($targets, $content, $type = 'publish', $target_type='pc')
{
    $TARGET_ALL = '_ALL_';

    $ws_servers = C('WSS');
    if (is_array($content)) {
        $content = encode_json($content);
    }
    $post_data = [
        'type'    => $type,
        'content' => $content,
        'to'      => null,
    ];
    if (!is_array($targets) and $targets !== $TARGET_ALL) {
        $targets = [$targets];
    }
    $result = [];
    if (is_array($targets)) {
        if ($target_type == 'app') {
            $targets = array_unique(array_diff($targets, [0]));
        } else {
            $targets = array_unique(array_diff(array_map('intval', $targets), [0]));
        }
        if ($targets) {
            // chunk by ws server index
            $targets_chunk   = [];
            $ws_server_count = count($ws_servers);
            foreach ($targets as $target) {
                if ($target_type == 'app') {
                    $uid = explode('_', $target)[1];
                    $targets_chunk[$uid % $ws_server_count][] = $target;
                } else {
                    $targets_chunk[$target % $ws_server_count][] = $target;
                }
            }
            foreach ($targets_chunk as $index => $chunk) {
                $url = $ws_servers[$index]['be'];
                if ($chunk) {
                    $target          = implode(',', $chunk);
                    $post_data['to'] = $target;
                    $sub_result      = request($url, $post_data, 'POST');
                    if ($sub_result['errno'] == 0) {
                        $result = array_merge($result, $sub_result);
                    }
                }
            }
        }
    } elseif ($targets === $TARGET_ALL) {
        foreach ($ws_servers as $ws_server) {
            $url        = $ws_server['be'];
            $sub_result = request($url, $post_data, 'POST');
            if ($sub_result['errno'] == 0) {
                $result = array_merge($result, $sub_result);
            }
        }
    }

    return $result;
}

function cmm_fastcgi_finish_request($r_sync = false, $script_execute_timeout = 30)
{
    if (!function_exists('fastcgi_finish_request')) {
        function fastcgi_finish_request()
        {
        }
    }
    if ($r_sync) {
        Message::getInstance()->addIds(pk_collector('', [], '', false));
        B('Common\Behavior\BusinessSync');
        B('Common\Behavior\RetrieverSync');
    }
//    B('Common\Behavior\updateRefreshCache');
    fastcgi_finish_request();
    set_time_limit($script_execute_timeout);
}

/**
 * @return array
 */
function array_order_by()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $fields = explode('.', $field);
            if (count($fields) > 1) {
                $tmp = $data;
                foreach ($fields as $sub_field) {
                    $tmp = array_column($tmp, $sub_field);
                }
            } else {
                $tmp = array_column($data, $field);
            }
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);

    return array_pop($args);
}

function export_file($file_name, $header = [], $body, $footer = [])
{
    $low_cache_output = function ($fp, $row_data) {
        $res = fputcsv($fp, $row_data, "\t");
        // jdump($res);
    };
//    $file_name = md5("Report{$file_name}");
//    $file_path = rtrim(C("FILE_STORE")["report_export"], '/').'/'.$file_name.'.csv';
    $file_path = $file_name.'.csv';
    $fp  = fopen($file_path,'w');
    // jdump($fp);die;
    // header
    if ($header) {
        $low_cache_output($fp, $header);
    }
    // body
    if (is_callable($body)) {
        foreach ($body() as $row_data) {
            foreach ($row_data as &$field) {
                if ((is_numeric($field) && ((substr($field, 0, 1) == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false))
                    $field = "\t" . $field;
            }
            $low_cache_output($fp, $row_data);
        }
    } else {
        foreach ($body as $row_data) {
            foreach ($row_data as &$field) {
                if ((is_numeric($field) && ((substr($field, 0, 1) == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false))
                    $field = "\t" . $field;
            }
            $low_cache_output($fp, $row_data);
        }
    }
    // footer
    if ($footer) {
        $footer_array = [];
        $low_cache_output($fp, $footer_array);
    }
//    fwrite($fp, serialize($this->_cache_info) . "\n$output");
    fclose($fp);
}

function export_csv($file_name, $header = [], $body, $footer = [], $charset = 'GBK')
{
    set_time_limit(400);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
    header('Cache-Control: max-age=0');

    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $low_cache_output = function ($fp, $row_data) {
        static $offset = 0;
        fputcsv($fp, $row_data, "\t");
        $offset++;
        if ($offset >= 100) {
            $offset = 0;
            flush();
            ob_flush();
        }
    };
    $fp = fopen('php://output', 'w');
    // header
    if ($header) {
        foreach ($header as $k => $v) {
            $header[$k] = iconv('UTF-8', $charset, $v);
        }
        $low_cache_output($fp, $header);
    }
    // body
    if (is_callable($body)) {
        foreach ($body() as $row_data) {
            foreach ($row_data as &$field) {
                $field = iconv('UTF-8', $charset.'//TRANSLIT', $field);
                if ((is_numeric($field) && ((substr($field, 0, 1) == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false))
                    $field = "\t" . $field;
            }
            $low_cache_output($fp, $row_data);
        }
    } else {
        foreach ($body as $row_data) {
            foreach ($row_data as &$field) {
                $field = iconv('UTF-8', $charset.'//TRANSLIT', $field);
                if ((is_numeric($field) && ((substr($field, 0, 1) == '0' && substr($field, 0, 2) !== '0.') || strlen($field) > 11))
                    || (!is_numeric($field) and strpos($field, '-') !== false and checkTime($field) === false))
                    $field = "\t" . $field;
            }
            $low_cache_output($fp, $row_data);
        }
    }
    // footer
    if ($footer) {
        $footer_array = [];
        foreach ($footer as $k => $v) {
            $footer_array[$k] = iconv('UTF-8', $charset, $v);
        }
        $low_cache_output($fp, $footer_array);
    }

    flush();
    ob_flush();
    fclose($fp);
}


function export_xls($file_name, $header = [], $body, $footer = [], $charset = 'GBK')
{
    set_time_limit(400);
//    header('Content-Type: application/octet-stream');
//    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
//    header('Cache-Control: max-age=0');
//
//    // If you're serving to IE over SSL, then the following may be needed
//    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
//    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
//    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
//    header('Pragma: public'); // HTTP/1.0

    header("Content-Encoding: none\r\n");
    $string_fields = [];
    foreach ($header as $k=>$v) {
        if ($v['type'] === 'keyword' or $v['type'] === 'text') {
            $string_fields[] = $k;
        }
    }

    $low_cache_output = function ($type, $row_data) {
        static $offset = 0;
        static $data = [];
        static $num = 0;
        if ($type === 'data') {
            $data[] = $row_data;
            $offset ++;
        }

        if ($type !== 'data' or $offset >= 100) {
            if ($type == 'header') {
                echo '{';
            }
            if ($data) {
                echo "\"$num\":".json_encode([
                        'type' => 'data',
                        'data' => $data,
                    ], JSON_UNESCAPED_UNICODE);
//                echo str_repeat(" ",1024*4);
                echo ',';
                ob_flush();
                flush();
//                sleep(3);
                $data = [];
                $offset = 0;
                $num ++;
            }
            if ($type !== 'data') {
                $row_data['type'] = $type;
                echo  "\"$num\":".json_encode($row_data, JSON_UNESCAPED_UNICODE);
                if ($type == 'footer') {
                    echo '}';
                } else {
                    echo ',';
                }
//                echo str_repeat(" ",1024*4);
                ob_flush();
                flush();
                $num ++;
//                sleep(3);
            }
        }
    };

    // header
    if ($header) {
        $header = [
            'type' => 'header',
            'datasType' => array_column($header, 'type'),
            'columnWidth' => array_column($header, 'width'),
            'data' => [array_column($header, 'title')],
        ];
        $low_cache_output('header', $header);
    }
    // body
    if (is_callable($body)) {
        foreach ($body() as $row_data) {
            foreach ($string_fields as $string_field) {
                $row_data[$string_field] = (string)$row_data[$string_field];
            }
            $low_cache_output('data', array_values($row_data));
        }
    } else {
        foreach ($body as $row_data) {
            foreach ($string_fields as $string_field) {
                $row_data[$string_field] = (string)$row_data[$string_field];
            }
            $low_cache_output('data', array_values($row_data));
        }
    }
    // footer
    if ($footer) {
        $footer_array = [];
        foreach ($footer as $k => $v) {
            $footer_array[$k] = $v;
        }
        $low_cache_output('footer', ['data' => [array_values($footer_array)]]);
    }

    flush();
    ob_flush();
}


function checkTime($string) {
    if (
        date('Y-m-d H:i:s', strtotime($string)) === $string
        or date('Y-m-d', strtotime($string)) === $string
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * 用上下文信息替换记录信息中的占位符
 */
function interpolate($message, array $context = [])
{
    // 构建一个花括号包含的键名的替换数组
    $replace = [];

    $air_value_replace  = LOG_TEMPLATE::AIR_VALUE_REPLACE;

    foreach ($context as $key => $val) {
        if (!$val and array_key_exists($key, $air_value_replace)) {
            $val    = $air_value_replace[ $key ];
        }

        $replace['{' . $key . '}'] = $val;
    }

    // 替换记录信息中的占位符，最后返回修改后的记录信息。
    return strtr($message, $replace);
}

function jdump($var, $name=null)
{
    header('Content-Type: application/json; charset=utf-8');
    if(is_scalar($name)){
        $var = [$name=>$var];
    }
    echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function jdd($var, $name=null)
{
    header('Content-Type: application/json; charset=utf-8');
    if(is_scalar($name)){
        $var = [$name=>$var];
    }
    echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    die;
}

function cmm_exit($content = '', $tag = true)
{
    if ($tag) {
        tag('app_end');
    }

    exit($content);
}

function send_to_mq($topic, $data, $key = null, $partition = null)
{
    return Utils\MQ::send($topic, $data, $key, $partition);
}

function email($title, $content, $options)
{
    $autoCompleteDomain = function ($user_list) {
        if (is_string($user_list)) {
            $user_list = explode(',', $user_list);
        }
        foreach ($user_list as &$user) {
            if (strpos($user, '@') === false) {
                $user .= '@chemanman.com';
            }
        }

        return implode(',', $user_list);
    };
    $server             = array_key_exists('server', $options) ? $options['server'] : 'smtp.exmail.qq.com';
    $port               = array_key_exists('port', $options) ? $options['port'] : 25;
    $username           = array_key_exists('username', $options) ? $options['username'] : '';
    $password           = array_key_exists('password', $options) ? $options['password'] : '';
    $content_type       = array_key_exists('content_type', $options) ? $options['content_type'] : 'TEXT';
    $to                 = array_key_exists('to', $options) ? $autoCompleteDomain($options['to']) : '';
    $cc                 = array_key_exists('cc', $options) ? $autoCompleteDomain($options['cc']) : '';
    $bcc                = array_key_exists('bcc', $options) ? $autoCompleteDomain($options['bcc']) : '';
    $subject            = $title;
    $smtp               = new \Home\Model\Email($server, $port, true, $username, $password);
    if ($to and $smtp->send($to, $username, $subject, $content, $content_type, $cc, $bcc)) {
        return ERRNO::SUCCESS;
    } else {
        return ERRNO::EMAIL_SEND_FAILED;
    }
}

function danger_sql_monitor($table, array $ids, $sql)
{
    $threshold = C('MONITOR')['sql_monitor_threshold'];
    if ($ids) {
        $row_count = count($ids);
        $content = [
            '影响行数：' . $row_count,
            '影响表：' . $table,
            //            '影响行id列表：' . implode(',', $ids),
            'SQL语句：' . $sql,
        ];
        if ($threshold['log'] <= $row_count) {
            cmm_log($content, 'danger_sql_monitor');
        }
        if ($threshold['email'] <= $row_count) {
            $content = '<pre style="word-break:break-all;word-wrap:break-word;white-space:pre-line">' .
                implode(PHP_EOL, $content) . '</pre>';
            email('高风险SQL报警@' . gethostname(), $content, C('email')['SLOW_SQL']);
        }
    }
}

/**
 * @param int    $errno
 * @param string $errstr
 * @param string $errfile
 * @param int    $errline
 *
 * @return array
 */
function notice_log($errno = 0, $errstr = '', $errfile = '', $errline = 0)
{
    static $logs = [];
    if (empty($errno)) {
        $tmp  = $logs;
        $logs = [];

        return $tmp;
    } else {
        // 过滤掉模版的
        if (preg_match('/\/tmp\//', $errfile) > 0) {
            return $logs;
        }

        $key = $errfile . '_' . $errline;
        if (!isset($logs[$key])) {
            $logs[$key] = [
                'errno'      => $errno,
                'errfile'    => $errfile,
                'errline'    => $errline,
                'errstr'     => $errstr,
            ];
        }
        return $logs;
    }
}

/**
 * 向PROFILE_LOG里添加日志
 * @param string $category
 * @param string $fun
 * @param string $log
 */
function profile_log($category='', $fun='', $log='') {
    static $logs = [];
    if (empty($category) && empty($fun) && empty($log)) {
        $ret = $logs;
        $logs = [];
        return $ret;
    }
    if (!isset($logs[$category])) $logs[$category] = [];
    if (!isset($logs[$category][$fun])) $logs[$category][$fun] = [];
    $logs[$category][$fun][] = $log;

    return $logs;
}

/**
 * @param        $message
 * @param string $level
 * @param bool   $pretty
 */
function cmm_log($message, $level = 'debug', $pretty = false)
{
    $memory_limit = ini_get('memory_limit');
    ini_set('memory_limit', ((int)$memory_limit + 10) . 'M');
    $level = strtoupper($level);
    $json_fmt = JSON_UNESCAPED_UNICODE;
    if ($pretty) {
        $json_fmt |= JSON_PRETTY_PRINT;
    }
    if ($level === 'ERROR_TRACE') {
        $message = [
            'error' => $message,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ];
    }
    if (is_array($message)) {
        $message = json_encode($message, $json_fmt);
    }
    $log_content = [
        round(microtime(true) - I('server.REQUEST_TIME_FLOAT'), 4),
        'msg=' . $message,
    ];
    Think\Log::write(implode(' ', $log_content), $level);
    if ($level === 'ERROR_TRACE' and C('MONITOR')['turn_on']) {
        C('MONITOR.turn_on', false);

        /** @var \Monitor\Model\MonitorModel $m_monitor */
        $m_monitor = D('Monitor/Monitor', 'Model');
        $m_monitor->addErrorTrace($message);

        C('MONITOR.turn_on', true);
    }
    ini_set('memory_limit', $memory_limit);
}

function encode_json($data, $pretty = false)
{
    if ($pretty) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

function decode_json($json_string)
{
    $result = @json_decode($json_string, true);
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        return false;
    } else {
        return $result;
    }
}

function client_ip($forwarded=true){
    $remote_ip  = I('server.REMOTE_ADDR');
    $forward_ip = I('server.HTTP_X_FORWARDED_FOR');
    if ($forward_ip && $forwarded) {
        $forward_ip = explode(',', str_replace(' ', '', $forward_ip));
        return $forward_ip[0];
    } else {
        return $remote_ip;
    }
}

function is_inner_ip($ip = null)
{
    /**
     * 私有IP：A类  10.0.0.0-10.255.255.255
     * B类  172.16.0.0-172.31.255.255
     * C类  192.168.0.0-192.168.255.255
     * 还有127这个网段是环回地址
     **/
    static $result = [];
    if ($ip === null) {
        $ip = client_ip();
    }
    if (!array_key_exists($ip, $result)) {
        if (APOLLO_ENV_STAGE == 'prod'){
            if (\Common\Service\CSASToolsService::isSasEnv()){
                $env_bms_ip = \Common\Cnsts\SAS_ENV::ENV_BMS_IP;
                $is_bms_ip = (isset($env_bms_ip[APOLLO_ENV_NAME][$ip]) && $env_bms_ip[APOLLO_ENV_NAME][$ip] == 1);
                if ($is_bms_ip){
                    $result[$ip] = true;
                } else {
                    $result[$ip] = false;
                }
            } else {
                $fields = explode('.', $ip);
                if ($fields[0] == 10) {
                    $result[$ip] = true;
                } elseif ($fields[0] == 172) {
                    if ($fields[1] >= 16 and $fields[1] <= 31) {
                        $result[$ip] = true;
                    } else {
                        $result[$ip] = false;
                    }
                } elseif ($fields[0] == 192 and $fields[1] == 168) {
                    $result[$ip] = true;
                } else {
                    $result[$ip] = false;
                }
            }
        } elseif (APOLLO_ENV_STAGE == 'gamma') {
            if ($ip == '182.92.131.153') { // gamma1000主机IP地址
                $result[$ip] = true;
            }
        }
    }

    return $result[$ip];
}


/**
 * 列表枚举格式转为数组格式
 * !!模板引擎使用!!!
 * ['ordernum'=>['display'=>'运单号',xx...] 转换为 ['key'=>'ordernum', 'name'=>'运单号']]
 * @param[in] array 需要转换的数据
 * @param[in] key 需要转换为的key
 * @param[in] name 需要转换为的name
 * return
 *       array 转换后的数组
 * */
function enum_to_dict($array, $key="key", $name="name", $display='display', $getallenumkeys=false){
    $key = empty($key)? "key":$key;
    $name = empty($name)? "name":$name;

    if(!is_array($array)) return $array;
    $is_enum = false;
    foreach($array as $k=>$v){
        if(is_array($v)
            && (!array_key_exists($key, $v) || !array_key_exists($name, $v) || $getallenumkeys
                || array_values($array) !== $array)
        ){
//            && array_key_exists($display, $v) ){
            $is_enum = true;
        }
        break;
    }
    if($is_enum){
        $dict = [];
        foreach($array as $k=>$v){
            if($getallenumkeys){
                $t = $v;
            }else{
                $name_val = isset($v[$display]) ? $v[$display] : (empty($v['display']) ? '' : $v['display']);
                $t = [
                    $key    => $k,
                    $name   => $name_val
                ];
            }
            if(isset($v['sug'])){
                $t['sug'] = $v['sug'];
            }
            $dict[] = $t;
        }
        $array = $dict;
    }
    return $array;
}
function enum_to_Arr($array){
    if(!is_array($array)) return $array;
    $dict = [];
    foreach($array as $k=>$v){
        $v['key'] = $k;
        $v['name'] = $v['display'];
        $dict[] = $v;
    }
    return $dict;
}

/**
 * 字典/枚举格式转为数组格式
 * !!模板引擎使用!!!
 * ['ordernum'=>'运单号] 转换为 [['key'=>'ordernum', 'name'=>'运单号']]
 * @param[in] array 需要转换的数据
 * @param[in] key 需要转换为的key
 * @param[in] name 需要转换为的name
 * return
 *       array 转换后的数组
 * */
function dict_to_array($array, $key="key", $name="name", $getallenumkeys, $display='display'){
    $key = empty($key)? "key":$key;
    $name = empty($name)? "name":$name;

    if(!is_array($array)) return $array;
    $is_dict = true;
    foreach($array as $k=>$v){
        if(!is_array($v)){
            $is_dict = false;
        }
        break;
    }
    if(!$is_dict){
        $dict = [];
        foreach($array as $k=>$v){
            $dict[] = [
                $key    => $k,
                $name   => $v
            ];
        }
        $array = $dict;
    }else{
        $array = enum_to_dict($array, $key, $name, $display, $getallenumkeys);
    }
    return $array;
}
/**
 * 字符串格式转为数组格式
 * !!模板引擎使用!!!
 * "onClick,onChange"转换为 ["onClick","onChange"]
 * @param[in] str 需要转换的字符串
 * return
 *       array 转换后的数组
 * */
function explode_graceful($string, $delimiter=","){
    if(empty($delimiter)) $delimiter = ",";
    if(empty($string) || !is_string($string)) return $string;

    $arr = explode($delimiter, $string);
    if(is_array($arr)){
        return $arr;
    }
    return [];
}
/**
 * 根据keys过滤array数据
 * !!模板引擎使用!!!
 * keys 可为 "key" or [key]  or [key,...] or [["key"=>k, "name"=> ...],...]
 * @param[in] array 数据
 * @param[in] keys 需要过滤的keys
 * @param[in] keyfiled 字段名
 * return
 *        array 过滤后的array
 * */
function array_filter_by_keys($array, $keys, $key="key",$name="name", $forceshow="false"){


    $key = empty($key)? "key":$key;
    $name = empty($name)? "name":$name;
    $array = dict_to_array($array, $key, $name, false, $name);

    if (empty($array) && $forceshow !== 'true') {
        return $keys;
    }

    //['key'=>'value']  trans to [['key'=>'value']]
    if(is_array($keys) && array_key_exists($key, $keys)){
        $keys = array($keys);
    }

    // get all keys
    if(is_array($keys) ){
        // [['key'=>'value']] trans to [key, key]
        $_tmp = array_column($keys,$key);
        if(!empty($_tmp)) $keys = $_tmp;
    }
    else{
        // key trans to [key]
        $keys = array($keys);
    }
    $keys = array_flip($keys);

    $new_array = [];
    foreach($array as $k=>$v){
        if(array_key_exists($v[$key], $keys)){
            $new_array[] = $v;
        }
    }
    if ($forceshow === 'true' && empty($new_array)) {
        $show_name_keys = array_keys($keys);
        if(!empty($show_name_keys) && $show_name_keys[0] !== '') {
            $new_array[] = [];
            $new_array[0][$key] = $show_name_keys[0];
            $new_array[0][$name] = $show_name_keys[0];
        }
    }
    return $new_array;
}



/**
 * 处理模板引擎输出的变量
 * !!模板引擎使用!!!
 * 非字符串变量不处理
 * 字符串变量,引号"转为\"
 * @param[in] str 数据
 * return
 *        str 过滤后的数据
 * 参考 json.org && php-5.6.29/ext/json/json.c
 * */
function json_escape($str)
{
    if(is_bool($str)){
        if(true === $str) return "true";
        return "false";
    }
    if(!is_string($str)) return $str;

    $res = "";
    $length = strlen($str);

    for ($i = 0; $i <= $length; $i++) {

        $char = substr($str, $i, 1);

        if($char == "\""){
            $char = "\\\"";
        }
        else if($char == "\/"){
            $char = "\\/";
        }
        else if($char == "\\"){
            $char = "\\\\";
        }
        else if($char == "\n"){
            $char = "\\n";
        }
        else if($char == "\r"){
            $char = "\\r";
        }
        else if($char == "\t"){
            $char = "\\t";
        }
        else if($char == "\x08"){
            $char = "\\f";
        }
        else if($char == "\x0c"){
            $char = "\\b";
        }
        else if(ord($char) >0 && ord($char) < 32 ){
            //处理控制字符
            $digit = ord($char);
            $char = "\\u";
            $char .= ($digit & 0xf000) >> 12;
            $char .= ($digit & 0xf00) >> 8;
            $char .= ($digit & 0xf0) >> 4;
            $char .= ($digit & 0xf);
        }


        $res .= $char;


    }
    return $res;
}

/**
 * 0值转为空字符串
 * !!模板引擎使用!!!
 * 0/null/unset 转换为 ''
 * @param[in] var 需要转换值
 * return 原值 或 空字符串
 * */
function zero2null($val){
    if($val == 0 || $val == null || !isset($val)) return '';
    return $val;
}

/**
 * @param $header array [
{
"range_name" => {
"title" =>  "网点"
"type" =>  "text"
"display" =>  "show"
"export_format" => [
]
"summable" => false
}
}
]
 * @param $enum  array 枚举
 * @param $data   array 数据
 * @return array 返回导出header、data、footer
 */
function getExportData($header, $enum, $data) {
    $exp_header = [];
    $exp_data = [];
    $footer = [];

    foreach($header as $key=>$info) {
        if ((!isset($info['display'])) || ($info['display'] == 'show')) {
            $exp_header[$key] = $info['title'];
        }
    }
    foreach($data as $value) {
        $exp_item = [];
        foreach($exp_header as $key=>$info) {
            if (!isset($value[$key])) {
                $exp_item[$key] = '';
                if (!isset($footer[$key])) {
                    $footer[$key] = '';
                }
                continue;
            }

            if (isset($enum[$key]) && isset($enum[$key][$value[$key]])) {
                $value[$key] = $enum[$key][$value[$key]];
            }
            if (isset($header[$key]['summable']) && $header[$key]['summable']) {
                if (!isset($footer[$key])) {
                    $footer[$key] = 0;
                }
                $footer[$key] += $value[$key];
            } else {
                if (!isset($footer[$key])) {
                    $footer[$key] = '';
                }
            }

            if (isset($header[$key]['export_format']) && $header[$key]['export_format']) {
                foreach($header[$key]['export_format'] as $format_call) {
                    if (is_callable($format_call)) {
                        $value[$key] = $format_call($value[$key]);
                    }
                }
            }

            $exp_item[$key] = $value[$key];
        }
        $exp_data[] = array_values($exp_item);
    }

    return [array_values($exp_header), $exp_data, array_values($footer)];
}


/**
 * @param $data [{"name":'sheet1', 'is_default'=>1, 'data'=>[header, data, footer]}]
 */
function expMultiExcel($file_name, $exp_data) {
    // 初始化excel信息
    $row_num          = 1;
    $max_column_key   = 'A';
    $column_key_start = 'A';
    vendor("PHPExcel");
    $objPHPExcel = new \PHPExcel();

    $default_sheet_index = 0;
    foreach($exp_data as $index=>$info) {
        if ($index > 0) {
            $objPHPExcel->createSheet($index);
        }
        $sheet = $objPHPExcel->getSheet($index);
        if (isset($info['name'])) {
            $sheet->setTitle($info['name']);
        }
        if (isset($info['is_default']) && $info['is_default']) {
            $default_sheet_index = $index;
        }

        $header = $info['data'][0];
        $body = $info['data'][1];
        $footer = $info['data'][2];

        $column_key_count = count($header);
        // 表头格式
        $head_style = [
            'borders'   => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            ],
            'font'      => [
                'bold' => true,
                'size' => 16,
            ],
        ];
        // body格式
        $body_style = [
            'borders'   => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'wrap' => true,
            ],
            'font'      => [
                'size' => 16,
            ],
        ];
        // 单元格格式长度，12表示4个汉字,以此为标准
        $standard = 12;
        // <12 长度校验
        $adjust_s = 1;
        // >=12 长度校验
        $adjust_l = 2;
        // 同时设置单元格格式
        $key = $column_key_start;
        for ($i = 0; $i < $column_key_count; $i++) {
            // 设置单元格长度
            $l = strlen($header[$i]);
            if ($l >= $standard) {
                $s = $l - $adjust_l;
            } else {
                $s = $l - $adjust_s;
            }
            $sheet->getColumnDimension($key)->setWidth($s);
            $sheet->getRowDimension('1')->setRowHeight('20');
            // content
            $max_column_key = $key;
            $sheet->setCellValue($key++ . '1', $header[$i]);
        }
        $row_num++;
        $sheet->getStyle('A1:' . $max_column_key . '1')
            ->applyFromArray($head_style);
        // 导出body
        $body_count = count($body);
        for ($i = 0; $i < $body_count; $i++) {
            $key = $column_key_start;
            for ($j = 0; $j < $column_key_count; $j++) {
                if ((is_int($body[$i][$j]) && $body[$i][$j] > 999999999999) ||
                    (!empty($body[$i][$j]) && strpos($body[$i][$j],'=') === 0) ||
                    (strlen($body[$i][$j]) > 12 && (strpos($body[$i][$j], '.') == false))
                ) {
                    $sheet->setCellValueExplicit($key++ . (2 + $i), $body[$i][$j]);
                } else {
                    $sheet->setCellValue($key++ . (2 + $i), $body[$i][$j]);
                }
            }
            $row_num++;
        }

        // 导出footer
        if ($footer) {
            $key = $column_key_start;
            for ($i = 0; $i < $column_key_count; $i++) {
                if ((is_int($footer[$i]) && $footer[$i] > 999999999999) ||
                    (!empty($footer[$i]) && strpos($footer[$i],'=') === 0) ||
                    (strlen($footer[$i]) > 12 && (strpos($footer[$i], '.') == false))
                ) {
                    $sheet->setCellValueExplicit($key++ . (2 + $body_count), $footer[$i]);
                } else {
                    $sheet->setCellValue($key++ . (2 + $body_count), $footer[$i]);
                }
            }
            $sheet->getStyle(
                $column_key_start . (2 + $body_count) . ':' . $max_column_key . (2 + $body_count)
            )->applyFromArray($head_style);
        } else {
            $row_num--;
        }

        // 设置数据格式
        $sheet->getStyle('A1:' . $max_column_key . $row_num)
            ->applyFromArray($body_style);
    }

    $objPHPExcel->setActiveSheetIndex($default_sheet_index);
    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');
    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');


}

/**
 * 批量上传图片
 *
 * @param array $files 上传的图片
 * @param string $type 图片应用类型 根据type会读配置文件转换成图片保存的根目录
 * @param string $sub_path  图片保存的子目录
 * @param  array $image_info 包括name和path
 * return ERRNO
 */
function uploadImages($files, $type, $sub_path, &$image_info) {
    //$files = $_FILES;
    $upload = new \Think\Upload(); // 实例化上传类
    $upload->maxSize  = 2 * 1024 * 1024; // 设置附件总上传大小, 对于多文件上传来说，是所有附件之和 2M
    $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
    $upload->rootPath = C('IMAGE_PATH')[$type]; // 设置附件上传根目录
    $upload->autoSub  = true; //开启子目录保存
    $upload->replace = true;

    foreach ($files as $key => $value) {
        foreach ($value['name'] as $k => $v) {
            $suffix = explode('/', $value['type'][$k])[1];
            $timestamp = explode('/', $value['tmp_name'][$k])[2];

            $newfile = [
                //'name' => $value['name'][$k].'.jpg',[]
                'name' => $key. '_'. $timestamp. '.'. $suffix,
                'type' => $value['type'][$k],
                'tmp_name' => $value['tmp_name'][$k],
                'error' => $value['error'][$k],
                'size' => $value['size'][$k],
            ];
            //$upload->saveName = 'safsjf'; // 上传文件名

            $upload->saveName = $key. '_'. $timestamp; // 上传文件名
            $file_ext = explode('/', $newfile['type']);
            $upload->saveExt = isset($file_ext[1]) ? $file_ext[1] : '';

            $upload->subName = "".$sub_path;

            $info_raw = $upload->uploadOne($newfile);
            $err_msg = $upload->getError();
            !empty($err_msg) && cmm_log(['upload_img_error' => $err_msg, func_get_args()]);
            if ($info_raw) {
                $image_info[$key][] = [
                    'name' => $info_raw['savename'],
                    'path' => $sub_path,
                    'type' => $type,
                ];
            }
        }



    }

    //TO DO 图片上传失败检查
    return ERRNO::SUCCESS;

}

/**
 * 对比db中已存的图片和新上传的图片 返回最终图片集合
 *
 * @param $img_names
 * 图片名称全集
 *  {
 *      "license_img": [
 *          "license_img_phpZ1m4ah.jpeg",
 *          1499936218000
 *      ],
 *      "oper_img": [
 *          "oper_img_phpFxSSNI.jpeg"
 *      ],
 *      "tr_img": []
 *  }
 * @param $db_imgs
 * 数据库查出的一条数据 应包含图片相关的字段
 * @param $new_imgs
 * 本次新上传的图片
 *  {
 *      "license_img": [
 *          {
 *              "name": "license_img_phpxTCVcA.jpeg",
 *              "path": "732",
 *              "type": "truck"
 *          }
 *      ]
 *  }
 */
function mergeImages($img_names, $db_imgs, $new_imgs) {
    $result = [];
    // 只留和图片相关的字段
    $db_imgs = array_intersect_key($db_imgs, $img_names);
    // 将图片名称作为key
    foreach ($db_imgs as $k => $v) {
        $v = decode_json($v);
        $db_imgs[$k] = empty($v) ? [] : array_column($v, null, 'name');
    }
    foreach ($img_names as $field => $field_value) {
        $result[$field] = [];
        foreach ($field_value as $key => $name) {
            if (array_key_exists($name, $db_imgs[$field])) {
                $result[$field][] = $db_imgs[$field][$name];
            } else {
                $new_value = array_shift($new_imgs[$field]);
                if ($new_value){
                    $result[$field][] = $new_value;
                }else{
                    cmm_log(['new_img_error'=>$new_imgs, $name, $field, $db_imgs, $img_names]);
                }
            }
        }
    }
    foreach ($result as $k => &$v) {
        $v = json_encode($v, JSON_UNESCAPED_UNICODE);
    }

    return $result;
}

/**
 * 批量上传图片
 *
 * @param $root_path string 图片保存的根目录
 * @param $sub_path string 图片保存的子目录
 * @param  array $image_info 包括name和path
 * return ERRNO
 */
function uploadImage($root_path, $sub_path, &$image_info, $key) {
    $files = $_FILES;
    $upload = new \Think\Upload(); // 实例化上传类
    $upload->maxSize  = 3 * 500 * 1024; // 设置附件总上传大小, 对于多文件上传来说，是所有附件之和 3 * 500K
    $upload->exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
    $upload->rootPath = $root_path; // 设置附件上传根目录
    $upload->autoSub  = true; // 开启子目录保存
    $upload->replace = true;

    $upload->saveName = $key . '_'. (microtime(true)*10000); // 上传文件名
    $file_ext = explode('/', $files['file']['type']);
    $upload->saveExt = isset($file_ext[1]) ? $file_ext[1] : '';

    $upload->subName = "".$sub_path;

    // 如果配置不为空,重写部分校验条件
    if (!empty(\Common\Cnsts\UPLOAD_CONFIG::UPLOAD_IMG_TYPE[$key])) {
        $config = \Common\Cnsts\UPLOAD_CONFIG::UPLOAD_IMG_TYPE[$key];
        if (!empty($config['img_type'])) $upload->exts = $config['img_type'];
        if (!empty($config['px'])) $upload->px = $config['px'];
        if (!empty($config['width_height'])) $upload->widthHeight = $config['width_height'];
        if (!empty($config['max_size'])) $upload->maxSize = $config['max_size'];
    }

    $info_raw = $upload->uploadOne($files['file']);
    $err_msg = $upload->getError();

    $full_path = $root_path . "/" . $sub_path. "/" . $info_raw['savename'];

    if ($info_raw) {
        $image_info = [
            'name' => $info_raw['savename'],
            'path' => $sub_path,
            'type' => $key,
        ];
        $errno = ERRNO::SUCCESS;
    }else{
        $errno = ERRNO::UPLOAD_IMAGE_FAIL;
    }

    //TO DO 图片上传失败检查
    return [$errno, $full_path,$err_msg];

}

/**
 * 获取图片
 *
 * @param $type string 图片应用的业务类型 如车辆 签收 电子回单
 * @param $sub_path string 图片保存的子目录
 * @param $name string  图片的名称
 * @param $image_info array 包括file_type和full_path
 * @return int $image_info
 */
function getImage($type, $sub_path, $name, &$image_info)
{
    $root_path = C('IMAGE_PATH')[$type];
    $full_path = $root_path . "/" . $sub_path . "/" . $name;

    $full_path = realpath($full_path);

    if ($full_path === false) {
        return ERRNO::FILE_NOT_EXISTS;
    }

    $base_dir = dirname(dirname($full_path));
    $exp_base_dir = realpath($root_path);

    if (!file_exists($full_path)) {
        return ERRNO::IMAGE_NOT_EXISTS;
    }

    if ($base_dir != $exp_base_dir) {
        return ERRNO::PATH_NOT_EXISTS;
    }

    $image = getimagesize($full_path);
    if ($image === false) {
        return ERRNO::FILE_TYPE_NOT_SUPPORT;
    }
    $mime = image_type_to_mime_type($image[2]);

    $file_type = substr(strstr($mime, '/'), 1);

    $image_info = [
        'file_type' => $file_type,
        'full_path' => $full_path
    ];

    return ERRNO::SUCCESS;
}

function formatUserData($data, $k='id', $v='name', $key_func='intval',$suffix='')
{
    $return_val = [];
    foreach ($data as $key => $value) {
        $curr_key = call_user_func($key_func, $value[$k]);
        if(!empty($suffix)){
            $curr_key = $curr_key.$suffix;
        }
        $return_val[$curr_key] = [
            'first_py' => '',
            'all_py' => '',
            'display' => $value[$v],
        ];
    }
    return $return_val;
}

function getTradeRecordSuffix($category,$tab)
{
    $suffix='';
    if($category=='TradeRecord' || $category=='TradeBalance')
    {
        $suffix_type = [
            TRADE_RECORD::TAB_VEHICLE=>BALANCE::VEHICLE_USER,
            TRADE_RECORD::TAB_EMPLOYEE=>BALANCE::EMPLOYEE_USER,
            TRADE_RECORD::TAB_DRIVER=>BALANCE::DRIVER_USER,
            TRADE_RECORD::TAB_CONSIGNOR=>BALANCE::CONSIGNOR_USER,

            TRADE_RECORD::TAB_VEHICLE_PRINT=>BALANCE::VEHICLE_USER,
            TRADE_RECORD::TAB_EMPLOYEE_PRINT=>BALANCE::EMPLOYEE_USER,
            TRADE_RECORD::TAB_DRIVER_PRINT=>BALANCE::DRIVER_USER,
            TRADE_RECORD::TAB_CONSIGNOR_PRINT=>BALANCE::CONSIGNOR_USER,

            'vehicle_account_book_manage' => BALANCE::VEHICLE_USER,
            'employee_account_book_manage' => BALANCE::EMPLOYEE_USER,
            'driver_account_book_manage' => BALANCE::DRIVER_USER,
            'consignor_account_book_manage' => BALANCE::CONSIGNOR_USER,
            'vehicle_account_book_manage_print' => BALANCE::VEHICLE_USER,
            'employee_account_book_manage_print' => BALANCE::EMPLOYEE_USER,
            'driver_account_book_manage_print' => BALANCE::DRIVER_USER,
            'consignor_account_book_manage_print' => BALANCE::CONSIGNOR_USER,
        ];
        if(isset($suffix_type[$tab])){
            $suffix = '_'.$suffix_type[$tab];
        }
    }
    return $suffix;
}

/**
 *比较两个浮点数
 *相等返回0，左边大返回1，右边大返回-1
 */
function my_bccomp($left, $right, $accur=2) {
    $left = round($left, $accur);
    $right = round($right, $accur);
    if ($left == $right) {
        return 0;
    } else if ($left > $right) {
        return 1;
    } else {
        return -1;
    }
}

/** export format for search
 * @param $value
 * @param array $methods
 * @return float
 */
function export_format($value, array $methods)
{
    $method_map = [
        Table\Cnsts\TABLE::FORMAT_MULTIPLES     => function ($value, $arg) {
            if (is_array($value)) {
                foreach ($value as &$v) {
                    $v = $v * $arg;
                }
            } else {
                $value = $value * $arg;
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_DIVISION      => function ($value, $arg) {
            if (is_array($value)) {
                foreach ($value as &$v) {
                    $v = $v / $arg;
                }
            } else {
                $value = $value / $arg;
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_ROUND         => function ($value, $arg) {
            if (is_array($value)) {
                foreach ($value as &$v) {
                    $v = sprintf('%.' . $arg . 'f', $v);
                }
            } else {
                $value = sprintf('%.' . $arg . 'f', $value);
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_TRIM_ZERO     => function ($value, $arg) {
            if ($arg) {
                if (is_array($value)) {
                    foreach ($value as &$v) {
                        $v = (string)(float)$v;
                    }
                } else {
                    $value = (string)(float)$value;
                }
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_ZERO2NULL     => function ($value, $arg) {
            if ($arg) {
                if (is_array($value)) {
                    foreach ($value as &$v) {
                        $v = empty($v) ? '' : $v;
                    }
                } else {
                    $value = empty($value) ? '' : $value;
                }
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_THOUSANDS_SEP => function ($value, $arg) {
            if (is_array($value)) {
                foreach ($value as &$v) {
                    $values = explode('.', (string)$v);
                    if ($values[0] === '-0') {
                        $prefix = '-';
                    } else {
                        $prefix = '';
                    }
                    $int    = number_format($values[0], 0, '', ',');
                    if (count($values) === 1) {
                        $v = $int;
                    } else {
                        $v = $prefix. $int . '.' . $values[1];
                    }
                }
            } else {
                $values = explode('.', (string)$value);
                if ($values[0] === '-0') {
                    $prefix = '-';
                } else {
                    $prefix = '';
                }
                $int    = number_format($values[0], 0, '', ',');
                if (count($values) === 1) {
                    $value = $int;
                } else {
                    $value = $prefix. $int . '.' . $values[1];
                }
            }

            return $value;
        },
        Table\Cnsts\TABLE::FORMAT_DATETIME      => function ($value, $arg) {
            return empty($value) ? '' : "\t".date($arg, strtotime($value));
        },
        Table\Cnsts\TABLE::FORMAT_JOIN          => function ($value, $arg) {
            return empty($value) ? '' : (is_array($value) ? implode($arg, $value) : $value);
        },
        Table\Cnsts\TABLE::FORMAT_UNIT_PRICE    => function ($value, $arg) {
            $arg      = $arg === 'KG' ? '千克' : '吨';
            $unit_map = [
                'per_num' => '元/件',
                'per_w'   => '元/' . $arg,
                'per_v'   => '元/方',
            ];
            $result   = [];
            foreach ($value as $v) {
                if (stripos($v['unit_p'],'E-')===false) {
                    $unit_price = $v['unit_p'];
                    if ($v['unit_p_unit'] === 'per_w' and $arg === '吨') {
                        $unit_price = $v['unit_p']*1000;
                    }
                    $result[] = $unit_price . $unit_map[$v['unit_p_unit']];
                } else {
                    $arr = explode('E-', $v['unit_p']);
                    $number = number_format($v['unit_p'], $arr[1]+strlen(trim($arr[0],'0'))-2, '.', '');
                    if ($v['unit_p_unit'] === 'per_w' and $arg === '吨') {
                        $number = $number*1000;
                    }
                    $result[] = $number . $unit_map[$v['unit_p_unit']];
                }
            }

            return implode(',', $result);
        },
        Table\Cnsts\TABLE::FORMAT_SET_UNIT    => function ($value, $arg) {
            $arg = $arg === 'KG' ? '千克' : '吨';
            $result = [];
            foreach ($value as $v) {
                if ($arg === '吨') {
                    $result[] = ($v * 1000) . '元/吨';
                } else {
                    $result[] = $v . '元/千克';
                }
            }
            return implode(',', $result);
        },
    ];

    foreach ($method_map as $name => $method) {
//        echo $name,'<br>';
        if (array_key_exists($name, $methods)) {
            $value = $method($value, $methods[$name]);
        }
    }

    return $value;
}

function cny($num, $need_add_unit=true){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2);
    //将数字转化为整数
    $num = bcmul($num, 100);
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    }
    $symbol = '';
    if ($num < 0) {
        $symbol = '负';
    }
    $num = abs($num);
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        }
        $j = $j + 3;
    }
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    if ($need_add_unit) {
        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元整";
        } else {
            return $symbol . $c . "整";
        }
    } else {
        if (empty($c)) {
            return "零";
        } else {
            return $symbol . $c;
        }
    }
}

function num_to_word($num)
{
    $chiNum = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
    $chiUni = array('','拾', '佰', '仟', '万', '亿', '十', '佰', '仟');

    $num_str = (string)abs($num);

    $count = strlen($num_str);
    $last_flag = true; //上一个 是否为0
    $zero_flag = true; //是否第一个
    $temp_num = null; //临时数字

    $chiStr = '';//拼接结果
    if ($count == 2) {//两位数
        $temp_num = $num_str[0];
        $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num].$chiUni[1];
        $temp_num = $num_str[1];
        $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
    }else if($count > 2){
        $index = 0;
        for ($i=$count-1; $i >= 0 ; $i--) {
            $temp_num = $num_str[$i];
            if ($temp_num == 0) {
                if (!$zero_flag && !$last_flag ) {
                    $chiStr = $chiNum[$temp_num]. $chiStr;
                    $last_flag = true;
                }
            }else{
                $chiStr = $chiNum[$temp_num].$chiUni[$index%9] . $chiStr;
                $zero_flag = false;
                $last_flag = false;
            }
            $index ++;
        }
    }else{
        $chiStr = $chiNum[$num_str[0]];
    }
    if ($num < 0) {
        $chiStr = '负' . $chiStr;
    }
    return $chiStr;
}

function dbgProfileStart($tag = 'start')
{
    global $time_arr;
    global $start_tag;
    $time_arr = array(array('tag' => $tag, 'timestamp' => microtime(true)));
    $start_tag = $tag;
}

function dbgProfile($tag)
{
    global $time_arr;
    $now = microtime(true);
    $last = end($time_arr);
    $time_arr[] = array('tag' => $tag, 'timestamp' => $now);
    cmm_log(['tag' => $tag, 'acc_tc' => $now - $time_arr[0]['timestamp'], 'cur_tc' => $now - $last['timestamp']]);
}

function dbgProfilePrint()
{
    global $time_arr;
    global $start_tag;
    $log = array();
    // tag acc_time cur_time-last_time
    foreach($time_arr as $index => $val) {
        $tag  = $val['tag'];
        if ($tag == $start_tag) {
            $log[] = array(
                'tag' => $tag,
                'acc_time' => 0,
                'cur_time' => 0,
            );
            continue;
        }
        $log[] = array(
            'tag' => $tag,
            'acc_time' => $val['timestamp'] - $time_arr[0]['timestamp'],
            'cur_time' => $val['timestamp'] - $time_arr[$index - 1]['timestamp'],
        );
    }
    cmm_log($log);
}

function reverseBool($res){
    return ($res === 'false' || !$res) ? true : false;
    // return !res;
}
function isRequire($value, $other = '', $key = 'require'){
    return $value ? $key.','.$other : $other;
}

function toArray($value){
    return $value ? [$value] : [];
}

function orState($value, $other){
    return $value || $other;
}

function listToDict($array, $key, $value = null, $value_type = 'auto')
{
    $result = [];
    if ($array) {
        if ($value_type === 'auto' and count(array_unique(array_column($array, $key))) < count($array)) {
            $value_type = 'list';
        }
        if ($value_type === 'list') {
            foreach ($array as $v) {
                $idx = $v[$key];
                if ($value == null) {
                    $item = $v;
                } elseif (is_array($value)) {
                    $item = [];
                    foreach ($value as $sub_value) {
                        $item[$sub_value] = $v[$sub_value];
                    }
                } else {
                    $item = $v[$value];
                }
                if (array_key_exists($idx, $result)) {
                    $result[$idx][] = $item;
                } else {
                    $result[$idx] = [$item];
                }
            }
        } else {
            $result = array_column($array, $value, $key);
        }
    }

    return $result;
}

function array_reduce_depth(array $data)
{
    if (count($data) > 1) {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = array_reduce_depth($v);
            }
        }
    }
    while (is_array($data) and $data and count($data) === 1) {
        foreach ($data as $k => $v) {
            if (is_numeric($k)) {
                $data = $v;
            } else {
                break 2;
            }
        }
    }

    return $data;
}

/**
 * 根据addr获取详细的地址信息
 * */
function addrInfo($addr,$is_type = true) {
    //// 到站为市县，调用接口
    //$url = "http://restapi.amap.com/v3/geocode/geo";
    //$params = [
    //    "key" => "e113abd45f9b22b845ed5b83169fa356",
    //    "address" => $addr,
    //];
    $url = \Common\Cnsts\GD::URL3;
    $params = [
        "key" => \Common\Cnsts\GD::KEY1,// 通过账号13811195264创建的
//	    "city" => $addr,
        "keywords" => $addr,
        "extensions" => "all",
//		"citylimit" => false,
        "types" => "190101|190102|190103|190104|190105|190106|190107",
        "page" => 1,
        "offset" => 10,
    ];
    if (!$is_type) {
        unset($params['types']);
    }
    return request($url, $params)["pois"][0];
}

function addrPoiInfo($addr)
{
    // 暂时没有购买批量接口服务
    $is_batch = false;
    $url_core = \Common\Cnsts\GD::URL4;
    $url = "http://" . $url_core;
//    $key = 'e113abd45f9b22b845ed5b83169fa356';
    $key = \Common\Cnsts\GD::KEY3;// 这个key是通过账号songbaojiang@chemanman.com创建的
    $param = array('key' => $key,'address' => '');
    if ($is_batch) {
        $batch_url = \Common\Cnsts\GD::URL5;
        $batch_param = array('ops' => []);
        foreach($addr as $s_addr) {
            $param['address'] = $s_addr;
            $param_str = http_build_query($param);
            $url = $url_core . "?" . $param_str;
            $batch_param['ops'][] = array('url' => $url);
        }
        $batch_param = json_encode($batch_param);
        $res = request($batch_url, $batch_param, 'POST');
    } else {
        $params = [
            "key"     => \Common\Cnsts\GD::KEY3,
            "address" => $addr,
        ];
        $res = request($url, $params);
    }
    return $res;
}

/**
 * 根据坐标获取地址信息
 * @param $pois
 * @return array|mixed
 * 参考API文档：https://lbs.amap.com/api/webservice/guide/api/georegeo/#regeo
 */
function poiAddrInfo($pois)
{
    $url = \Common\Cnsts\GD::URL6;
    $key = \Common\Cnsts\GD::KEY3;// 这个key是通过账号songbaojiang@chemanman.com创建的
    $params = [
        "key"     => $key,// 这个key是通过账号songbaojiang@chemanman.com创建的
        "location" => $pois,
    ];
    $res = request($url, $params);
    return $res;
}

/**
 * 根据poi计算两点间的里程.
 * @param $poi1
 * @param $poi2
 * @param $mileRatio
 * @return float
 */
function poiCalDistance($poi1, $poi2, $mileRatio) {
    // $url = 'https://restapi.amap.com/v4/direction/truck';// 受限了，收费了
    $url = \Common\Cnsts\GD::URL7;
    $params = [
        'key' => \Common\Cnsts\GD::KEY3,// 这个key是通过账号songbaojiang@chemanman.com创建的
        'origin' => $poi1,
        'destination' => $poi2,
        'extensions' => 'all',
//        'size' => 2,
//        'strategy' => 5,
//        'width' => 2.5,
//        'weight' => 10,
//        'axis' => 2,
//        'height' => 1.6,
//        'load' => 0.9,
//        'nosteps' => 1,
    ];
    $res = request($url, $params);
    if ($res['status'] == 1 && !empty($res['route']['paths'][0]['distance'])) {
        $mile = $res['route']['paths'][0]['distance'];
        return round($mile * $mileRatio / 1000, 3);
    } else {
        return 0;
    }
}

/**
 * 根据poi计算两点间的路由距离
 * @param $poi1
 * @param $poi2
 * @return float
 */
function get_mile_from_amap($poi1, $poi2) {
    $url = \Common\Cnsts\GD::URL8;
    $params = [
        // 线上key
        // 'key' => 'e113abd45f9b22b845ed5b83169fa356',
        // 备用key  正常情况下使用备用key
        'key' => \Common\Cnsts\GD::KEY2,
        'origin' => $poi1,
        'destination' => $poi2,
        'strategy' => 'LEAST_DISTANCE',
    ];
    $res = request($url, $params);
    if ($res['status'] == '1') {
        $mile = $res['route']['paths'][0]['distance'];
        return round($mile / 1000, 3);
    } else {
        return 0;
    }
}

/**
 * 根据poi计算两点间路线,截取每个关键点返回和总时长
 * @param $poi1
 * @param $poi2
 * @return float
 */
function get_amap_line_and_time($poi1, $poi2) {
    $url = \Common\Cnsts\GD::URL8;
    $params = [
        // 线上key
        // 'key' => 'e113abd45f9b22b845ed5b83169fa356',
        // 备用key  正常情况下使用备用key
        'key' => \Common\Cnsts\GD::KEY2,
        'origin' => $poi1,
        'destination' => $poi2,
        'strategy' => 'LEAST_DISTANCE',
        'extensions' =>'base',
    ];
    $res = request($url, $params);
    if ($res['status'] == '1') {
        $steps_n = [];
        $steps = $res['route']['paths'][0]['steps'];
        $duration = $res['route']['paths'][0]['duration'];
        foreach ($steps as $step){
            $steps_n[] = explode(';',$step['polyline'])[0];
        }
        return [$steps_n,$duration];
    } else {
        return [[],0];
    }
}

/**
 * 根据poi计算距离、时效
 * */
function calcDist($start_poi, $end_poi) {
    // 调用接口，获取里程和时效
    $url = \Common\Cnsts\GD::URL9;
    $params = [
        "key" => \Common\Cnsts\GD::KEY1,// 通过账号13811195264创建的
        "origins" => $start_poi,
        "destination" => $end_poi,
    ];
    return request($url, $params);
}

/**
 * 检查是否为手机号码
 * @return boolean
 */
function is_mobilephone($mobilephone)
{
    if (preg_match("/^((\(\d{2,3}\))|(\d{3}\-))?1[3,4,5,6,7,8,9]\d{9}$/", $mobilephone)) {
        return true;
    }

    return false;
}

/**
 * 检查是否为全汉字
 * @return boolean
 */
function is_all_chinese($str)
{
    return preg_match("/^[\x7f-\xff]+$/", $str);
}

/**
 * 检查是否有特殊字符
 * @return boolean
 */
function is_special_characters($str)
{
    return preg_match ( '/[\Q~!@#$%^&*()+-_=.:?<>,，\E]/', $str);
}

/**
 * 检查是否为座机号码
 * @return boolean
 */
function is_phone($phone)
{
    if (preg_match("/^\d{3,4}-\d{7,8}$/", $phone)) {
        return true;
    }

    return false;
}

/**
 * 检查是否为身份证号码
 * @return boolean
 */
function is_id_card($id_card){
    if(strlen($id_card)==18){
        return idcard_checksum18($id_card);
    }elseif((strlen($id_card)==15)){
        $id_card=idcard_15to18($id_card);
        return idcard_checksum18($id_card);
    }else{
        return false;
    }
}
// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base){
    if(strlen($idcard_base)!=17){
        return false;
    }
    //加权因子
    $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
    //校验码对应值
    $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
    $checksum=0;
    for($i=0;$i<strlen($idcard_base);$i++){
        $checksum += substr($idcard_base,$i,1) * $factor[$i];
    }
    $mod=$checksum % 11;
    $verify_number=$verify_number_list[$mod];
    return $verify_number;
}
// 将15位身份证升级到18位
function idcard_15to18($idcard){
    if(strlen($idcard)!=15){
        return false;
    }else{
        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
            $idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
        }else{
            $idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
        }
    }
    $idcard=$idcard.idcard_verify_number($idcard);
    return $idcard;
}
// 18位身份证校验码有效性检查
function idcard_checksum18($idcard){
    if(strlen($idcard)!=18){
        return false;
    }
    $idcard_base=substr($idcard,0,17);
    if(idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
        return false;
    }else{
        return true;
    }
}

function get_param($key, $default = null, $req = [])
{
    static $_req = null;
    if (is_null($_req)) {
        $_req = json_decode(html_entity_decode(I('req', '', 'htmlspecialchars')), true);
        if (empty($_req)) {
            $_req = json_decode(file_get_contents('php://input'), true);
        }
    }
    if (!is_array($req) or empty($req)) {
        $req = $_req;
    }

    return isset($req[$key]) ? $req[$key] : $default;
}

/**
 * 过滤黑词
 * @param[in] message 待处理文本
 * @param[in] need_hit_words 是否需要返回命中敏感词 默认不返回
 * return
 *		need_hit_words为true返回命中敏感词语
 *		need_hit_words为false返回处理后的消息
 * */
function black_word_filter($message, $need_hit_words = false) {
    $black_words = \Basic\Cnsts\MSG::BLACK_WORD_FILTER;
//    $black_word_file = APP_PATH.'Runtime/black_word.dat';
//    if(@file_exists($black_word_file)) {
//        if($fp = @fopen($black_word_file, 'rb')) {
//            while(!feof($fp)) {
//                $word = fgets($fp);
//                $word = preg_replace('/[\n\r]/', '', $word);
//                if(!empty($word)) {
//                    $black_words[] = $word;
//                }
//            }
//            fclose($fp);
//        }
//    }
    // 命中的敏感词
    $hit_words = [];
    foreach($black_words as $black_word) {
        if(strpos($message, $black_word) === false) {
            continue;
        }
        $word = str_replace(
            array(' ', '　', '_', '-', '.', '|', '&', '*'),
            array('', '', '', '', '', '', '', ''),
            $black_word
        );

        $char = preg_split("//u", $word, -1, PREG_SPLIT_NO_EMPTY);
        $word = implode('|', $char);
        if(strpos($message, $word) === false) {
            $hit_words []= $black_word;
            $message = str_replace($black_word, $word, $message);
            continue;
        }

        $word = implode('_', $char);
        if(strpos($message, $word) === false) {
            $hit_words []= $black_word;
            $message = str_replace($black_word, $word, $message);
            continue;
        }

        $word = implode('*', $char);
        if(strpos($message, $word) === false) {
            $hit_words []= $black_word;
            $message = str_replace($black_word, $word, $message);
            continue;
        }
        $hit_words []= $black_word;
        $message = str_replace($black_word, '*', $message);
    }
    return ($need_hit_words ? $hit_words : $message);
}

function enum_to_array($enum){
    $new_enum = [];
    foreach($enum as $enk => $env){
        $new_ev = [];
        foreach($env as $ik=>$iv){
            $iv['app_type_name'] = $ik;
            $new_ev[] = $iv;
        }
        $new_enum[$enk] = $new_ev;
        unset($new_ev);
    }
    return $new_enum;
}

function jsonDecodeAsArray($json_string)
{
    $result = @json_decode($json_string, true);
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        return false;
    } else {
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }
}

/**
 * 检查是否为手机号码
 * @return boolean
 */
function ucmm_is_mobilephone($mobilephone)
{
    if (preg_match("/^((\(\d{2,3}\))|(\d{3}\-))?1[3,4,5,7,8]\d{9}$/", $mobilephone)) {
        return true;
    }

    return false;
}

/**
 * 检查是否为座机号码
 * @return boolean
 */
function ucmm_is_telephone($telephone)
{
    if (preg_match("/(\d{3}-|\d{4}-)?(\d{8}|\d{7})?/", $telephone)) {
        // preg_match("/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/i", $telephone)
        return true;
    }
    return false;
}

/**
 * 批量生成短url
 * average cost 1000 ms for 1000 same url, 3000 ms for 1000 different url
 * @param[in] long_urls需要生成短url的数组
 * @param [int] $offset 数据截取偏移量，递归调用使用
 * @return array url数组
 *      e.g. normal: [['url_short'=>'xxx', 'url_long'=>'xxx', 'type'=>0], ...]
 *      e.g. error(url format error or query more than 20 in one time): {"request":"/short_url/shorten.json","error_code":"400","error":"40001:Error: param error, see doc for more info."}
 * @version 2.0.0
 *  - 有接口访问失败时，返回false
 * @version 1.1.0
 *  - 增加超时限制
 * @version 1.0.0
 * */
function shortUrls($long_urls, $offset=0){
    static $ch = null;
    $EACH_QUERY_NUM = 18;  // max is 20

    if (is_null($ch)) { $ch = curl_init(); }
    $url = "http://api.t.sina.com.cn/short_url/shorten.json?";
    $url .= "source=3021157329";

    $res_array = [];
    if ($offset < count($long_urls)) {
        for ($i=$offset; $i<$offset+$EACH_QUERY_NUM; $i++) {
            if (!empty($long_urls[$i])) {
                $url .= "&url_long=".urlencode($long_urls[$i]);
            }
        }
        $header = ["Content-type: text/xml"];
        // 发送url生成请求
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $res = curl_exec($ch);
        $res_array = json_decode($res, true);
        // some error happened
        // @todo record error log
        if (isset($res_array['error_code']) || is_null($res_array)) {
            $res_array = [];
            return $res_array;
        }
        $offset += $EACH_QUERY_NUM;
        $res_array = array_merge($res_array, shortUrls($long_urls, $offset));
    }
    return $res_array;
}

/**
 * 980.so短链服务
 *
 * @param [type] $long_urls
 * @return array
 * @known error
 *  - http://980.so/7f0tT(http://t800.chemanman.com/index.php?qr=901056769792)
 *    实际结果为https://mobile.xunlei.com/m/channel.html?from=ThunderLink_PCPopUp&taskDownload=thunder%3A%2F%2FQUFodHRwOi8vcHNmLWQueXN0czguY29tOjgwMDAv5YW25LuW6K%2BE5LmmL%2BeZveecieWkp%2BS%2BoF%2FnlLXop4bkuablnLrniYgt5LiK6YOoLzAyNV9ZLm1wMz8xMjY1NTQyNjMyNDd4MTUxNTIxNzE2M3gxMjcwMTY4NzYwMDEtMDU5OTU1NDM4MTA1NTQwNDM0MTk3MDRaWg%3D%3D&scheme=xunleiapp%3A%2F%2Fxunlei.com%2FsharePage%3FshareH5%3Dshare_H5
 */
function shortUrlsV2($long_urls) {
    $short_url_dict = [];
    static $ch = null;
    if (is_null($ch)) { $ch = curl_init(); }
    $url = 'http://980.so/api.php';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    foreach ($long_urls as $long_url) {
        $data = ['url' =>$long_url];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $short_url = curl_exec($ch);
        $short_url_dict[$long_url] = $short_url ?: '';
    }
    return $short_url_dict;
}

/**
 * http://dwz.chacuo.net/weibo
 *
 * @param [type] $long_urls
 * @return array
 */
function shortUrlsV4($long_urls) {
    $short_url_dict = [];
    static $ch = null;
    if (is_null($ch)) { $ch = curl_init(); }
    $url = 'http://dwz.chacuo.net/weibo';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    $data = [
        'data' => implode("\r\n", $long_urls)."\r\n",
        'type' => 'weibo',
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    if ($res) {
        $res_array = json_decode($res, true);
        if (isset($res_array['error_code']) || is_null($res_array)) {
            return [];
        }
        if (!empty($res_array['data'])) {
            $short_urls = explode("\r\n", $res_array['data'][0]);
            foreach ($long_urls as $idx=>$long_url) {
                $short_url_dict[$long_url] = $short_urls[$idx];
            }
        }
    }
    return $short_url_dict;
}

/**
 * 优先使用weibo
 *
 * @param [type] $long_urls
 * @return array "{url_long}"=>"{url_short}"
 */
function shortUrlsV3($long_urls) {
    $res = shortUrls($long_urls);
    if (false === $res) {
        //$res = shortUrlsV2($long_urls);
        $res = shortUrlsV4($long_urls);
    } else {
        $res = array_column($res, 'url_short', 'url_long');
    }
    $result = [];
    if($res){
        foreach ($long_urls as $key => $source_url){
            if(isset($res[$source_url])){
                $result[$key] = $res[$source_url];
            }
        }
    }

    return $result;
}

function cmm_curl_multi($params, $protocol = 'post')
{
    $mh = curl_multi_init();
    $ch = [];
    foreach ($params as $key => $value) {
        $value['url'] .= (strpos($value['url'], '?') === false) ? ('?rid=' . REQUEST_ID) : ('&rid=' . REQUEST_ID);
        $value['url'] .= (strpos($value['url'], '?') === false) ? ('?gid=' . GROUP_ID) : ('&gid=' . GROUP_ID);
        $ch[$key] = curl_init();
        curl_setopt($ch[$key], CURLOPT_URL, $value['url']);
        curl_setopt($ch[$key], CURLOPT_HEADER, 0);
        curl_setopt($ch[$key], CURLOPT_COOKIE, 'PHPSESSID=' . $_COOKIE['PHPSESSID']);
        curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, false);
        if ($protocol === 'post') {
            $post_data = isset($value['post_data']) ? $value['post_data'] : [];
            $post_data = (is_array($post_data)) ? http_build_query($post_data) : $post_data;
            curl_setopt($ch[$key], CURLOPT_POST, 1);
            curl_setopt($ch[$key], CURLOPT_POSTFIELDS, $post_data);
        }
        curl_multi_add_handle($mh, $ch[$key]);
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }
    foreach ($params as $key => $value) {
        curl_multi_remove_handle($mh, $ch[$key]);
        curl_close($ch[$key]);
    }
    curl_multi_close($mh);
}

function cmm_curl($params, $protocol = 'post')
{
    $url = $params['url'];
    $url .= (strpos($params['url'], '?') === false) ? ('?rid=' . $params['request_id']) : ('&rid=' . $params['request_id']);
    $url .= (strpos($params['url'], '?') === false) ? ('?gid=' . $params['group_id']) : ('&gid=' . $params['group_id']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $params['cookie']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
    if ($protocol === 'post') {
        $post_data = isset($params['post_data']) ? $params['post_data'] : [];
        $post_data = (is_array($post_data)) ? http_build_query($post_data) : $post_data;
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new \Exception(curl_error($ch), 0);
    } else {
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 !== $httpStatusCode) {
            throw new \Exception($response, $httpStatusCode);
        }
    }
    curl_close($ch);

    return jsonDecodeAsArray($response);
}

//des加密
function des_encrypt_common($data, $key = 'cmm@2017')
{
    $crypt = new \Org\Crypt\McryptDes($key);
    $encode_data = $crypt->encrypt($data, true);
    return $encode_data;
}

//des解密
function des_decrypt_common($data, $key = 'cmm@2017')
{
    $crypt       = new \Org\Crypt\McryptDes($key);
    $decode_data = $crypt->decrypt($data, true);
    return $decode_data;
}

/** 用于记录全局debug信息
 * @param $type
 * @param $content
 * @param $level
 *
 * @return array
 */
function dbg($type = null, $content = '', $level = \Common\Cnsts\DEBUG::BUSINESS_CONTROLLER, $record = true)
{
    if (!$record) {
        return [];
    }
    static $dbg = null;
    static $data = [];
    static $time = 0;
    static $memory = 0;
    if (is_null($dbg)) {
        $dbg = I('_dbg/d', 0);
    }
    if (APP_DEBUG && $dbg < \Common\Cnsts\DEBUG::BUSINESS_SERVICE) {
        $dbg = \Common\Cnsts\DEBUG::BUSINESS_SERVICE;
    }

    if ($level <= $dbg and !is_null($type)) {
        $caller = debug_backtrace()[1];
        if (\Common\Cnsts\DEBUG::BASE_SERVICE <= $dbg) {
            $content = [
                $content,
                'trace' => [
                    'method' => $caller['function'],
                    'class'  => $caller['class'] ? $caller['class'] : '',
                ],
            ];
        }
        if (\Common\Cnsts\DEBUG::TRACE <= $dbg and $type !== 'profile') {
            $content['trace']['args'] = $caller['args'];
        }
        if (\Common\Cnsts\DEBUG::PROFILE <= $dbg and $level == \Common\Cnsts\DEBUG::PROFILE) {
            $cur_time = microtime(true) - REQUEST_TIME / 1000000;
            $cur_memory = memory_get_peak_usage(true);
            $content['time'] = number_format($cur_time, 6);
            $content['memory'] = number_format($cur_memory, 0, '.', ',');
            if (!$time) {
                $content['time_delta'] = number_format(0, 6);
            } else {
                $content['time_delta'] = number_format($cur_time - $time, 6);
            }
            if (!$memory) {
                $content['memory_delta'] = number_format(0, 0, '.', ',');
            } else {
                $content['memory_delta'] = number_format($cur_memory - $memory, 0, '.', ',');
            }
            $time = $cur_time;
            $memory = $cur_memory;
        }
        if (!isset($data[$type])) {
            $data[$type] = [];
        }
        $data[$type][] = $content;
    }

    if ($dbg === \Common\Cnsts\DEBUG::PROFILE_PRINT and is_null($type)) {
        $output_fmt = "%' 12s%' 12s%' 15s%' 15s     %' -20s\n";
        echo '<pre style="font-size: 13px;line-height: 1.5;">', sprintf($output_fmt, 'time(ms)', 'delta', 'mem(byte)', 'delta', 'flag');
        foreach ($data['profile'] as $key => $value) {
            echo sprintf($output_fmt, $value['time']*1000, $value['time_delta']*1000, $value['memory'], $value['memory_delta'], $value['0']);
        }
        echo '</pre>';
    }

    return $data;
}

/**
 * 检测输入的数组是不是关联数组
 * @author DoubleZ
 * @param $arr array 数组
 * @return boolean
 */
function tpl_check_assoc_array($arr) {
    if (!is_array($arr)) {
        return false;
    } else {
        $idx = 0;
        foreach (array_keys($arr) as $key) {
            if (gettype($idx) != gettype($key) || $idx != $key) {
                return true;
            }
            $idx+=1;
        }
        return false;
    }
}

/**
 * 将关联数组转换成索引数组
 * @author DoubleZ
 * @param $arr array 数组
 * @return array
 */
function tpl_convert_assoc_array($arr, $key='key') {
    $tmp = [];
    foreach($arr as $k => $v) {
        $v[$key] = $k;
        array_push($tmp, $v);
    }
    return $tmp;
}

function is_online() {
    return in_array(APOLLO_ENV_STAGE, ['prod', 'gamma']);
}

/**
 * @return bool
 */
function anti_frequently_refresh_wl($white_list = null)
{
    if (isset($_GET['token']) && '1v0JJOMk9QZprgKVrfWVZw==' == $_GET['token']) {
        return true;
    }
    if (empty($white_list)) {
        $white_list = C('ANTI_FR_WHITE_LIST');
    }
    $current = [MODULE_NAME, CONTROLLER_NAME, ACTION_NAME];
    $match   = false;
    foreach ($white_list as $policy) {
        for ($i = 0; $i < count($policy); $i++) {
            if ($i < count($current)) {
                $match = $policy[$i] == '*' || $policy[$i] == $current[$i];
            } else {
                $match = $policy[$i] == '*' || $policy[$i];
            }
            if ($match === false) {
                break;
            }
        }
        if ($match === true) {
            break;
        }
    }

    return $match;
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    if (filter_var($ip, \FILTER_VALIDATE_IP,\FILTER_FLAG_IPV4)) {
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    } elseif (filter_var($ip, \FILTER_VALIDATE_IP,\FILTER_FLAG_IPV6)) {
        $long = ip2long6($ip);
        $ip   = $long ? array($ip, $long) : array('::', 0);
    }
    return $ip[$type];
}

function get_ip_location($ip){
    if (empty($ip)){
        return [];
    }
    $ip_location = M('ip_location')->where(['ip' => $ip])->field(['ip', 'province', 'city', 'operator'])->find();
    // 离线库没有从接口查询
    if (empty($ip_location)){
        $params = [
            'url' => 'https://api.ip138.com/ip/?ip=' . $ip . '&datatype=jsonp&token=0ab9cd42bffa012c458322d59f263707',
        ];
        $res = cmm_curl($params, 'GET');
        // 直辖市
        if (in_array($res['data'][1], ['北京', '上海', '重庆', '天津'])){
            $province = $res['data'][1] . '市';
        }elseif (in_array($res['data'][1], ['香港', '澳门'])){
            $province = $res['data'][1] . '特别行政区';
        }else{
            $province = $res['data'][1] . '省';
        }
        $city = CompatibleCity($res['data'][2]);
        $ip_data = [
            'ip' => $ip,
            'province' => $province,
            'city' => $city,
            'operator' => $res['data'][4],
            'address' => json_encode($res['data'], 256),
        ];
        M('ip_location')->add($ip_data);
        return [
            'area' => $res['data'][4],
            'ip' => $res['ip'],
            'province' => $province,
            'city' => $city,
            'country' => $province . $city
        ];
    }else{
        return [
            'area' => $ip_location['operator'],
            'ip' => $ip_location['ip'],
            'province' => $ip_location['province'],
            'city' => $ip_location['city'],
            'country' => $ip_location['province'] . $ip_location['city']
        ];
    }

}

function CompatibleCity($city){
    switch ($city){
        // 自治州
        case '延边':
            return '延边朝鲜鮮族自治州';
        case '恩施':
            return '恩施土家族苗族自治州';
        case '阿坝':
            return '阿坝藏族羌族自治州';
        case '甘孜':
            return '甘孜藏族自治州';
        case '凉山':
            return '凉山彝族自治州';
        case '黔东南':
            return '黔东南苗族侗族自治州';
        case '黔南':
            return '黔南布依族苗族自治州';
        case '黔西南':
            return '黔西南布依族苗族自治州';
        case '楚雄':
            return '楚雄彝族自治州';
        case '红河':
            return '红河哈尼族彝族自治州';
        case '文山':
            return '文山壮族苗族自治州';
        case '西双版纳傣族自治州':
            return '西双版纳';
        case '大理':
            return '大理白族自治州';
        case '德宏':
            return '德宏傣族景颜族自治州';
        case '怒江':
            return '怒江傈係族自治州';
        case '迪庆':
            return '迪庆藏族自治州';
        case '临夏':
            return '临夏回族自治州';
        case '甘南':
            return '甘南藏族自治州';
        case '海南':
            return '海南藏族自治州';
        case '海北藏族自治州':
            break;
        case '海西':
            return '海北藏族自治州';
        case '黄南':
            return '黄南藏族自治州';
        case '果洛':
            return '果洛藏族自治州';
        case '玉树':
            return '玉树藏族自治州';
        case '伊犁':
            return '伊犁哈萨克自治州';
        case '博州':
            return '博尔塔拉蒙古自治州';
        case '昌吉':
            return '昌吉回族自治州';
        case '巴州':
            return '巴音郭楞蒙古自治州';
        case '克州':
            return '克孜勒苏柯尔克孜自治州';
        //地区
        case '和田':
            return '和田地区';
        case '喀什':
            return '喀什地区';
        case '塔城':
            return '塔城地区';
        case '阿勒泰':
            return '阿勒泰地区';
        case '阿克苏':
            return '阿克苏地区';
        case '大兴安岭':
            return '大兴安岭地区';
        case '阿里':
            return '阿里地区';
        // 盟
        case '兴安':
            return '兴安盟';
        case '锡林郭勒':
            return '锡林郭勒盟';
        case '阿拉善':
            return '阿拉善盟';
        default:
            return $city . '市';
    }
}

/**
 * IPV6 地址转换为整数
 * @param $ipv6
 * @return string
 * */
function ip2long6($ip)
{
    if(($ip_n = inet_pton($ip)) === false) return false;
    $bits = 15; // 16 x 8 bit = 128bit (ipv6)
    while ($bits >= 0)
    {
        $bin = sprintf("%08b",(ord($ip_n[$bits])));
        $ipbin = $bin.$ipbin;
        $bits--;
    }
    return $ipbin;
}

/**
 * 转换arr[$key] 里面的元素转换为数组
 * */
function str_2_int_array($key, &$arr) {
    if (isset($arr[$key])) {
        if (is_array($arr[$key])) {
            $arr[$key][1] = is_array($arr[$key][1]) ?
                array_map('intval', $arr[$key][1]) : intval($arr[$key][1]);
        } else {
            if (is_string($arr[$key]))
                $arr[$key] = intval($arr[$key]);
        }
    }
}

/**
 * sug接口中过滤 ' "字符
 * */
function str_filter($search) {
    $search = str_replace("'", "", $search);
    $search = str_replace("\"", "", $search);
    return $search;
}

/**
 * @return string
 * 获取当前协议
 */
function getProtocol() {
    $protocol = is_ssl() ? "https://" :"http://";
    return $protocol;
}

function trim_cn($str, $trim, $charset = 'UTF-8') {
    $len = mb_strlen($str, $charset);
    if (!$len)
        return '';

    $t1 = $t2 = false;$o=$l=0;
    for($i=0;$i<$len;$i++)
    {
        $str1 = mb_substr($str, $i, 1, $charset);
        $str2 = mb_substr($str, $len-$i-1, 1, $charset);
        if($str1 == $trim && !$t1) $o++; else $t1 = true;
        if($str2 == $trim && !$t2) $l++; else $t2 = true;
    }
    return mb_substr($str, $o, ($len-$l-$o), $charset);
}

/**
 * 数字转换为中文
 * @param  string|integer|float  $num  目标数字
 * @param  integer $mode 模式[true:金额（默认）,false:普通数字表示]
 * @param  boolean $sim 使用小写（默认）
 * @return string
 */
function number2chinese($num,$mode = true,$sim = true){
    if(!is_numeric($num)) return '含有非数字非小数点字符！';
    $char    = $sim ? array('零','一','二','三','四','五','六','七','八','九')
        : array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖');
    $unit    = $sim ? array('','十','百','千','','万','亿','兆')
        : array('','拾','佰','仟','','萬','億','兆');
    $retval  = $mode ? '元':'点';
    //小数部分
    if(strpos($num, '.')){
        list($num,$dec) = explode('.', $num);
        $dec = strval(round($dec,2));
        if($mode){
            $retval .= "{$char[$dec['0']]}角{$char[$dec['1']]}分";
        }else{
            for($i = 0,$c = strlen($dec);$i < $c;$i++) {
                $retval .= $char[$dec[$i]];
            }
        }
    }
    $out = [];
    //整数部分
    $str = $mode ? strrev(intval($num)) : strrev($num);
    for($i = 0,$c = strlen($str);$i < $c;$i++) {
        $out[$i] = $char[$str[$i]];
        if($mode){
            $out[$i] .= $str[$i] != '0'? $unit[$i%4] : '';
            if($i>1 and $str[$i]+$str[$i-1] == 0){
                $out[$i] = '';
            }
            if($i%4 == 0){
                $out[$i] .= $unit[4+floor($i/4)];
            }
        }
    }
    $retval = join('',array_reverse($out)) . $retval;
    $retval = trim_cn($retval, '元');
    $retval = trim_cn($retval, '零');
    return $retval;
}

/** convert order query number to short string for short url
 * @param $int_value
 *
 * @return string
 */
function compressIntToString($int_value)
{
    $int_value = intval($int_value);
    $charset      = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
//    $charset      = '1234567890';
    $charset_size = strlen($charset);
    $result       = '';
    while ($int_value !== 0) {
        $result    = $charset[$int_value % $charset_size] . $result;
        $int_value = intval($int_value / $charset_size);
    }

    return $result;
}

/** convert short string from short url to int
 * @param $str_value
 *
 * @return float|int|mixed
 */
function decompressStringToInt($str_value)
{
    $charset = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
//    $charset      = '1234567890';
    $charset_size = strlen($charset);
    $charset_map  = [];
    for ($i = 0; $i < $charset_size; $i++) {
        $charset_map[$charset[$i]] = $i;
    }
    $result = 0;
    for ($i = 0; $i < strlen($str_value); $i++) {
        $result = bcadd(bcmul($result, $charset_size), $charset_map[$str_value[$i]]);
    }

    return $result;
}

// 获取主库db配置
function main_db_config()
{
    $main_db = C('MAIN_DB');
    $rw_name  = isset($main_db[0]) ? $main_db[0] : '';
    $ro0_name = isset($main_db[1]) ? $main_db[1] : $main_db[0];
    $ro1_name = isset($main_db[2]) ? $main_db[2] : $main_db[0];
    $db_list = C('DB_LIST');
    $rw  = isset($db_list[$rw_name]) ? $db_list[$rw_name] : [];
    $ro0 = isset($db_list[$ro0_name]) ? $db_list[$ro0_name] : [];
    $ro1 = isset($db_list[$ro1_name]) ? $db_list[$ro1_name] : [];
    return [$rw, $ro0, $ro1];
}

/**
 * 根据入参获取db配置
 * @param int $gid 没有的话传0
 * @param array $option 可能的选项有 db auto_fix env_key
 * @return array
 */
function get_dbsplit_config($gid, $option = [])
{
    /**
     * 为确保线上配置不被误修改 在这里定义两个保险变量对应于db.php文件中的MAIN_DB和GID_DB_MAP配置 需同时修改才生效
     */
    $main_db_safety = ['master_rw', 'master_ro0', 'master_ro1'];
    $gid_db_map_safety = [
        '[0,0]'      => ['master_rw', 'master_ro0', 'master_ro1'],
        '[1,70000]'      => ['master_rw', 'master_ro0', 'master_ro1'],
    ];
    $errmsg = [];
    $gid_db_map = C('GID_DB_MAP');
    $db_list = C('DB_LIST');
    $main_db = C('MAIN_DB');
    list($rw_name, $ro0_name, $ro1_name) = ['', '', ''];
    // 如果是online环境 则验证保险变量和db.php文件中的对应变量是否完全一致
    if (isset($option['env_key']) && $option['env_key'] == 'online') {
        if ($main_db_safety != $main_db || $gid_db_map_safety != $gid_db_map) {
            $error = [
                'level' => 'DEBUG',
                'fatal' => 1,
                'content' => 'db config and db config safety does not match!'
            ];
            error_collector($error);
            return [[], [], []];
        }
    }

    if ($gid !== '' && $gid >= 0) {
        foreach ($gid_db_map as $range => $db_name) {
            $range = array_map('intval', json_decode($range, true));
            if ($range[0] <= $gid && $range[1] >= $gid) {
                $rw_name  = $db_name[0];
                $ro0_name = isset($db_name[1]) ? $db_name[1] : $db_name[0];
                $ro1_name = isset($db_name[2]) ? $db_name[2] : $db_name[0];
                break;
            }
        }
        $errmsg[] = 'gid_match_db_failed';
    }

    // 内部ip支持直接指定库名 若同时传了gid和db且两者配置不一致 则取gid配置且写日志
    if (isset($option['db']) && $option['db']) {
        list($rw_db, $ro0_db, $ro1_db) = explode(',', $option['db']);
        if ($gid !== '' && $gid >= 0 && [$rw_name, $ro0_name, $ro1_name] != [$rw_db, $ro0_db, $ro1_db]) {
            $errmsg[] = 'conflict_gid_and_db';
        } else {
            list($rw_name, $ro0_name, $ro1_name) = [$rw_db, $ro0_db, $ro1_db];
        }
    }

    $rw  = isset($db_list[$rw_name]) ? $db_list[$rw_name] : [];
    $ro0 = isset($db_list[$ro0_name]) ? $db_list[$ro0_name] : [];
    $ro1 = isset($db_list[$ro1_name]) ? $db_list[$ro1_name] : [];
    if ($rw && $ro0 && $ro1) {
        $result = [$rw, $ro0, $ro1];
    } else {
        $errmsg[] = 'get_dbsplit_config_failed';
        $error = [
            'level' => 'DEBUG',
            'fatal' => 0,
            'content' => implode('&', $errmsg)
        ];
        error_collector($error);
        // 线上环境强行自动修复
        if ((isset($option['auto_fix']) && $option['auto_fix']) || (isset($option['env_key']) && $option['env_key'] == 'online')) {
            $result = main_db_config();
        } else {
            $result = [[], [], []];
        }
    }

    return $result;
}

// 根据gid修改C函数中的db配置
function set_dbsplit_config($gid, $option = [])
{
    list($rw, $ro0, $ro1) = get_dbsplit_config($gid);
    if (!$rw && isset($option['auto_fix']) && $option['auto_fix']) {
        list($rw, $ro0, $ro1) = main_db_config();
    }
    if ($rw) {
        $db_list = C('DB_LIST');
        $db_list['rw']  = $rw;
        $db_list['ro0'] = $ro0;
        $db_list['ro1'] = $ro1;
        $config = $rw;
        $config['DB_LIST'] = $db_list;
        C($config);
        return true;
    } else {
        return false;
    }
}

// 错误收集
function error_collector($error = null, $option = [])
{
    static $collector = [];
    $return_value = null;
    if (is_null($error)) {
        $return_value = $collector;
    } else {
        $collector[] = $error;
    }
    if (isset($option['clear']) && $option['clear']) {
        $collector = [];
    }
    return $return_value;
}

function uploadFile($type){

    $config = \Common\Cnsts\UPLOAD_CONFIG::UPLOAD_TYPE[$type];
    $file = $_FILES[$config['file_field_name']];

    $sub_path = "".strval(date('Y-m-d', time()));

    $upload = new \Think\Upload(); // 实例化上传类
    $upload->maxSize  = $config['max_size']; // 设置附件上传大小
    $upload->exts     = $config['file_type']; // 设置附件上传类型
    $upload->rootPath = C('FILE_STORE')[$type]; // 设置附件上传根目录
    $upload->autoSub  = true; // 开启子目录保存
    $upload->replace = true;
    $upload->saveName = ''.(microtime(true)*10000); // 上传文件名
    $upload->subName = $sub_path;

    $info_raw = $upload->uploadOne($file);
    $err_msg = $upload->getError();

    if ($info_raw) {
        $err_no = \Basic\Cnsts\ERRNO::SUCCESS;
        $data = [
            'title' => $sub_path . '/'  .$info_raw['savename'],
            'original' => $info_raw['name'],
            'size' => $info_raw['size'],
        ];
    }else{
        $err_no = \Basic\Cnsts\ERRNO::UPLOAD_FAIL;
        // 失败时将错误信息统一修改为上传失败
        $err_msg = '上传失败';
        $data = '';
    }

    return [$err_no, $err_msg, $data];
}
/**
 * 发表格邮件
 * @param $header
 * @param $body
 * @return string
 */
function email_table_content($header, $body)
{
    $table_header = '<tr><th style="border: 1px solid black;">' . implode('</th><th style="border: 1px solid black;">',
            $header) . '</th></tr>';
    $table_body   = '';
    foreach ($body as $row) {
        $table_body .= '<tr><td style="border: 1px solid black;"><div style="max-width: 500px;overflow: hidden;">' . implode('</div></td><td style="border: 1px solid black;"><div style="max-width: 500px;overflow: hidden;">', $row) . '</div></td></tr>';
    }

    return '<!DOCTYPE html>
<html>
<head>
</head>
<body>
<table style="width:100%;border-collapse: collapse">' . $table_header . $table_body . '</table>
</body>
</html>
';
}


// 通过该函数来兼容查单号新老格式
function compatible_query_num($gid, $query_num)
{
    if (is_array($query_num)) {
        $result = [];
        foreach ($query_num as $num) {
            $result[] = $num;
            if (false === strpos($num, '_')) {
                $result[] = $gid . '_' . $num;
            }
        }
        return $result;
    } else {
        if (false === strpos($query_num, '_')) {
            return [$query_num, $gid . '_' . $query_num];
        } else {
            return $query_num;
        }
    }
}

/**
 * @param        $key
 * @param int    $expire
 * @param string $value
 *
 * @return mixed true: success; false: failed; null: sys error;
 */
function lock($category, $key = '', $expire = 10, $value = 'locked')
{
    if (array_key_exists($category, \Common\Cnsts\LOCK::PREFIX_MAP)) {
        $cache = Think\Cache::getInstance();
        $key   = \Common\Cnsts\LOCK::LOCK_PREFIX . '.' . $category . '.' . $key;

        return $cache->add($key, $value, $expire);
    } else {
        return null;
    }
}

/**
 * @param        $category
 * @param string $key
 *
 * @return null
 */
function get_lock_data($category, $key = '')
{
    if (array_key_exists($category, \Common\Cnsts\LOCK::PREFIX_MAP)) {
        $cache = Think\Cache::getInstance();
        $key   = \Common\Cnsts\LOCK::LOCK_PREFIX . '.' . $category . '.' . $key;

        return $cache->get($key);
    } else {
        return null;
    }
}

/**
 * @param        $category
 * @param string $key
 *
 * @return mixed
 * @throws Exception
 */
function unlock($category, $key = '')
{
    if (array_key_exists($category, \Common\Cnsts\LOCK::PREFIX_MAP)) {
        $cache = Think\Cache::getInstance();
        $key   = \Common\Cnsts\LOCK::LOCK_PREFIX . '.' . $category . '.' . $key;

        return $cache->rm($key);
    } else {
        return null;
    }
}

// 获取所有分库的配置列表
function all_db_config()
{
    $result = [];
    $gid_db_map = C('GID_DB_MAP');
    $db_list = C('DB_LIST');
    foreach ($gid_db_map as $key => $value) {
        $rw_name  = $value[0];
        $ro0_name = isset($value[1]) ? $value[1] : $value[0];
        $ro1_name = isset($value[2]) ? $value[2] : $value[0];
        $rw  = isset($db_list[$rw_name])  ? $db_list[$rw_name] : [];
        $ro0 = isset($db_list[$ro0_name]) ? $db_list[$ro0_name] : [];
        $ro1 = isset($db_list[$ro1_name]) ? $db_list[$ro1_name] : [];
        $conf = [$rw, $ro0, $ro1];

        $result[$key] = $conf;
    }

    return $result;
}

// 获取所有分库的名称列表
function all_db_name()
{
    $result = [];
    $gid_db_map = C('GID_DB_MAP');
    foreach ($gid_db_map as $key => $value) {
        $rw_name  = $value[0];
        $ro0_name = isset($value[1]) ? $value[1] : $value[0];
        $ro1_name = isset($value[2]) ? $value[2] : $value[0];
        $names = [$rw_name, $ro0_name, $ro1_name];
        $result[$key] = $names;
    }

    return $result;
}

// 内部访问接口判断
function inner_access()
{
    $data         = [
        'cli'         => IS_CLI,
        'app_debug'   => APP_DEBUG,
        'is_inner_ip' => is_inner_ip(),
        'office_ip'   => in_array(client_ip(), C('CMM_IPS')) || in_array(client_ip(false), C('CMM_IPS')),
    ];
    $inner_access = false;
    foreach ($data as $k => $v) {
        if ($v) {
            $inner_access = true;
            break;
        }
    }
    if (!$inner_access) {
        $data['client_ip'] = client_ip();
        $data['cmm_ips']   = C('CMM_IPS');

        return [
            'status'  => 1,
            'message' => 'permission denied.',
            'data'    => $data,
        ];
    }

    return $inner_access;
}

/** 重试等待:二次指数退避
 * https://baike.baidu.com/item/二进制指数退避算法/3405081?fr=aladdin
 * @param int $max_retry
 * @param int $time_unit (ms)
 */
function wait_for_retry($time_unit = 1)
{
    static $callers = [];
    $caller_id   = md5(json_encode(debug_backtrace()));
    $max_exp     = 9;
    $retry_times = 1;
    if (isset($callers[$caller_id])) {
        list($retry_times, $time_unit) = $callers[$caller_id];
    }
    $exp        = rand(0, $retry_times - 1);
    $time_as_ms = $time_unit * pow(2, $exp);
    usleep($time_as_ms * 1000);
    if ($retry_times >= $max_exp) {
        $retry_times = $max_exp;
    }
    $callers[$caller_id] = [$retry_times + 1, $time_unit];
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param string $day1
 * @param string $day2
 * @return number
 */
function diff_between_two_days($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 - $second2) / 86400;
}

/**
 * format memory_get_usage()
 *
 * @param $size
 * @return string
 */
function format_bytes($size)
{
    $unit = ['b','kb','mb','gb','tb','pb'];
    /** @var int $i */
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).$unit[$i];
}

/**
 * 是否是正整数
 *
 * @author zhaijijiang
 * @param $val
 * @return bool
 */
function is_positive_int($val)
{
    if(!is_numeric($val) || strpos($val,".")!==false || intval($val) <= 0){
        return false;
    }
    return true;
}

/**
 * 验证是否是正数,且若为小数时验证小数点后的位数
 *
 * @author zhaijijiang
 * @param $val
 * @param $precision
 * @return bool
 */
function is_positive_decimal_num($val, $precision)
{
    if(!is_numeric($val) || floatval($val) <= 0){
        return false;
    }
    if(strpos($val,'.') !== false){
        $val_arr = explode('.',$val);
        if(strlen($val_arr[1]) > $precision){
            return false;
        }
    }
    return true;
}

/**
 * 车牌是否有效
 * @param $tr_num
 *
 * @return false|int
 */
function checkTruckNum($tr_num){
    $regular_1 = '/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使领]{1}[A-Z]{1}[0-9a-zA-Z]{4}[A-HJ-NP-Z0-9挂警学领使港澳]{1}$/u';
    $regular_2 = '/[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领]{1}[A-Z]{1}[A-HJ-NP-Z0-9]{6}$/u';
    return preg_match($regular_1, $tr_num) || preg_match($regular_2, $tr_num);
}

/**
 * 计算两个时间段是否有交集（边界重叠不算）,若有交集则返回交集时间端，否则返回false
 *
 * @param string $beginTime1 开始时间1'2019-06-08 10:00:00'
 * @param string $endTime1 结束时间1
 * @param string $beginTime2 开始时间2
 * @param string $endTime2 结束时间2
 * @return bool
 */
function get_cross_time($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '') {
    $begin_stamp1 = strtotime($beginTime1); //1_1
    $end_stamp1   = strtotime($endTime1);   //1_2
    $begin_stamp2 = strtotime($beginTime2); //2_1
    $end_stamp2   = strtotime($endTime2);   //2_2
    if ($begin_stamp1 == false || $begin_stamp1 == -1 || $end_stamp1 == false || $end_stamp1 == -1
        || $begin_stamp2 == false || $begin_stamp2 == -1 || $end_stamp2 == false || $end_stamp2 == -1) {
        return false;
    }
    $status = $begin_stamp2 - $begin_stamp1;
    if ($status > 0) {       // 1_1 < 2_1
        $status2 = $begin_stamp2 - $end_stamp1;
        if ($status2 >= 0) { // 1_2 <= 2_1
            return false;
        } else {             // 1_2 > 2_1
            $from = $beginTime2;
        }
    } else {                 // 1_1 >= 2_1
        $status2 = $end_stamp2 - $begin_stamp1;
        if ($status2 > 0) {  // 1_1 < 2_2
            $from = $beginTime1;
        } else {             // 1_1 >= 2_2
            return false;
        }
    }
    if (empty($from)) {
        return false;
    }
    if ($end_stamp1 > $end_stamp2) {
        return [$from, $endTime2];
    } else {
        return [$from, $endTime1];
    }
}

/**
 * 下划线转驼峰
 * 思路:
 * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
 * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
 */
function camelize($uncamelized_words, $separator = '_')
{
    $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));

    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
}

/**
 * 驼峰命名转下划线命名
 * 思路:
 * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", lcfirst($camelCaps)));
}

/**
 * 对部分集团限制打包管理
 * @param $group_id
 * @return bool
 */
function isHasPackAuth($group_id) {
    if ($group_id <= 0) {
        return false;
    }
    if (\Basic\Cnsts\PACK::IS_PACK_AUTH){
        $pag = \Basic\Cnsts\PACK::PACK_AUTH_GROUP;
        if (isset($pag[$group_id]) || (int)$group_id < 1000) {
            return true;
        }else{
            return false;
        }
    }
    return false;
}

/**
 * @param string $num 科学计数法字符串  如 2.1E-5
 * @param int $double 小数点保留位数 默认6位
 * @return string
 */
function sc_to_num($num, $double = 6){
    if (false !== stripos($num, 'e')) {
        $a = explode('e', strtolower($num));
        return bcmul($a[0], bcpow(10, $a[1], $double), $double);
    }
    return $num;
}

/**
 * 校验日期格式是否合法
 * @param string $date
 * @param array $formats
 * @return bool
 */
function is_date_valid($date, $formats = array('Y-m-d', 'Y/m/d')) {
    $unixTime = strtotime($date);
    if (!$unixTime) { //无法用strtotime转换，说明日期格式非法
        return false;
    }

    //校验日期合法性，只要满足其中一个格式就可以
    foreach ($formats as $format) {
        if (date($format, $unixTime) == $date) {
            return true;
        }
    }

    return false;
}

if (!function_exists('array_merge_deep')) {
    /**
     * 多维数组合并
     * @param array ...$arrs
     *
     * @return array
     */
    function array_merge_deep(...$arrs)
    {
        $merged = [];
        while ($arrs) {
            $array = array_shift($arrs);
            if (!$array) {continue;}
            foreach ($array as $key => $value) {
                if (is_array($value) && array_key_exists($key, $merged)
                    && is_array($merged[$key])) {
                    $merged[$key] = array_merge_deep(...[$merged[$key], $value]);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
if (!function_exists('double_file_write')) {
    function double_file_write($source_path, $file_chmod=0){
        if (!empty(C('UPLOAD_DOUBLE_WRITE'))){
            $target_file = str_replace(C('UPLOAD_DOUBLE_WRITE_PATH')['source'], C('UPLOAD_DOUBLE_WRITE_PATH')['target'], $source_path);
            $pos = strrpos($target_file, '/');
            $file_path = substr($target_file, 0, $pos);
            if(!file_exists($file_path)) {
                mkdir($file_path,0777,true);
            }
            cmm_log(['double_2_debug' => $source_path]);
            copy($source_path, $target_file);
            if (!empty($file_chmod)){
                chmod($target_file,$file_chmod);
            }
        }
    }
}
///**
// * 为添加至消息总线的消息设置ids
// * @param $key
// * @param $value
// * @return array
// */
//function mb_ids_collector($key = null, $value = null)
//{
//    // 先进先出
//    static $ext = [];
//    if ($key !== null) {
//        $ext[$key] = $value;
//    } else{
//        $tmp = $ext;
//        $ext = [];
//        return $tmp;
//    }
//}
//
///**
// * 为添加至消息总线的消息设置ext
// * @param $key
// * @param $value
// * @return array
// */
//function mb_ext_collector($key = null, $value = null)
//{
//    // 先进先出
//    static $ext = [];
//    if ($key !== null) {
//        $ext[$key] = $value;
//    } else{
//        $tmp = $ext;
//        $ext = [];
//        return $tmp;
//    }
//}
/**
 * 根据关联的字段，合并两个数组，从而组成新的数组
 * @param array $array1 主数组
 * @param string $field1 主数组字段key
 * @param array $array2 从数组
 * @param string $field2 从数组字段key
 * @return array
 */
if (!function_exists('array_merge_by_field')) {
    function array_merge_by_field(array $array1, string $field1, array $array2, string $field2): array
    {
        if (!is_multi_array($array1)) {
            return [];
        }
        if (!is_multi_array($array2)) {
            return [];
        }
        $array2 = array_column($array2, null, $field2);
        $array2 = array_map(function ($i) use ($field2) {
            unset($i[$field2]);
            return $i;
        }, $array2);
        $merged = array_map(function ($i) use ($array2, $field1) {
            if (isset($array2[$i[$field1]])) {
                $i = array_merge($i, $array2[$i[$field1]]);
            } else {
                return false;
            }
            return $i;
        }, $array1);
        $merged = array_filter($merged);
        return $merged;
    }
}

/**
 * 判断数组是否为多维数组
 * @param array $data
 * @return bool
 */
if (!function_exists('is_multi_array')) {
    function is_multi_array(array $data): bool
    {
        return count($data) !== count($data, COUNT_RECURSIVE);
    }
}

/**
 *
 * @param array $list 改造后的M函数查询数据
 * @param array $primary_field M函数表字段，格式如下:
 * [
 *     'key' => 'join field'
 * ]
 * @param array $foreign_field  join表外键字段，格式如下:
 * [
 *     'table' => 'foreign table name',
 *     'field' => 'need query fields',
 *     'key' => 'join primary table key',
 * ]
 * @param array $ext 扩展字段
 *     time_limit: 查询超时时间
 * @return array
 *  $foreign_field = [
'table' => '',
'field' => [],
'key' => 'id',
];
$primary_field = [
'key' => 'od_id',
];
 */
if (!function_exists('dealJoinSplit')) {
    function dealJoinSplit($list, $primary_field, $foreign_field, $ext = [])
    {
        $options = [];
        isset($ext['time_limit']) && $options['time_limit'] = $ext['time_limit'];
        $foreign_keys = array_map('intval', array_unique(array_column($list, $primary_field['key'])));
        $foreign_list = M($foreign_field['table'])->where([$foreign_field['key'] => ['in', $foreign_keys]])->field($foreign_field['field'])->select($options);
        $list = $list ?: [];
        $foreign_list = $foreign_list ?: [];
        $list = array_merge_by_field($list, $primary_field['key'], $foreign_list, $foreign_field['key']);
        return $list;
    }
}
// 递归修改键名
if (!function_exists('changeKeys')) {
    function changeKeys($array, $keyOriArray, $keyNewArray)
    {
        if(!is_array($array)) return $array;
        $tempArray = array();
        foreach ($array as $key => $value){
            // 处理数组的键，进行替换
            $key = array_search($key, $keyOriArray, true) === false ? $key : $keyNewArray[array_search($key, $keyOriArray)];
            if(is_array($value)){
                $value = changeKeys($value, $keyOriArray, $keyNewArray);
            }
            $tempArray[$key] = $value;
        }
        return $tempArray;
    }
}
/**
 * 返回微秒时间戳
 * @return float
 */
function micTimestamp(): float
{
    list($msec, $sec) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
}
/**
 * 图片转base64
 * @param $image_file
 * @return string
 */
function base64EncodeImage($image_file): string
{
    $image_info = getimagesize($image_file);
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
    return 'data:' . $image_info['mime'] . ';base64,' . base64_encode($image_data);
}
/**
 * 解码一维数组json字段
 * @param $map
 * @param $json_field
 * @return array
 */
function decodeField4Json($map, $json_field)
{
    return array_merge($map, array_map(function ($i) {
        !empty($i) && $i = json_decode($i, true);
        return $i;
    }, array_intersect_key($map, array_flip($json_field))));
}

/**
 * 解码多维数组json字段
 * @param $list
 * @param $json_field
 * @return array|array[]
 */
function decodeField4JsonList($list, $json_field)
{
    return array_map(function ($ni_info) use ($json_field) {
        return array_merge($ni_info, array_map(function ($i) {
            !empty($i) && $i = json_decode($i, true);
            return $i;
        }, array_intersect_key($ni_info, array_flip($json_field))));
    }, $list);
}

/**
 * 编码一维数组字段转JSON字符串
 * @param $map
 * @param $json_field
 * @return array
 */
function encodeField4Json($map, $json_field)
{
    return array_merge($map, array_map(function ($i) {
        $i = json_encode($i, JSON_UNESCAPED_UNICODE);
        return $i;
    }, array_intersect_key($map, array_flip($json_field))));
}

/**
 * 编码多维数组字段转JSON字符串
 * @param $list
 * @param $json_field
 * @return array|array[]
 */
function encodeField4JsonList($list, $json_field)
{
    return array_map(function ($ni_info) use ($json_field) {
        return array_merge($ni_info, array_map(function ($i) {
            $i = json_encode($i, JSON_UNESCAPED_UNICODE);
            return $i;
        }, array_intersect_key($ni_info, array_flip($json_field))));
    }, $list);
}
function hideName($name) {
    if (mb_strlen($name, 'utf-8') == 1) {
        return '*';
    } elseif (mb_strlen($name, 'utf-8') == 2) {
        return  '*' . mb_substr($name, 1, 1, 'utf-8');
    } else {
        $length = mb_strlen($name, 'utf-8');
        $hiddenChars = '';
        for ($i = 1; $i < $length-1; $i++) {
            $hiddenChars .= '*';
        }
        return mb_substr($name, 0, 1, 'utf-8') . $hiddenChars . mb_substr($name, -1, 1, 'utf-8');
    }
}
function hidePhoneNumber($phoneNumber) {
    if (strlen($phoneNumber) >= 8) {
        $hiddenChars = '';
        for ($i = 0; $i < strlen($phoneNumber)-6; $i++) {
            $hiddenChars .= '*';
        }
        return substr($phoneNumber, 0, 3) . $hiddenChars . substr($phoneNumber, -3);
    } else {
        return $phoneNumber;
    }
}