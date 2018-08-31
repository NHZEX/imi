<?php
namespace Imi\Bean;

use Imi\App;
use Imi\Config;
use Imi\Util\Imi;
use Imi\Util\Text;
use Imi\Aop\JoinPoint;
use Imi\RequestContext;
use Imi\Aop\AroundJoinPoint;
use Imi\Aop\Parser\AopParser;
use Imi\Bean\Annotation\Inject;
use Imi\Bean\Parser\BeanParser;
use Imi\Aop\AfterThrowingJoinPoint;
use Imi\Aop\AfterReturningJoinPoint;
use Imi\Aop\Annotation\AfterThrowing;
use Imi\Util\Coroutine;

class BeanProxy
{
	/**
	 * 对象
	 * @var mixed
	 */
	private $object;

	/**
	 * 对象反射
	 * @var \ReflectionClass
	 */
	private $refClass;

	/**
	 * 存储每个类对应的切面关系
	 * @var \SplPriorityQueue[]
	 */
	private static $aspects = [];

	public function __construct($object)
	{
		$this->object = $object;
		$this->init();
	}

	/**
	 * 魔术方法
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function call($method, $callback, $args)
	{
		try{
			// 先尝试环绕
			if($this->parseAround($method, $args, $result, $callback))
			{
				return $result;
			}
			else
			{
				// 正常请求
				return $this->callOrigin($method, $args, $callback);
			}
		}catch(\Throwable $throwable){
			// 异常
			$this->parseAfterThrowing($method, $args, $throwable);
		}
	}

	/**
	 * 初始化
	 * @return void
	 */
	private function init()
	{
		$this->refClass = new \ReflectionClass($this->object);
		// 属性注入
		$this->injectProps();
		$className = $this->refClass->getParentClass()->getName();
		// 每个类只需处理一次
		if(isset(static::$aspects[$className]))
		{
			return;
		}
		static::$aspects[$className] = new \SplPriorityQueue;
		$aopData = AopParser::getInstance()->getData();
		foreach($aopData as $option)
		{
			// 判断是否属于当前类的切面
			if($this->isAspectCurrentClass($option))
			{
				static::$aspects[$className]->insert($option, $option['aspect']->priority);
			}
		}
	}

	/**
	 * 注入属性
	 *
	 * @return void
	 */
	private function injectProps()
	{
		$className = $this->refClass->getParentClass()->getName();
		list($annotations, $configs) = static::getInjects($className);

		// @inject()和@requestInject()注入
		foreach($annotations as $propName => $annotation)
		{
			$propRef = $this->refClass->getProperty($propName);
			$propRef->setAccessible(true);
			$propRef->setValue($this->object, static::getInjectValueByAnnotation($annotation));
		}

		// 配置注入
		foreach($configs as $name => $value)
		{
			$propRef = $this->refClass->getProperty($name);
			if(null === $propRef)
			{
				continue;
			}
			$propRef->setAccessible(true);
			$propRef->setValue($this->object, $value);
		}
	}

	/**
	 * 根据注解获取注入值
	 *
	 * @param \Imi\Aop\Annotation\Inject $annotation
	 * @return mixed
	 */
	public static function getInjectValueByAnnotation($annotation)
	{
		if(isset($annotation['requestInject']) && Coroutine::isIn())
		{
			return RequestContext::getBean($annotation['requestInject']->name, ...$annotation['requestInject']->args);
		}
		else if(isset($annotation['inject']))
		{
			return App::getBean($annotation['inject']->name, ...$annotation['inject']->args);
		}
		else
		{
			return null;
		}
	}

	/**
	 * 获取注入属性的注解们
	 *
	 * @param string $className
	 * @return array
	 */
	public static function getInjectAnnotations($className)
	{
		$aopData = AopParser::getInstance()->getData();
		if(!isset($aopData[$className]))
		{
			return [];
		}
		return $aopData[$className]['property'];
	}

