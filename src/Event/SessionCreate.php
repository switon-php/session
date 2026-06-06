<?php

declare(strict_types=1);

namespace Switon\Session\Event;

use JsonSerializable;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Session\AbstractSessionContext;
use Switon\Session\SessionInterface;

/**
 * Event emitted when a new session is created.
 *
 * Log category: session lifecycle.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Event\SessionDestroy
 */
#[EventLevel(Severity::INFO)]
class SessionCreate implements JsonSerializable
{
    /**
     * @param SessionInterface $session Session component.
     * @param AbstractSessionContext $context Session context.
     * @param string $sessionId Created session ID.
     */
    public function __construct(
        public SessionInterface       $session,
        public AbstractSessionContext $context,
        public string                 $sessionId,
    ) {
    }

    /**
     * Returns the created session identifier.
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
