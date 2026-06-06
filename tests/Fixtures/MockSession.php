<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Fixtures;

use Switon\Session\AbstractSession;

class MockSession extends AbstractSession
{
    protected array $storage = [];
    protected array $writtenSessions = [];
    protected array $touchedSessions = [];
    protected array $destroyedSessions = [];

    public function doRead(string $sessionId): string
    {
        return $this->storage[$sessionId] ?? '';
    }

    public function doWrite(string $sessionId, string $data, int $ttl): bool
    {
        $this->storage[$sessionId] = $data;
        $this->writtenSessions[$sessionId] = ['data' => $data, 'ttl' => $ttl];
        return true;
    }

    public function doTouch(string $sessionId, int $ttl): bool
    {
        $this->touchedSessions[$sessionId] = $ttl;
        return true;
    }

    public function doDestroy(string $sessionId): void
    {
        unset($this->storage[$sessionId]);
        $this->destroyedSessions[] = $sessionId;
    }

    public function doGc(int $ttl): void
    {
    }

    public function getWrittenSessions(): array
    {
        return $this->writtenSessions;
    }

    public function getTouchedSessions(): array
    {
        return $this->touchedSessions;
    }

    public function getDestroyedSessions(): array
    {
        return $this->destroyedSessions;
    }

    public function clearStorage(): void
    {
        $this->storage = [];
        $this->writtenSessions = [];
        $this->touchedSessions = [];
        $this->destroyedSessions = [];
    }

    public function setStorageData(string $sessionId, string $data): void
    {
        $this->storage[$sessionId] = $data;
    }
}
