<?php

namespace Imi\Server\Http\Listener;

use Imi\ConnectContext;
use Imi\RequestContext;
use Imi\Server\Event\Listener\IRequestEventListener;
use Imi\Server\Event\Param\RequestEventParam;
use Imi\Server\Http\Dispatcher;

/**
 * request事件前置处理.
 */
class BeforeRequest implements IRequestEventListener
{
    /**
     * 事件处理方法.
     *
     * @param RequestEventParam $e
     *
     * @return void
     */
    public function handle(RequestEventParam $e)
    {
        try
        {
            $request = $e->request;
            $response = $e->response;
            // 上下文创建
            RequestContext::muiltiSet([
                'server'    => $server = $request->getServerInstance(),
                'request'   => $request,
                'response'  => $response,
            ]);
            /** @var \Imi\Server\Http\Server $server */
            if ($server->isHttp2())
            {
                RequestContext::set('fd', $request->getSwooleRequest()->fd);
                ConnectContext::create();
            }
            // 中间件
            /** @var Dispatcher $dispatcher */
            $dispatcher = $server->getBean('HttpDispatcher');
            $dispatcher->dispatch($request);
        }
        catch (\Throwable $th)
        {
            if (!isset($server))
            {
                throw $th;
            }
            if (true !== $server->getBean('HttpErrorHandler')->handle($th))
            {
                throw $th;
            }
        }
    }
}
