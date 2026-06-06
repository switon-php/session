<?php

declare(strict_types=1);

namespace Switon\Session;

/**
 * Stores per-request mutable session state for context-aware session implementations.
 *
 * Use when one request/coroutine needs isolated in-memory session state before persistence.
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\SessionInterface
 */
class AbstractSessionContext
{
    /** Per-request TTL override; null uses default TTL. */
    public ?int $ttl = null;

    /** Whether session startup has completed for this request. */
    public bool $started = false;

    /** Whether the session was newly created during this request. */
    public ?bool $isNew = null;

    /** Whether session data changed after load/start. */
    public bool $isDirty = false;

    /** Current session ID. */
    public ?string $sessionId = null;

    /** @var array<string, mixed>|null Session key-value data. */
    public ?array $data = null;
}
