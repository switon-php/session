<?php

declare(strict_types=1);

namespace Switon\Session;

use Switon\Core\Attribute\Autowired;
use Switon\Core\ContainerInterface;
use Switon\Core\ServiceProviderInterface;
use Switon\Eventing\ListenerProviderInterface;

/**
 * Registers session listeners for framework bootstrap.
 *
 * Use when the framework must register `SessionInterface` event listeners during boot.
 *
 * Road-signs:
 * - register() relies on namespace auto-mapping
 * - boot() registers SessionInterface listeners
 *
 * @see \Switon\Core\ServiceProviderInterface
 * @see \Switon\Session\SessionInterface
 * @see \Switon\Eventing\ListenerProviderInterface
 */
class ServiceProvider implements ServiceProviderInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    /** {@inheritDoc} */
    public function register(ContainerInterface $container): void
    {
    }

    /** {@inheritDoc} */
    public function boot(): void
    {
        $this->listenerProvider->register(SessionInterface::class);
    }
}
