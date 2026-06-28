<?php

declare(strict_types=1);

namespace Switon\Session\Event;

use JsonSerializable;
use Switon\Core\SenderAwareInterface;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Session\AbstractSessionContext;
use Switon\Session\SessionInterface;

/**
 * Event emitted when a session is destroyed.
 *
 * Log category: session lifecycle.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Event\SessionCreate
 */
#[EventLevel(Severity::INFO)]
class SessionDestroy implements JsonSerializable, SenderAwareInterface
{
    /**
     * @param AbstractSessionContext|null $context Session context when destroying current request session.
     * @param string $sessionId Destroyed session ID.
     */
    public function __construct(
        public SessionInterface $sender,
        public ?AbstractSessionContext $context,
        public string                  $sessionId,
    ) {
    }

    public function getSender(): SessionInterface
    {
        return $this->sender;
    }

    /**
     * Returns the destroyed session identifier.
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
