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
        $tpl = (new \Home\Service\IndexService())->loginCheck($first_class, $second_class);
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

    public function logout() {
        session(null);
        $this->doResponse(ERRNO::SUCCESS, ERRNO::e(ERRNO::SUCCESS), []);
    }

    public function sendEmail() {
        $title = '这是一个测试';
        $time = '2000-2-2 34:34:33';
        $content = <<<EOF
        <h1>Hello World!</h1> 
        <h2 id="test">$time</h2>
        EOF;
        $option = [
            'to' => '----------@------',
            'username' => '----------@------',
            'password' => '***********',
        ];
        $res = email($title, $content, $option);
    }
}