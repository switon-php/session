<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Unit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Switon\Core\AppInterface;
use Switon\Redis\ClientInterface;
use Switon\Session\Session;
use Switon\Session\Tests\Fixtures\MockRedisClient;
use Switon\Session\Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SessionTest extends TestCase
{
    protected MockRedisClient $mockRedis;
    protected AppInterface&MockObject $mockApp;
    protected Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRedis = new MockRedisClient();
        $this->mockApp = $this->createMock(AppInterface::class);
        $this->mockApp->method('id')->willReturn('test-app');

        $this->container->set(ClientInterface::class, $this->mockRedis);
        $this->container->set(AppInterface::class, $this->mockApp);

        $this->session = $this->container->make(Session::class);
    }

    public function testDoReadReturnsStringValueFromRedis(): void
    {
        $this->mockRedis->storage['cache:test-app:session:sid-1'] = '{"k":"v"}';

        $value = $this->session->doRead('sid-1');

        $this->assertSame('{"k":"v"}', $value);
        $this->assertSame('cache:test-app:session:sid-1', $this->mockRedis->calls['get'][0]['key']);
    }

    public function testDoReadReturnsEmptyStringWhenRedisValueIsNotString(): void
    {
        $this->mockRedis->storage['cache:test-app:session:sid-2'] = 123;

        $value = $this->session->doRead('sid-2');

        $this->assertSame('', $value);
    }

    public function testDoWriteUsesDefaultPrefixAndTtl(): void
    {
        $ok = $this->session->doWrite('sid-3', '{"role":"admin"}', 180);

        $this->assertTrue($ok);
        $this->assertSame('cache:test-app:session:sid-3', $this->mockRedis->calls['set'][0]['key']);
        $this->assertSame('{"role":"admin"}', $this->mockRedis->calls['set'][0]['value']);
        $this->assertSame(180, $this->mockRedis->calls['set'][0]['ttl']);
    }

    public function testDoWriteUsesCustomPrefixWhenConfigured(): void
    {
        $this->injector->inject($this->session, ['prefix' => 'custom:session:']);

        $this->session->doWrite('sid-4', '{"ok":true}', 300);

        $this->assertSame('custom:session:sid-4', $this->mockRedis->calls['set'][0]['key']);
    }

    public function testDoTouchAndDestroyUseSameComputedKey(): void
    {
        $this->mockRedis->storage['cache:test-app:session:sid-5'] = 'payload';

        $this->assertTrue($this->session->doTouch('sid-5', 60));
        $this->session->doDestroy('sid-5');

        $this->assertSame('cache:test-app:session:sid-5', $this->mockRedis->calls['expire'][0]['key']);
        $this->assertSame(['cache:test-app:session:sid-5'], $this->mockRedis->calls['del'][0]['keys']);
    }

    public function testWriteUsesContextTtlWhenOverridden(): void
    {
        $this->session->setTtl(90);

        $this->session->write('sid-6', ['u' => 1]);

        $call = $this->mockRedis->calls['set'][0];
        $this->assertSame('cache:test-app:session:sid-6', $call['key']);
        $this->assertSame(90, $call['ttl']);
        $this->assertSame(['u' => 1], json_decode((string)$call['value'], true));
    }

    public function testDoGcDoesNotTouchRedis(): void
    {
        $this->session->doGc(30);

        $this->assertSame([], $this->mockRedis->calls);
    }

    public function testDoWriteReturnsFalseWhenRedisSetFails(): void
    {
        $failingRedis = new class () extends MockRedisClient {
            public function set(string $key, mixed $value, ?int $ttl = null): bool
            {
                $this->calls['set'][] = ['key' => $key, 'value' => $value, 'ttl' => $ttl];
                return false;
            }
        };

        $this->container->replace(ClientInterface::class, $failingRedis);
        $session = $this->container->make(Session::class);

        $this->assertFalse($session->doWrite('sid-fail', '{"k":"v"}', 120));
        $this->assertSame('cache:test-app:session:sid-fail', $failingRedis->calls['set'][0]['key']);
    }

    public function testDoTouchReturnsFalseWhenRedisExpireFails(): void
    {
        $failingRedis = new class () extends MockRedisClient {
            public function expire(string $key, int $seconds): bool
            {
                $this->calls['expire'][] = ['key' => $key, 'seconds' => $seconds];
                return false;
            }
        };

        $this->container->replace(ClientInterface::class, $failingRedis);
        $session = $this->container->make(Session::class);

        $this->assertFalse($session->doTouch('sid-touch-fail', 30));
        $this->assertSame('cache:test-app:session:sid-touch-fail', $failingRedis->calls['expire'][0]['key']);
    }
}
