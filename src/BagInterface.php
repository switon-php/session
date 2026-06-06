<?php

declare(strict_types=1);

namespace Switon\Session;

/**
 * Defines namespaced bag operations on top of session storage.
 *
 * Use when isolating component-specific keys under one session namespace.
 *
 * Road-signs:
 * - all()/get()/set() for bag values
 * - destroy() for namespace reset
 * - remove() for one property
 *
 * @see \Switon\Session\Bag
 * @see \Switon\Session\SessionInterface
 */
interface BagInterface
{
    /**
     * Removes the entire bag from session.
     */
    public function destroy(): void;

    /**
     * Sets a property value.
     *
     * @param string $property Bag property key.
     * @param mixed $value Property value.
     *
     * @return static
     */
    public function set(string $property, mixed $value): static;

    /**
     * Returns all bag data.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Gets a property value.
     *
     * @param string $property Bag property key.
     * @param mixed $default Default value when key is missing.
     */
    public function get(string $property, mixed $default = null): mixed;

    /**
     * Checks if a property exists.
     *
     * @param string $property Bag property key.
     */
    public function has(string $property): bool;

    /**
     * Removes a property.
     *
     * @param string $property Bag property key.
     */
    public function remove(string $property): void;
}
