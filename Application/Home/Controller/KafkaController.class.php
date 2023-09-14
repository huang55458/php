<?php
namespace Home\Controller;

use Home\Model\MQ;
use Think\Controller;

class KafkaController extends Controller
{
    // 请求参数
    protected $req = null;

    public function __construct()
    {
        parent::__construct();
        $this->req = json_decode(html_entity_decode(I("req", "", "htmlspecialchars")), true);
        $tpl = (new \Home\Service\IndexService())->loginCheck();
        if ($tpl === 'login') {
            jdd('未登录');
        }
    }



    public function kafka() {
//        jdd(C('KAFKA'));
        $val = MQ::send(C('KAFKA_TOPIC')['test'], ['test' => 'test']);

//        $conf = new \RdKafka\Conf();
//        $conf->set('metadata.broker.list', '45.32.46.233:9091'); //设置Kafka broker地址
//        $producer = new \RdKafka\Producer($conf); //创建生产者对象
//        $topic = $producer->newTopic('test'); //创建主题
//        $topic->produce(RD_KAFKA_PARTITION_UA, 0, 'Hello, World!'); //发送消息
//        $producer->poll(0); //等待消息发送完成
//        while ($producer->getOutQLen() > 0) { //检查发送队列是否为空
//            $producer->poll(50);
//        }
    }
    /*
     * 默认的分区策略是：
如果在发消息的时候指定了分区，则消息投递到指定的分区
如果没有指定分区，但是消息的key不为空，则基于key的哈希值来选择一个分区
如果既没有指定分区，且消息的key也是空，则用轮询的方式选择一个分区
    生产者发送一条消息只会进入一个分区
    不同的消费者组会读同一条消息
     */
    public function consumerStart() { // 启动kafka
//        $brokers = "45.32.46.233:9091"; // Kafka broker 服务器地址和端口
        $brokers = C('KAFKA')['topics']['test']; // Kafka broker 服务器地址和端口
        $topic = "test"; // 消费的主题名称
        $groupId = "test_group"; // 消费者组 ID

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $groupId);

        $consumer = new \RdKafka\Consumer($conf);
        $consumer->addBrokers($brokers);

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.offset.reset', 'smallest');

        $topic = $consumer->newTopic($topic, $topicConf);

        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true) {
            $message = $topic->consume(0, 1000);
            if ($message === null) {
                continue;
            }

            if ($message->err) {
                cmm_log('kafka consumer 连接失败,'."Error: " . $message->errstr());
                break;
            }
            if (isset($message->payload)) {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . '/api/';
                $params = [
                    'url' => $url,
                    'post_data' => [
                        'req' => json_encode($message->payload, JSON_UNESCAPED_UNICODE),
                    ],
                    'cookie' => cookie('PHPSESSID'),
                ];
            }

            try {
                isset($params) && $res = cmm_curl($params);
            } catch (\Exception $e) {
                cmm_log(['fromBill-post', $e->getMessage()]);
            }
//            jdd( "Received message: " . $message->payload );
        }

        $consumer->close();
    }
}