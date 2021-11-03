<?php

declare(strict_types=1);

namespace Imi\Redis;

use InvalidArgumentException;
use function sha1;

abstract class RedisLua
{
    /** @var string[] */
    protected static     $luaSha1;

    private RedisHandler $redis;

    /**
     * @param string|null $poolName
     * @return $this
     */
    protected function use(?string $poolName = null):? self
    {
        $redis = RedisManager::getInstance($poolName);
        if (empty($redis)) {
            return null;
        }
        return new static($redis);
    }

    public function __construct(RedisHandler $redis)
    {
        $this->redis = $redis;
    }

    public function getName(): string
    {
        return static::class;
    }

    public function getCode(): string
    {
        return $this->luaCode();
    }

    public function getSha1(): string
    {
        $name = $this->getName();
        if (isset(self::$luaSha1[$name])) {
            return self::$luaSha1[$name];
        }
        return self::$luaSha1[$name] = sha1($this->luaCode());
    }

    public function loaded(): bool
    {
        return $this->redis->script('exists', $this->getSha1())[0] > 0;
    }

    public function load(): void
    {
        if (!$this->loaded()) {
            $this->redis->clearLastError();
            $result = $this->redis->script('load', $this->luaCode());
            if (false === $result) {
                throw new \LogicException($this->redis->getLastError());
            }
            if ($this->getSha1() !== $result) {
                throw new \LogicException('load lua fail');
            }
        }
    }

    abstract protected function numKeys(): int;

    abstract protected function luaCode(): string;

    /**
     * @param array              $keys
     * @param array              $argv
     * @return mixed
     */
    public function __invoke(array $keys, array $argv = [])
    {
        if (count($keys) !== $this->numKeys()) {
            throw new InvalidArgumentException('Keys length error.');
        }

        $isRetry = false;
        RETRY_EVAL:
        $this->redis->clearLastError();
        $result = $this->redis->evalSha($this->getSha1(), array_merge($keys, $argv), $this->numKeys());
        if (false === $result
            && !is_null($error = $this->redis->getLastError())
            && str_starts_with($error, 'NOSCRIPT')
        ) {
            if (false === $isRetry) {
                $isRetry = true;
                $this->load();
                goto RETRY_EVAL;
            } else {
                throw new \LogicException($this->redis->getLastError());
            }
        }

        return $result;
    }
}
