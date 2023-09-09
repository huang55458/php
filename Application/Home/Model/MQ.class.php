<?php

namespace Home\Model;



use Home\Cnsts\ERRNO;

class MQ
{
    public static function send($topic, $data, $key = null, $partition = null)
    {
        $config = C('KAFKA');
        static $producer = [];
        static $topics = [];
        if (isset($config['topics'][$topic])) {
            // init producer
            if (!isset($producer[$topic])) {
                $producer_config = new \RdKafka\Conf();
                $producer_config->set('queue.buffering.max.ms', 10);
                $producer_config->set('socket.blocking.max.ms', 1);
                $producer_config->set('batch.num.messages', 100);
                $producer_config->set('socket.keepalive.enable', "true");
                $producer_config->set('api.version.request', "false");

                $producer_config->setDrMsgCb(function ($kafka, $message) {
                    if ($message->err) {
                        cmm_log(['kafka_producer_response', $kafka, $message], 'ERROR_TRACE');
                    } else {
                        cmm_log(['kafka_producer_response', $message]);
                    }
                });
                $producer_config->setErrorCb(function ($kafka, $err, $reason) {//发送失败后调用
                    cmm_log(['kafka_producer_response', 'kafka' => $kafka, 'err' => $err, 'reason' => $reason], 'ERROR_TRACE');
                });

                $producer[$topic] = new \RdKafka\Producer($producer_config);
                $producer[$topic]->addBrokers($config['topics'][$topic]);
            }
            // init topic
            if (!isset($topics[$topic])) {
                $topic_conf = new \RdKafka\TopicConf();
                $topic_conf->set('request.required.acks', 1);
                $topics[$topic] = $producer[$topic]->newTopic($topic, $topic_conf);
            }
            // send data
            $data['value']['_producer_send_time'] = microtime(true);
            // REQUEST_ID
            $data['value']['_request_id'] = REQUEST_ID;
            // producer hostname
            $data['value']['_hostname'] = gethostname();
            // group_id 分库
            $data['value']['_group_id'] = GROUP_ID;
            // session_id 异步处理需要共享session
            $data['value']['_s_group_id']   = session('group_id');
            $data['value']['_s_company_id'] = session('company_id');
            $data['value']['_s_user_id']    = session('user_id');
            if ($key === null) {
                $topics[$topic]->produce(($partition !== null) ? ($partition) : RD_KAFKA_PARTITION_UA, 0,
                                         json_encode($data, JSON_UNESCAPED_UNICODE));
            } else {
                $topics[$topic]->produce(($partition !== null) ? ($partition) : RD_KAFKA_PARTITION_UA, 0,
                                         json_encode($data, JSON_UNESCAPED_UNICODE), $key);
            }
//            if('import' != $topic) {
//                cmm_log('kafka_producer: ' . json_encode($topic) . ' ' . json_encode($data) . ' ' . ($key?:'NULL') . ' ' .
//                    $partition);
                cmm_log('kafka_producer: ' . json_encode([
                    'topic' => $topic,
//                    'data' => $data,
                    'key' => $key,
                    'partition' => $partition,
//                    'str_length' => strlen(json_encode($data, 256)),
                    ], 64|256));
//            }
            return [ERRNO::SUCCESS, ''];
        }

        return [ERRNO::MQ_TOPIC_NOT_EXISTS, ERRNO::e(ERRNO::MQ_TOPIC_NOT_EXISTS)];
    }

}
