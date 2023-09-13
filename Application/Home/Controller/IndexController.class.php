<?php
namespace Home\Controller;

use Home\Cnsts\ERRNO;
use Home\Model\MQ;
use Home\Model\SocketModel;
use Think\Controller;

class IndexController extends Controller
{

    // 请求参数
    protected $req = null;
    protected $socket = null;
    protected $client = null;

    public function __construct()
    {
        parent::__construct();
        $this->req = json_decode(html_entity_decode(I("req", "", "htmlspecialchars")), true);
    }

    protected function doResponse($errno = ERRNO::SUCCESS, $errmsg = 'success', $res = [], $tpl = "")
    {
        $resp = [
            "errno"  => $errno,
            "errmsg" => $errmsg,
            "res"    => $res,
        ];
        if (empty($tpl)) {
            header('Content-type: application/json');
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->assign("data", $resp);
        $this->display($tpl, 'utf-8', 'text/html');
    }

    public function fff()
    {
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }

    public function test() {
        $data = [
            'sex' => '男',
            'name' => '测试',
            'password' => '123456',
        ];
        return M('user')->add($data);
    }

    public function test2() {
        $res = M('user')->select();
        jdd($res);
    }

    public function index($first_class = '', $second_class = '') {
        !empty($this->req['first_class']) && $first_class = $this->req['first_class'];
        !empty($this->req['second_class']) && $second_class = $this->req['second_class'];
        $tpl = $this->loginCheck($first_class, $second_class);
        $this->display($tpl, 'utf-8', 'text/html');
    }

    public function test4() {

        jdd(S("name","test"));
    }

    public function test5() {

        jdd(S("name"));
    }

    public function login($name = '', $password = '') {// get 请求
        $errno = ERRNO::SUCCESS;
        $option = [
            'where' => [
                'name' => $this->req['name'],
                'password' => $this->req['password'],
            ],
        ];
        $user = D('User')->select($option);
        if (empty($user)) {
            $errno = ERRNO::USER_PWD_ERROR;
            $this->doResponse($errno, ERRNO::e($errno), []);
        } else {
            session('user_id', $user[0]['id']);
            $this->doResponse($errno, ERRNO::e($errno), []);
        }
    }
    public function loginCheck($first_class, $second_class): string
    {
        if (empty(session('user_id'))) {
//            $this->doResponse(ERRNO::NO_LOGIN, ERRNO::e(ERRNO::NO_LOGIN), []);
//            exit();
            return 'login';
        }
        if ($first_class === 'test') {
            return 'login';
        }
        return 'index';
    }

    public function logout() {
        session(null);
        $this->doResponse(ERRNO::SUCCESS, ERRNO::e(ERRNO::SUCCESS), []);
    }

    public function socketInit() {
        $this->doResponse(ERRNO::SUCCESS, ERRNO::e(ERRNO::SUCCESS), []);
        cmm_fastcgi_finish_request();
//        (new SocketModel())->start();
    }

    public function socketClient() {
        $this->client = $this->getSocketClient();
        if ($this->client !== false) {
            $message = 'test';
            while (true) {
                socket_recv($this->client, $message, 1000, MSG_WAITALL);
                cmm_log([' socket Client 收到消息：' . $message]);
            }
        }
    }

    public function sendMessage($message = 'hello') {
        empty($this->client) && $this->client = $this->getSocketClient();
        if ($this->client !== false) {
            socket_write($this->client, $message, strlen($message));
        }
    }
    public function getSocketClient()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        cmm_log([' socket Client 开始连接']);
        $result = socket_connect($socket,'a.chumeng1.top', 12345);
        if ($result === false) {
            cmm_log(["socket Client 连接失败: " . socket_strerror(socket_last_error())]);
        } else {
            cmm_log([' socket Client 连接成功']);
        }
        return $result;
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
}