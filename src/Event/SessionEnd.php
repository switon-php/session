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
 * Event emitted when a started session reaches response end handling.
 *
 * Log category: `switon.session.end`
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Event\SessionStart
 * @see \Switon\Session\Event\SessionUpdate
 */
#[EventLevel(Severity::DEBUG)]
class SessionEnd implements JsonSerializable, SenderAwareInterface
{
    /**
     * @param AbstractSessionContext $context Session context.
     * @param string $sessionId Session ID at end stage.
     */
    public function __construct(
        public SessionInterface $sender,
        public AbstractSessionContext $context,
        public string                 $sessionId,
    ) {
    }

    public function getSender(): SessionInterface
    {
        return $this->sender;
    }

    /**
     * Returns the session identifier observed at response-end handling.
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
