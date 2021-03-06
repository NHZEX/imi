<?php

namespace Imi\Aop;

class AfterThrowingJoinPoint extends JoinPoint
{
    /**
     * 异常.
     *
     * @var \Throwable
     */
    private $throwable;

    /**
     * 是否取消抛出异常.
     *
     * @var bool
     */
    private $isCancelThrow = false;

    /**
     * @param string              $type
     * @param string              $method
     * @param array               $args
     * @param object              $target
     * @param \Imi\Bean\BeanProxy $_this
     * @param \Throwable          $throwable
     */
    public function __construct($type, $method, $args, $target, $_this, \Throwable $throwable)
    {
        parent::__construct($type, $method, $args, $target, $_this);
        $this->throwable = $throwable;
    }

    /**
     * 获取异常.
     *
     * @return \Throwable
     */
    public function getThrowable()
    {
        return $this->throwable;
    }

    /**
     * 取消抛出异常.
     *
     * @param bool $isCancelThrow 是否取消，默认为true
     *
     * @return void
     */
    public function cancelThrow($isCancelThrow = true)
    {
        $this->isCancelThrow = $isCancelThrow;
    }

    /**
     * 是否取消抛出异常.
     *
     * @return bool
     */
    public function isCancelThrow()
    {
        return $this->isCancelThrow;
    }
}