	/**
	 * 获取注入属性的配置们
	 *
	 * @param string $className
	 * @return array
	 */
	public static function getConfigInjects($className)
	{
		// 配置文件注入
		$beanData = BeanParser::getInstance()->getData();
		if(isset($beanData[$className]))
		{
			$beanName = $beanData[$className]['beanName'];
		}
		else
		{
			$beanName = $className;
		}
		$beanProperties = null;
		// 优先从服务器bean配置获取
		try{
			$beanProperties = Config::get('@server.' . RequestContext::getServer()->getName() . '.beans.' . $beanName, null);
		}
		catch(\Throwable $ex)
		{
			$beanProperties = null;
		}
		// 全局bean配置
		if(null === $beanProperties)
		{
			$beanProperties = Config::get('beans.' . $beanName, []);
		}
		return $beanProperties ?? [];
	}

	/**
	 * 获取注入类属性的注解和配置
	 *
	 * @param string $className
	 * @return [$annotations, $configs]
	 */
	public static function getInjects($className)
	{
		$annotations = static::getInjectAnnotations($className);
		$configs = static::getConfigInjects($className);
		foreach(array_keys($configs) as $key)
		{
			if(isset($annotations[$key]))
			{
				unset($annotations[$key]);
			}
		}
		return [$annotations, $configs];
	}

