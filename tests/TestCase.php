<?php

declare(strict_types=1);

namespace Switon\Session\Tests;

use Switon\Core\Attribute\Autowired;
use Switon\Core\ContextManagerInterface;
use Switon\Http\CookiesInterface;
use Switon\Testing\TestCase as BaseTestCase;

/**
 * Base test case for Session tests.
 *
 * Provides common functionality for all Session tests using Container (as in real applications).
 * All dependencies are injected through Container's autowiring.
 */
abstract class TestCase extends BaseTestCase
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected CookiesInterface $cookies;

    protected function setUpContainer(): void
    {
        parent::setUpContainer();

        // ContextManager is already registered in Switon\Testing\Container
        // Create and register cookies mock
        $this->container->set(CookiesInterface::class, $this->createMock(CookiesInterface::class));

        // Property autowiring is automatically performed by parent::setUp()
    }
}
