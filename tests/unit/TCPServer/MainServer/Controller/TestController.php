<?php

namespace Imi\Test\TCPServer\MainServer\Controller;

use Imi\ConnectContext;
use Imi\RequestContext;
use Imi\Server\Route\Annotation\Tcp\TcpAction;
use Imi\Server\Route\Annotation\Tcp\TcpController;
use Imi\Server\Route\Annotation\Tcp\TcpRoute;

/**
 * 数据收发测试.
 *
 * @TcpController
 */
class TestController extends \Imi\Controller\TcpController
{
    /**
     * 登录.
     *
     * @TcpAction
     * @TcpRoute({"action"="login"})
     *
     * @param \stdClass $data
     *
     * @return array
     */
    public function login($data)
    {
        ConnectContext::set('username', $data->username);
        $this->server->joinGroup('g1', $this->data->getFd());
        ConnectContext::bind($data->username);

        return ['action' => 'login', 'success' => true, 'middlewareData' => RequestContext::get('middlewareData')];
    }

    /**
     * 发送消息.
     *
     * @TcpAction
     * @TcpRoute({"action"="send"})
     *
     * @param \stdClass $data
     *
     * @return void
     */
    public function send($data)
    {
        $message = [
            'action'     => 'send',
            'message'    => ConnectContext::get('username') . ':' . $data->message,
        ];
        $this->server->groupCall('g1', 'send', $this->server->getBean(\Imi\Server\DataParser\DataParser::class)->encode($message));
    }

    /**
     * 测试重复路由警告.
     *
     * @TcpAction
     * @TcpRoute({"duplicated"="1"})
     *
     * @return void
     */
    public function duplicated1()
    {
    }

    /**
     * 测试重复路由警告.
     *
     * @TcpAction
     * @TcpRoute({"duplicated"="1"})
     *
     * @return void
     */
    public function duplicated2()
    {
    }
}
