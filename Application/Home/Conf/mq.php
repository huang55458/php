<?php


 {
    // 默认
    $uri = '192.168.1.85:9100';
    $topic = [
        'ASYNC_INDEX'      => ['name' => 'tms_idx_async_index_alpha', 'uri' => $uri],
        'ASYNC_INDEX_SLOW' => ['name' => 'tms_idx_async_index_slow_alpha', 'uri' => $uri],
        'MESSAGE'          => ['name' => 'msg_push_pro_prod_alpha', 'uri' => $uri],
        'DETAIL_ACCOUNT'   => ['name' => 'detail_account_alpha', 'uri' => $uri],
        'IMPORT'           => ['name' => 'import_alpha', 'uri' => $uri],
        'CMM_IMPORT'       => ['name' => 'cmm_import_alpha', 'uri' => $uri],
        'UNIVERSAL'        => ['name' => 'universal_alpha', 'uri' => $uri],
        'ORG_STATE'        => ['name' => 'org_state_alpha', 'uri' => $uri],
        'WECHAT'           => ['name' => 'wechat_alpha', 'uri' => $uri],
        'OPEN_RULE'        => ['name' => 'open_rule', 'uri' => $uri],
        'ASYNC_CAL_WAGE'   => ['name' => 'async_cal_wage_alpha', 'uri' => $uri],
        'ASYNC_FINANCE_DATA'   => ['name' => 'async_finance_data_alpha', 'uri' => $uri],
        'SYSTEM_BUS_1'     => ['name' => 'system_bus_1_alpha', 'uri' => $uri, 'partition' => 6], // partition分区数，不填则报错
        'SYSTEM_BUS_2'     => ['name' => 'system_bus_2_alpha', 'uri' => $uri, 'partition' => 6], // partition分区数，不填则报错
        'SYSTEM_BUS_3'     => ['name' => 'system_bus_3_alpha', 'uri' => $uri, 'partition' => 6], // partition分区数，不填则报错
    ];
}

return [
    'KAFKA' => [
        'topics'  => array_column($topic, 'uri', 'name'),
        'partition'  => array_column($topic, 'partition', 'name')
    ],
    'KAFKA_TOPIC' => array_combine(array_keys($topic), array_column($topic, 'name')),
];
