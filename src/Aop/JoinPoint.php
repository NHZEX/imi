<?php

namespace Imi\Aop;

class JoinPoint
{
    /**
     * 切入点类型.
     *
     * @var string
     */
    protected $type;

    /**
     * 请求方法名.
     *
     * @var string
     */
    protected $method;

    /**
     * 请求参数.
     *
     * @var array
     */
    protected $args;

    /**
     * 连接点所在的目标对象
     *
     * @var object
     */
    protected $target;

    /**
     * 代理对象本身.
     *
     * @var \Imi\Bean\BeanProxy
     */
    protected $_this;

    /**
     * @param string              $type
     * @param string              $method
     * @param array               $args
     * @param object              $target
     * @param \Imi\Bean\BeanProxy $_this
     */
    public function __construct($type, $method, &$args, $target, $_this)
    {
        $this->type = $type;
        $this->method = $method;
        $this->args = &$args;
        $this->target = $target;
        $this->_this = $_this;
    }

    /**
     * 获取切入点类型.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 获取请求方法名.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取请求参数.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * 获取连接点所在的目标对象
     *
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * 获取代理对象本身.
     *
     * @return \Imi\Bean\BeanProxy
     */
    public function getThis()
    {
        return $this->_this;
    }

    /**
     * 修改请求参数.
     *
     * @param array $args 请求参数
     *
     * @return void
     */
    public function setArgs(array $args)
    {
        $this->args = $args;
    }
}
