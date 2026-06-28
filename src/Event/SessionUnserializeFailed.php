<?php

declare(strict_types=1);

namespace Switon\Session\Event;

use JsonSerializable;
use Switon\Core\SenderAwareInterface;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Session\SessionInterface;

/**
 * Event emitted when stored session payload cannot be unserialized.
 *
 * Log category: session lifecycle.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Exception
 */
#[EventLevel(Severity::ERROR)]
class SessionUnserializeFailed implements JsonSerializable, SenderAwareInterface
{
    /**
     * @param string $sessionId Failed session ID.
     */
    public function __construct(
        public SessionInterface $sender,
        public string           $sessionId,
    ) {
    }

    public function getSender(): SessionInterface
    {
        return $this->sender;
    }

    /**
     * Returns the failed session identifier.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'sessionId' => $this->sessionId,
        ];
    }
}
