<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Unit;

use Switon\Core\ContainerInterface;
use Switon\Eventing\ListenerProviderInterface;
use Switon\Session\ServiceProvider;
use Switon\Session\SessionInterface;
use Switon\Testing\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testRegisterDoesNothing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('set');

        $provider = new ServiceProvider();
        $provider->register($container);

        $this->assertTrue(true);
    }

    public function testBootRegistersSessionListener(): void
    {
        $listenerProvider = $this->createMock(ListenerProviderInterface::class);
        $listenerProvider->expects($this->once())
            ->method('register')
            ->with(SessionInterface::class);

        /** @var ServiceProvider $provider */
        $provider = $this->make(ServiceProvider::class, [
            'listenerProvider' => $listenerProvider,
        ]);

        $provider->boot();
    }
}
