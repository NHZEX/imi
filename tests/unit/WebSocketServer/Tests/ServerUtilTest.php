<?php

namespace Imi\Test\WebSocketServer\Tests;

use Swoole\Coroutine\Channel;
use Yurun\Util\HttpRequest;

/**
 * @testdox Imi\Server\Server
 */
class ServerUtilTest extends BaseTest
{
    public function testGetServer()
    {
        $this->go(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host . 'serverUtil/getServer');
            $this->assertEquals([
                'null'      => 'main',
                'main'      => 'main',
                'notFound'  => true,
            ], $response->json(true));
        });
    }

    public function testSendMessage()
    {
        $this->go(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host . 'serverUtil/sendMessage');
            $this->assertEquals([
                'sendMessageAll'    => 2,
                'sendMessage1'      => 1,
                'sendMessage2'      => 2,
                'sendMessageRawAll' => 2,
                'sendMessageRaw1'   => 1,
                'sendMessageRaw2'   => 2,
            ], $response->json(true));
        });
    }

    public function testSend()
    {
        $this->go(function () {
            $th = null;
            $channel = new Channel(16);
            $func = function ($index, $recvCount) use ($channel) {
                $dataStr = json_encode([
                    'data'  => 'test',
                ]);
                $http = new HttpRequest();
                $http->retry = 3;
                $http->timeout = 10000;
                $client = $http->websocket($this->host);
                $this->assertTrue($client->isConnected());
                $this->assertTrue($client->send(json_encode([
                    'action'    => 'info',
                ])));
                $recv = $client->recv();
                $recvData = json_decode($recv, true);
                if (!isset($recvData['fd']))
                {
                    $this->assertTrue(false, $client->getErrorCode() . '-' . $client->getErrorMessage());
                }
                $channel->push([$index => $recvData['fd']]);
                for ($i = 0; $i < $recvCount; ++$i)
                {
                    $recvResult = $client->recv();
                    $this->assertEquals($dataStr, $recvResult, $client->getErrorCode() . '-' . $client->getErrorMessage());
                }
                $client->close();
            };
            $waitChannel = new Channel(16);
            go(function () use ($func, $channel, $waitChannel) {
                try
                {
                    $func(0, 6);
                    $waitChannel->push(1);
                }
                catch (\Throwable $th)
                {
                    $channel->push($th);
                    $waitChannel->push($th);
                }
            });
            go(function () use ($func, $channel, $waitChannel) {
                try
                {
                    $func(1, 4);
                    $waitChannel->push(1);
                }
                catch (\Throwable $th)
                {
                    $channel->push($th);
                    $waitChannel->push($th);
                }
            });
            go(function () use ($waitChannel, $channel) {
                try
                {
                    $dataStr = json_encode([
                        'data'  => 'test',
                    ]);
                    $http = new HttpRequest();
                    $http->retry = 3;
                    $http->timeout = 10000;
                    $client = $http->websocket($this->host);
                    $this->assertTrue($client->isConnected());
                    $this->assertTrue($client->send(json_encode([
                        'action'    => 'login',
                        'username'  => 'testSend',
                    ])));
                    $recv = $client->recv();
                    $recvData = json_decode($recv, true);
                    $this->assertTrue($recvData['success'] ?? null, $client->getErrorCode() . '-' . $client->getErrorMessage());
                    $channel->push('test');
                    for ($i = 0; $i < 4; ++$i)
                    {
                        $recvResult = $client->recv();
                        $this->assertEquals($dataStr, $recvResult, $client->getErrorCode() . '-' . $client->getErrorMessage());
                    }
                    $client->close();
                    $waitChannel->push(1);
                }
                catch (\Throwable $th)
                {
                    $channel->push($th);
                    $waitChannel->push($th);
                }
            });
            $fds = [];
            $th = null;
            for ($i = 0; $i < 3; ++$i)
            {
                $result = $channel->pop(30);
                $this->assertNotFalse($result);
                if (\is_array($result))
                {
                    $fds[key($result)] = current($result);
                }
                elseif ($result instanceof \Throwable)
                {
                    $th = $result;
                }
            }
            if (isset($th))
            {
                throw $th;
            }
            ksort($fds);
            $this->assertCount(2, $fds);
            $http = new HttpRequest();
            $response = $http->post($this->host . 'serverUtil/send', [
                'fds'  => $fds,
                'flag' => 'testSend',
            ], 'json');
            $th = null;
            for ($i = 0; $i < 3; ++$i)
            {
                $result = $waitChannel->pop();
                $this->assertNotFalse($result);
                if ($result instanceof \Throwable)
                {
                    $th = $result;
                }
            }
            if (isset($th))
            {
                throw $th;
            }
            $this->assertEquals([
                'send1'         => 0,
                'send2'         => 1,
                'send3'         => 2,
                'sendByFlag'    => 1,
                'sendRaw1'      => 0,
                'sendRaw2'      => 1,
                'sendRaw3'      => 2,
                'sendRawByFlag' => 1,
                'sendToAll'     => 3,
                'sendRawToAll'  => 3,
            ], $response->json(true));
        });
    }

    public function testSendToGroup()
    {
        $this->go(function () {
            $th = null;
            $waitChannel = new Channel(16);
            $func = function ($recvCount) use ($waitChannel) {
                $dataStr = json_encode([
                    'data'  => 'test',
                ]);
                $http = new HttpRequest();
                $http->retry = 3;
                $http->timeout = 10000;
                $client = $http->websocket($this->host);
                $this->assertTrue($client->isConnected());
                $this->assertTrue($client->send(json_encode([
                    'action'    => 'login',
                    'username'  => uniqid('', true),
                ])));
                $recv = $client->recv();
                $recvData = json_decode($recv, true);
                $this->assertTrue($recvData['success'] ?? null, $client->getErrorCode() . '-' . $client->getErrorMessage());
                $waitChannel->push(1);
                for ($i = 0; $i < $recvCount; ++$i)
                {
                    $recvResult = $client->recv();
                    $this->assertEquals($dataStr, $recvResult, $client->getErrorCode() . '-' . $client->getErrorMessage());
                }
                $client->close();
            };
            for ($i = 0; $i < 2; ++$i)
            {
                go(function () use ($func, $waitChannel) {
                    try
                    {
                        $func(2);
                        $waitChannel->push(1);
                    }
                    catch (\Throwable $th)
                    {
                        $waitChannel->push($th);
                    }
                });
            }
            $th = null;
            for ($i = 0; $i < 2; ++$i)
            {
                $result = $waitChannel->pop();
                $this->assertNotFalse($result);
                if ($result instanceof \Throwable)
                {
                    $th = $result;
                }
            }
            if (isset($th))
            {
                throw $th;
            }
            $http = new HttpRequest();
            $response = $http->get($this->host . 'serverUtil/sendToGroup');
            $th = null;
            for ($i = 0; $i < 2; ++$i)
            {
                $result = $waitChannel->pop();
                $this->assertNotFalse($result);
                if ($result instanceof \Throwable)
                {
                    $th = $result;
                }
            }
            if (isset($th))
            {
                throw $th;
            }
            $this->assertEquals([
                'groupFdCount'   => 2,
                'sendToGroup'    => 2,
                'sendRawToGroup' => 2,
            ], $response->json(true));
        });
    }

    public function testClose()
    {
        $http1 = new HttpRequest();
        $http1->retry = 3;
        $http1->timeout = 10000;
        $client1 = $http1->websocket($this->host);
        $this->assertTrue($client1->isConnected());
        $this->assertTrue($client1->send(json_encode([
            'action'    => 'info',
        ])));
        $recv = $client1->recv();
        $recvData1 = json_decode($recv, true);
        $this->assertTrue(isset($recvData1['fd']), 'Not found fd');

        $http2 = new HttpRequest();
        $http2->retry = 3;
        $http2->timeout = 10000;
        $client2 = $http2->websocket($this->host);
        $this->assertTrue($client2->isConnected());
        $this->assertTrue($client2->send(json_encode([
            'action'    => 'login',
            'username'  => 'testClose',
        ])));
        $recv = $client2->recv();
        $recvData2 = json_decode($recv, true);
        $this->assertTrue($recvData2['success'] ?? null, 'Not found success');

        $http3 = new HttpRequest();
        $response = $http3->post($this->host . 'serverUtil/close', ['fd' => $recvData1['fd'], 'flag' => 'testClose']);
        $this->assertEquals([
            'fd'   => 1,
            'flag' => 1,
        ], $response->json(true));
        $this->assertEquals('', $client1->recv(1));
        $this->assertEquals('', $client2->recv(1));
    }
}
