<?php

declare(strict_types=1);

namespace Switon\Session;

use ArrayAccess;
use JsonSerializable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Switon\Core\Attribute\Autowired;
use Switon\Core\ContextAware;
use Switon\Core\ContextManagerInterface;
use Switon\Core\Exception\JsonException;
use Switon\Core\Json;
use Switon\Core\RandomInterface;
use Switon\Eventing\Attribute\EventListener;
use Switon\Http\CookiesInterface;
use Switon\Http\Event\HeadersSending;
use Switon\Session\Event\SessionCreate;
use Switon\Session\Event\SessionDestroy;
use Switon\Session\Event\SessionEnd;
use Switon\Session\Event\SessionStart;
use Switon\Session\Event\SessionUnserializeFailed;
use Switon\Session\Event\SessionUpdate;

use function is_array;
use function time;

/**
 * Provides shared session lifecycle logic, cookie integration, and context-isolated state management.
 *
 * Use when implementing custom session storage backends by overriding storage hooks.
 *
 * @see \Switon\Session\SessionInterface
 * @see \Switon\Http\CookiesInterface
 * @see \Switon\Http\Event\HeadersSending
 * @see \Switon\Session\Event\SessionStart
 *
 * @implements ArrayAccess<string, mixed>
 *
 * @see \Switon\Session\Event\SessionUnserializeFailed
 */
abstract class AbstractSession implements SessionInterface, ContextAware, ArrayAccess, JsonSerializable
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RandomInterface $random;

    #[Autowired] protected CookiesInterface $cookies;

    #[Autowired] protected int $ttl = 3600;
    #[Autowired] protected int $lazy = 60;
    /** @noinspection SpellCheckingInspection */
    #[Autowired] protected string $name = 'PHPSESSID';

    /** @var array{expire: int, path: string, domain: string, secure: bool, httponly: bool} Session cookie parameters */
    protected array $params = ['expire' => 0, 'path' => '', 'domain' => '', 'secure' => false, 'httponly' => true];

    /**
     * @param array{expire?: int, path?: string, domain?: string, secure?: bool, httponly?: bool} $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params + $this->params;
    }

    public function getContext(): AbstractSessionContext
    {
        return $this->contextManager->getContext($this);
    }

    public function all(): array
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->data;
    }

    protected function start(): void
    {
        $context = $this->getContext();

        if ($context->started) {
            return;
        }

        if (($sessionId = $this->cookies->get($this->name)) && ($str = $this->doRead($sessionId))) {
            $context->isNew = false;

            if (is_array($data = $this->unserialize($str))) {
                $context->data = $data;
            } else {
                $context->data = [];
                $this->eventDispatcher->dispatch(new SessionUnserializeFailed($this, $sessionId));
            }
        } else {
            $sessionId = $this->generateSessionId();
            $context->isNew = true;
            $context->data = [];
        }

        $context->sessionId = $sessionId;
        $context->started = true;

        $this->eventDispatcher->dispatch(new SessionStart($this, $context, $sessionId));
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[EventListener] public function onResponseHeadersSending(HeadersSending $event): void
    {
        $context = $this->getContext();

        if (!$context->started) {
            return;
        }

        $sessionId = $context->sessionId;

        $this->eventDispatcher->dispatch(new SessionEnd($this, $context, $sessionId));

        if ($context->isNew) {
            if (!$context->data) {
                return;
            }

            $params = $this->params;
            $expire = $params['expire'] ? time() + $params['expire'] : 0;

            $this->cookies->set(
                $this->name,
                $context->sessionId,
                $expire,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );

            $this->eventDispatcher->dispatch(new SessionCreate($this, $context, $sessionId));
        } elseif ($context->isDirty) {
            // Session data changed, continue to write
        } elseif ($this->lazy) {
            if (isset($context->data['__T']) && time() - $context->data['__T'] < $this->lazy) {
                return;
            }
        } elseif ($this->doTouch($context->sessionId, $context->ttl ?? $this->ttl)) {
            return;
        }

        $this->eventDispatcher->dispatch(new SessionUpdate($this, $context, $sessionId));

        if ($this->lazy) {
            $context->data['__T'] = time();
        }

        $data = $this->serialize($context->data);
        $this->doWrite($context->sessionId, $data, $context->ttl ?? $this->ttl);
    }

    public function destroy(?string $sessionId = null): static
    {
        if ($sessionId) {
            $this->eventDispatcher->dispatch(new SessionDestroy($this, null, $sessionId));
            $this->doDestroy($sessionId);
        } else {
            $context = $this->getContext();

            if (!$context->started) {
                $this->start();
            }

            $sessionId = $context->sessionId;
            $this->eventDispatcher->dispatch(new SessionDestroy($this, $context, $sessionId));

            $context->started = false;
            $context->isDirty = false;
            $context->sessionId = null;
            $context->data = null;
            $this->doDestroy($sessionId);

            $name = $this->name;
            $params = $this->params;
            $this->cookies->delete($name, $params['path'], $params['domain']);
        }

        return $this;
    }

    abstract public function doTouch(string $sessionId, int $ttl): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function serialize(array $data): string
    {
        return Json::stringify($data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function unserialize(string $data): ?array
    {
        try {
            return Json::parse($data);
        } catch (JsonException $e) {
            return null;
        }
    }

    abstract public function doRead(string $sessionId): string;

    abstract public function doWrite(string $sessionId, string $data, int $ttl): bool;

    abstract public function doGc(int $ttl): void;

    protected function generateSessionId(): string
    {
        return $this->random->chars(32, 36);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->data[$name] ?? $default;
    }

    public function set(string $name, mixed $value): static
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        $context->isDirty = true;
        $context->data[$name] = $value;

        return $this;
    }

    public function has(string $name): bool
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return isset($context->data[$name]);
    }

    public function remove(string $name): static
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        $context->isDirty = true;
        unset($context->data[$name]);

        return $this;
    }

    public function getId(): string
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        return $context->sessionId;
    }

    public function setId(string $id): static
    {
        $context = $this->getContext();

        if (!$context->started) {
            $this->start();
        }

        if ($context->sessionId === $id) {
            return $this;
        }

        $context->sessionId = $id;
        $context->isNew = true;
        $context->isDirty = true;

        return $this;
    }

    public function getTtl(): int
    {
        $context = $this->getContext();

        return $context->ttl ?? $this->ttl;
    }

    public function setTtl(int $ttl): static
    {
        $context = $this->getContext();

        $context->ttl = $ttl;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function doDestroy(string $sessionId): void;

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $sessionId): array
    {
        $session = $this->doRead($sessionId);
        if (!$session) {
            return [];
        }

        return $this->unserialize($session) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function write(string $sessionId, array $data): static
    {
        $context = $this->getContext();

        $session = $this->serialize($data);

        $this->doWrite($sessionId, $session, $context->ttl ?? $this->ttl);

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
