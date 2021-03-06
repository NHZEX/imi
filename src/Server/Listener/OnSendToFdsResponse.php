<?php

namespace Imi\Server\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Util\Co\ChannelContainer;

/**
 * 发送给指定连接-响应.
 *
 * @Listener(eventName="IMI.PIPE_MESSAGE.sendToFdsResponse")
 */
class OnSendToFdsResponse implements IEventListener
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
        $data = $e->getData()['data'];
        if (ChannelContainer::hasChannel($data['messageId']))
        {
            ChannelContainer::push($data['messageId'], $data);
        }
    }
}
