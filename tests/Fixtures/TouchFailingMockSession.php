<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Fixtures;

class TouchFailingMockSession extends MockSession
{
    public function doTouch(string $sessionId, int $ttl): bool
    {
        $this->touchedSessions[$sessionId] = $ttl;
        return false;
    }
}
