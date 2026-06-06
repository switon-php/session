<?php

declare(strict_types=1);

namespace Switon\Session;

use Switon\Core\Attribute\Autowired;

/**
 * Implements a namespaced session bag backed by SessionInterface.
 *
 * Use when one component needs grouped bag properties under a single session key.
 *
 * Guidance: bag values live under one top-level session key configured by `$name`.
 *
 * @see \Switon\Session\BagInterface
 * @see \Switon\Session\SessionInterface
 */
class Bag implements BagInterface
{
    #[Autowired] protected SessionInterface $session;

    #[Autowired] protected string $name;

    /**
     * {@inheritDoc}
     */
    public function destroy(): void
    {
        $this->session->remove($this->name);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $property, mixed $value): static
    {
        $data = $this->session->get($this->name, []);
        $data[$property] = $value;

        $this->session->set($this->name, $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->session->get($this->name, []);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $property, mixed $default = null): mixed
    {
        $data = $this->session->get($this->name, []);

        return $data[$property] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $property): bool
    {
        $data = $this->session->get($this->name, []);

        return isset($data[$property]);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $property): void
    {
        $data = $this->session->get($this->name, []);
        unset($data[$property]);

        $this->session->set($this->name, $data);
    }
}
