<?php


 {
    // 默认
    $uri = '45.32.46.233:9091';
    $topic = [
        'test'     => ['name' => 'test', 'uri' => $uri],
//        'test'     => ['name' => 'test', 'uri' => $uri, 'partition' => 1], // partition分区数，不填则报错
    ];
}

return [
    'KAFKA' => [
        'topics'  => array_column($topic, 'uri', 'name'),
        'partition'  => array_column($topic, 'partition', 'name')
    ],
    'KAFKA_TOPIC' => array_combine(array_keys($topic), array_column($topic, 'name')),
];
