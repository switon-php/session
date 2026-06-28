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
 * Event emitted when a session starts or resumes.
 *
 * Log category: session lifecycle.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Event\SessionEnd
 */
#[EventLevel(Severity::DEBUG)]
class SessionStart implements JsonSerializable, SenderAwareInterface
{
    /**
     * @param AbstractSessionContext $context Session context.
     * @param string $sessionId Started session ID.
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
     * Returns a compact payload describing session startup state.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'isNew' => $this->context->isNew,
        ];
    }
}