	/**
	 * 判断是否属于当前类的切面
	 * @param array $option
	 * @return boolean
	 */
	private function isAspectCurrentClass($option)
	{
		if(!isset($option['method']))
		{
			return false;
		}
		foreach($option['method'] as $methodName => $methodOption)
		{
			if(!isset($methodOption['pointCut']))
			{
				continue;
			}
			foreach($methodOption['pointCut']->allow as $allowItem)
			{
				if(Imi::checkClassRule($allowItem, $this->refClass->getParentClass()->getName()))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 正常请求
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	private function callOrigin($method, $args, $callback)
	{
		$this->parseBefore($method, $args);
		// 原始方法调用
		$result = call_user_func_array($callback, $args);
		$this->parseAfter($method, $args);
		$this->parseAfterReturning($method, $args, $result);
		return $result;
	}

	/**
	 * 处理前置
	 * @param string $method
	 * @param array $args
	 * @return void
	 */
	private function parseBefore($method, $args)
	{
		$this->doAspect($method, 'before', function($aspectClassName, $methodName) use($method, $args){
			$joinPoint = new JoinPoint('before', $method, $args, $this->object, $this);
			call_user_func([new $aspectClassName, $methodName], $joinPoint);
		});
	}

	/**
	 * 处理后置
	 * @param string $method
	 * @param array $args
	 * @return void
	 */
	private function parseAfter($method, $args)
	{
		$this->doAspect($method, 'after', function($aspectClassName, $methodName) use($method, $args){
			$joinPoint = new JoinPoint('after', $method, $args, $this->object, $this);
			call_user_func([new $aspectClassName, $methodName], $joinPoint);
		});
	}

	/**
	 * 处理返回值
	 * @param string $method
	 * @param array $args
	 * @param mixed $returnValue
	 * @return void
	 */
	private function parseAfterReturning($method, $args, &$returnValue)
	{
		$this->doAspect($method, 'afterReturning', function($aspectClassName, $methodName) use($method, $args, &$returnValue){
			$joinPoint = new AfterReturningJoinPoint('afterReturning', $method, $args, $this->object, $this);
			$joinPoint->setReturnValue($returnValue);
			call_user_func([new $aspectClassName, $methodName], $joinPoint);
			$returnValue = $joinPoint->getReturnValue();
		});
	}

	/**
	 * 处理环绕
	 * @param string $method
	 * @param array $args
	 * @param mixed $returnValue
	 * @return void
	 */
	private function parseAround($method, $args, &$returnValue, $callback)
	{
		$aroundAspectDoList = [];
		$this->doAspect($method, 'around', function($aspectClassName, $methodName) use(&$aroundAspectDoList){
			$aroundAspectDoList[] = [new $aspectClassName, $methodName];
		});
		if(!isset($aroundAspectDoList[0]))
		{
			return false;
		}
		$aroundAspectDoList = array_reverse($aroundAspectDoList);

		$nextJoinPoint = null;
		$nextAroundAspectDo = null;

		foreach($aroundAspectDoList as $aroundAspectDo)
		{
			$joinPoint = new AroundJoinPoint('around', $method, $args, $this->object, $this, (null === $nextJoinPoint ? function() use($method, $args, $callback){
				return $this->callOrigin($method, $args, $callback);
			} : function() use($nextAroundAspectDo, $nextJoinPoint){
				return call_user_func($nextAroundAspectDo, $nextJoinPoint);
			}));
			$nextJoinPoint = $joinPoint;
			$nextAroundAspectDo = $aroundAspectDo;
		}
		$returnValue = call_user_func($nextAroundAspectDo, $nextJoinPoint);
		return true;
	}

	/**
	 * 处理异常
	 * @param string $method
	 * @param array $args
	 * @param \Throwable $throwable
	 * @return void
	 */
	private function parseAfterThrowing($method, $args, \Throwable $throwable)
	{
		$isCancelThrow = false;
		$this->doAspect($method, 'afterThrowing', function($aspectClassName, $methodName, AfterThrowing $annotation) use($method, $args, $throwable, &$isCancelThrow){
			// 验证异常是否捕获
			if(isset($annotation->allow[0]) || isset($annotation->deny[0]))
			{
				$throwableClassName = get_class($throwable);
				if(isset($annotation->allow[0]))
				{
					$allowResult = false;
					foreach($annotation->allow as $rule)
					{
						$allowResult = Imi::checkRuleMatch($rule, $throwableClassName);
						if($allowResult)
						{
							break;
						}
					}
					if(!$allowResult)
					{
						return;
					}
				}
				$denyResult = false;
				foreach($annotation->deny as $rule)
				{
					$denyResult = Imi::checkRuleMatch($rule, $throwableClassName);
					if($denyResult)
					{
						return;
					}
				}
			}
			// 处理
			$joinPoint = new AfterThrowingJoinPoint('afterThrowing', $method, $args, $this->object, $this, $throwable);
			call_user_func([new $aspectClassName, $methodName], $joinPoint);
			if(!$isCancelThrow && $joinPoint->isCancelThrow())
			{
				$isCancelThrow = true;
			}
		});
		// 不取消依旧抛出
		if(!$isCancelThrow)
		{
			throw $throwable;
		}
	}

	/**
	 * 执行切面操作
	 * @param string $method 方法名
	 * @param string $pointType 切入点类型
	 * @param callable $callback 回调
	 * @return void
	 */
	private function doAspect($method, $pointType, $callback)
	{
		$className = $this->refClass->getParentClass()->getName();
		$list = clone static::$aspects[$className];
		foreach($list as $option)
		{
			$aspectClassName = $option['className'];
			foreach($option['method'] as $methodName => $methodOption)
			{
				if(!isset($methodOption[$pointType]) || !$methodOption[$pointType])
				{
					continue;
				}
				$allowResult = false;
				foreach($methodOption['pointCut']->allow as $rule)
				{
					$allowResult = Imi::checkClassMethodRule($rule, $className, $method);
					if($allowResult)
					{
						break;
					}
				}
				if($allowResult)
				{
					$denyResult = false;
					foreach($methodOption['pointCut']->deny as $rule)
					{
						$denyResult = Imi::checkClassMethodRule($rule, $className, $method);
						if($denyResult)
						{
							break;
						}
					}
					if($denyResult)
					{
						continue;
					}
					call_user_func($callback, $aspectClassName, $methodName, $methodOption[$pointType]);
				}
			}
		}
	}

	/**
	 * 获取注入类属性的值
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function getInjectValue($className, $propertyName)
	{
		list($annotations, $configs) = static::getInjects($className);
		if(isset($configs[$propertyName]))
		{
			return $configs[$propertyName];
		}
		else if(isset($annotations[$propertyName]))
		{
			return static::getInjectValueByAnnotation($annotations[$propertyName]);
		}
		else
		{
			return null;
		}
	}
	
}