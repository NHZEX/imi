<?php

namespace Imi\Db\Listener;

use Imi\Db\Pool\DbResource;
use Imi\Db\Statement\StatementManager;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\RequestContext;

class RequestContextDestroy implements IEventListener
{
    /**
     * 事件处理方法.
     *
     * @param EventParam $e
     *
     * @return void
     */
    public function handle(EventParam $e)
    {
        // 释放当前连接池连接的 Statement
        $resources = RequestContext::get('poolResources', []);
        if ($resources)
        {
            foreach ($resources as $resource)
            {
                if ($resource instanceof DbResource)
                {
                    StatementManager::unUsingAll($resource->getInstance());
                }
            }
        }
    }
}
