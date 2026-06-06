<?php

declare(strict_types=1);

namespace Switon\Session;

/**
 * Defines the session API for reading, writing, and managing session lifecycle state.
 *
 * Use when storing user-scoped data across HTTP requests.
 *
 * Road-signs:
 * - all()/get()/set()/remove() for session data
 * - destroy() for current or arbitrary session invalidation
 * - read()/write() for direct storage access by id
 *
 * @see \Switon\Session\AbstractSession
 * @see \Switon\Session\Session
 * @see \Switon\Session\BagInterface
 * @see \Switon\Session\Exception
 */
interface SessionInterface
{
    /**
     * Returns all session data.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Gets a session value by name.
     *
     * @param string $name Session key.
     * @param mixed $default Default value when key is missing.
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Sets a session value.
     *
     * @param string $name Session key.
     * @param mixed $value Value to store.
     *
     * @return static
     */
    public function set(string $name, mixed $value): static;

    /**
     * Checks if a session key exists.
     *
     * Uses <code>isset()</code> semantics, so keys with <code>null</code> values return false.
     */
    public function has(string $name): bool;

    /**
     * Removes a session value.
     *
     * @param string $name Session key.
     *
     * @return static
     */
    public function remove(string $name): static;

    /**
     * Destroys the session.
     *
     * @param string|null $sessionId Null destroys current session; non-null destroys the specified session ID.
     *
     * @return static
     */
    public function destroy(?string $sessionId = null): static;

    /**
     * Returns the session ID.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Sets the session ID.
     *
     * @param string $id Session ID.
     *
     * @return static
     */
    public function setId(string $id): static;

    /**
     * Returns the session cookie name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the session TTL in seconds.
     *
     * @return int
     */
    public function getTtl(): int;

    /**
     * Sets the session TTL in seconds.
     *
     * @param int $ttl Time-to-live in seconds.
     *
     * @return static
     */
    public function setTtl(int $ttl): static;

    /**
     * Reads session data directly from storage.
     *
     * @param string $sessionId Session ID.
     *
     * @return array<string, mixed>
     */
    public function read(string $sessionId): array;

    /**
     * Writes session data directly to storage.
     *
     * @param string $sessionId Session ID.
     * @param array<string, mixed> $data Session data.
     *
     * @return static
     */
    public function write(string $sessionId, array $data): static;
}
