<?php

namespace Imi\Server\View;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\Http\Message\Response;

/**
 * 视图类.
 *
 * @Bean("View")
 */
class View
{
    /**
     * 核心处理器.
     *
     * @var array
     */
    protected $coreHandlers = [
        'html'  => \Imi\Server\View\Handler\Html::class,
        'json'  => \Imi\Server\View\Handler\Json::class,
        'xml'   => \Imi\Server\View\Handler\Xml::class,
    ];

    /**
     * 扩展处理器.
     *
     * @var array
     */
    protected $exHandlers = [];

    /**
     * 传入视图处理器的数据.
     *
     * @var array
     */
    protected $data = [];

    /**
     * 视图处理器对象列表.
     *
     * @var \Imi\Server\View\Handler\IHandler[]
     */
    protected $handlers;

    /**
     * @return void
     */
    public function __init()
    {
        $handlers = &$this->handlers;
        foreach ([$this->coreHandlers, $this->exHandlers] as $list)
        {
            if ($list)
            {
                foreach ($list as $name => $class)
                {
                    $handlers[$name] = RequestContext::getServerBean($class);
                }
            }
        }
    }

    /**
     * @param string                            $renderType
     * @param array                             $data
     * @param array                             $options
     * @param \Imi\Server\Http\Message\Response $response
     *
     * @return \Imi\Server\Http\Message\Response
     */
    public function render($renderType, $data, $options, Response $response = null): Response
    {
        $handlers = &$this->handlers;
        if (isset($handlers[$renderType]))
        {
            if ($this->data && \is_array($data))
            {
                $data = array_merge($this->data, $data);
            }
            if (null === $response)
            {
                $response = RequestContext::get('response');
            }

            return $handlers[$renderType]->handle($data, $options, $response);
        }
        else
        {
            throw new \RuntimeException('Unsupport View renderType: ' . $renderType);
        }
    }
}
