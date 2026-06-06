<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Unit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Switon\Http\CookiesInterface;
use Switon\Session\Bag;
use Switon\Session\SessionInterface;
use Switon\Session\Tests\Fixtures\MockSession;
use Switon\Session\Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BagTest extends TestCase
{
    protected function createMockSession(array $cookieData = []): MockSession
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->any())
            ->method('get')
            ->willReturnCallback(fn ($name) => $cookieData[$name] ?? null);
        $cookies->expects($this->any())
            ->method('set')
            ->willReturnSelf();
        $cookies->expects($this->any())
            ->method('delete')
            ->willReturnSelf();

        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);

        return $this->container->make(MockSession::class);
    }

    protected function createBag(string $name): Bag
    {
        $session = $this->createMockSession();
        $this->container->set(SessionInterface::class, $session);

        $bag = $this->container->make(Bag::class, ['name' => $name]);

        return $bag;
    }

    public function testSetStoresProperty(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key', 'value');

        $this->assertSame('value', $bag->get('key'));
    }

    public function testGetReturnsProperty(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key', 'value');

        $this->assertSame('value', $bag->get('key'));
    }

    public function testGetReturnsDefaultWhenPropertyDoesNotExist(): void
    {
        $bag = $this->createBag('test_bag');

        $this->assertNull($bag->get('nonexistent'));
        $this->assertSame('default', $bag->get('nonexistent', 'default'));
    }

    public function testHasReturnsTrueWhenPropertyExists(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key', 'value');

        $this->assertTrue($bag->has('key'));
    }

    public function testHasReturnsFalseWhenPropertyDoesNotExist(): void
    {
        $bag = $this->createBag('test_bag');

        $this->assertFalse($bag->has('nonexistent'));
    }

    public function testRemoveDeletesProperty(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key', 'value');
        $bag->remove('key');

        $this->assertFalse($bag->has('key'));
    }

    public function testAllReturnsAllProperties(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key1', 'value1');
        $bag->set('key2', 'value2');

        $all = $bag->all();

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $all);
    }

    public function testDestroyRemovesBagData(): void
    {
        $bag = $this->createBag('test_bag');

        $bag->set('key', 'value');
        $bag->destroy();

        $all = $bag->all();
        $this->assertEmpty($all);
    }
}
