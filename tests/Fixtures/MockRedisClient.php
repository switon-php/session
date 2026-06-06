<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Fixtures;

use Switon\Redis\ClientInterface;

use function is_array;

/**
 * In-memory Redis stub for Session tests.
 */
class MockRedisClient implements ClientInterface
{
    /** @var array<string, mixed> */
    public array $storage = [];

    /** @var array<string, array<int, array<string, mixed>>> */
    public array $calls = [];

    public function getUri(): ?string
    {
        return null;
    }

    public function getTransient(): static
    {
        return $this;
    }

    public function get(string $key): mixed
    {
        $this->calls['get'][] = ['key' => $key];
        return $this->storage[$key] ?? false;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->calls['set'][] = ['key' => $key, 'value' => $value, 'ttl' => $ttl];
        $this->storage[$key] = $value;
        return true;
    }

    public function expire(string $key, int $ttl): bool
    {
        $this->calls['expire'][] = ['key' => $key, 'ttl' => $ttl];
        return isset($this->storage[$key]);
    }

    public function del(string|array $key): int
    {
        $keys = is_array($key) ? $key : [$key];
        $this->calls['del'][] = ['keys' => $keys];

        $deleted = 0;
        foreach ($keys as $item) {
            if (isset($this->storage[$item])) {
                unset($this->storage[$item]);
                $deleted++;
            }
        }

        return $deleted;
    }
}
