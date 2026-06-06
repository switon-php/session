<?php

declare(strict_types=1);

namespace Switon\Session;

use Switon\Core\AppInterface;
use Switon\Core\Attribute\Autowired;
use Switon\Redis\ClientInterface;

use function is_string;
use function sprintf;

/**
 * Stores session payloads in Redis.
 *
 * Use when the application needs shared session storage across processes or nodes.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\SessionInterface
 * @see \Switon\Session\Exception
 * @see \Switon\Redis\ClientInterface
 * @see \Switon\Session\Event\SessionStart
 * @see \Switon\Session\Event\SessionEnd
 * @see \Switon\Session\Event\SessionCreate
 * @see \Switon\Session\Event\SessionDestroy
 * @see \Switon\Session\Event\SessionUpdate
 * @see \Switon\Session\Event\SessionUnserializeFailed
 */
class Session extends AbstractSession
{
    #[Autowired] protected AppInterface $app;
    #[Autowired] protected ClientInterface $redisCache;

    #[Autowired] protected ?string $prefix;

    protected function getKey(string $sessionId): string
    {
        return ($this->prefix ?? sprintf('cache:%s:session:', $this->app->id())) . $sessionId;
    }

    public function doRead(string $sessionId): string
    {
        $data = $this->redisCache->get($this->getKey($sessionId));
        return is_string($data) ? $data : '';
    }

    public function doWrite(string $sessionId, string $data, int $ttl): bool
    {
        return $this->redisCache->set($this->getKey($sessionId), $data, $ttl);
    }

    public function doTouch(string $sessionId, int $ttl): bool
    {
        return $this->redisCache->expire($this->getKey($sessionId), $ttl);
    }

    public function doDestroy(string $sessionId): void
    {
        $this->redisCache->del($this->getKey($sessionId));
    }

    public function doGc(int $ttl): void
    {
        // Redis handles expiration automatically, no garbage collection needed
    }
}
