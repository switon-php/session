<?php

declare(strict_types=1);

namespace Switon\Session\Tests\Unit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\EventDispatcher\EventDispatcherInterface;
use Switon\Http\CookiesInterface;
use Switon\Http\Event\HeadersSending;
use Switon\Http\ResponseInterface;
use Switon\Session\AbstractSessionContext;
use Switon\Session\Event\SessionCreate;
use Switon\Session\Event\SessionEnd;
use Switon\Session\Event\SessionStart;
use Switon\Session\Event\SessionUnserializeFailed;
use Switon\Session\Event\SessionUpdate;
use Switon\Session\Tests\Fixtures\MockSession;
use Switon\Session\Tests\Fixtures\TouchFailingMockSession;
use Switon\Session\Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AbstractSessionTest extends TestCase
{
    protected function createMockSession(array $cookieData = [], array $parameters = []): MockSession
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

        // Remove existing registration to allow replacement
        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);

        return $this->container->make(MockSession::class, $parameters);
    }

    public function testGetContextReturnsAbstractSessionContext(): void
    {
        $session = $this->createMockSession();

        $context = $session->getContext();

        $this->assertInstanceOf(AbstractSessionContext::class, $context);
    }

    public function testAllStartsSessionIfNotStarted(): void
    {
        $session = $this->createMockSession();

        $data = $session->all();

        $context = $session->getContext();
        $this->assertTrue($context->started);
        $this->assertIsArray($data);
    }

    public function testAllReturnsSessionData(): void
    {
        $sessionId = 'test_session_123';
        $sessionData = ['key1' => 'value1', 'key2' => 'value2'];
        $serializedData = json_encode($sessionData);

        $session = $this->createMockSession(['PHPSESSID' => $sessionId]);
        $session->write($sessionId, $sessionData);

        $data = $session->all();

        $this->assertSame($sessionData, $data);
    }

    public function testGetReturnsValue(): void
    {
        $session = $this->createMockSession();

        $session->set('user_id', 123);

        $this->assertSame(123, $session->get('user_id'));
    }

    public function testGetReturnsDefaultWhenKeyDoesNotExist(): void
    {
        $session = $this->createMockSession();

        $this->assertNull($session->get('nonexistent'));
        $this->assertSame('default', $session->get('nonexistent', 'default'));
    }

    public function testSetStoresValue(): void
    {
        $session = $this->createMockSession();

        $session->set('name', 'John');

        $this->assertSame('John', $session->get('name'));
    }

    public function testSetMarksContextAsDirty(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');

        $context = $session->getContext();
        $this->assertTrue($context->isDirty);
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $session = $this->createMockSession();

        $session->set('exists', 'value');

        $this->assertTrue($session->has('exists'));
    }

    public function testHasReturnsFalseWhenKeyDoesNotExist(): void
    {
        $session = $this->createMockSession();

        $this->assertFalse($session->has('nonexistent'));
    }

    public function testRemoveDeletesKey(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');
        $session->remove('key');

        $this->assertFalse($session->has('key'));
    }

    public function testRemoveMarksContextAsDirty(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');
        $session->remove('key');

        $context = $session->getContext();
        $this->assertTrue($context->isDirty);
    }

    public function testGetIdReturnsSessionId(): void
    {
        $session = $this->createMockSession();

        $sessionId = $session->getId();

        $this->assertNotEmpty($sessionId);
        $this->assertIsString($sessionId);
    }

    public function testSetIdSetsSessionId(): void
    {
        $session = $this->createMockSession();
        $newId = 'custom_session_id';

        $session->setId($newId);

        $this->assertSame($newId, $session->getId());
    }

    public function testSetIdMarksSessionAsNewAndDirty(): void
    {
        $sessionId = 'existing_session_123';
        $sessionData = ['user_id' => 1001];
        $session = $this->createMockSession(['PHPSESSID' => $sessionId]);
        $session->write($sessionId, $sessionData);

        $newId = 'rotated_session_456';
        $session->setId($newId);

        $context = $session->getContext();
        $this->assertSame($newId, $context->sessionId);
        $this->assertTrue($context->isNew);
        $this->assertTrue($context->isDirty);
    }

    public function testGetNameReturnsDefaultName(): void
    {
        $session = $this->createMockSession();

        $this->assertSame('PHPSESSID', $session->getName());
    }

    public function testGetTtlReturnsDefaultTtl(): void
    {
        $session = $this->createMockSession();

        $this->assertSame(3600, $session->getTtl());
    }

    public function testSetTtlSetsContextTtl(): void
    {
        $session = $this->createMockSession();

        $session->setTtl(7200);

        $this->assertSame(7200, $session->getTtl());
    }

    public function testDestroyDestroysCurrentSession(): void
    {
        $session = $this->createMockSession();
        $sessionId = $session->getId();
        $session->set('key', 'value');

        $session->destroy();

        $context = $session->getContext();
        $this->assertFalse($context->started);
        $this->assertNull($context->sessionId);
        $this->assertNull($context->data);
        $this->assertContains($sessionId, $session->getDestroyedSessions());
    }

    public function testDestroyAllowsFreshSessionToStartAgain(): void
    {
        $session = $this->createMockSession();
        $originalId = $session->getId();
        $session->set('key', 'value');

        $session->destroy();

        $this->assertSame('fallback', $session->get('missing', 'fallback'));

        $context = $session->getContext();
        $this->assertTrue($context->started);
        $this->assertNotNull($context->sessionId);
        $this->assertNotSame($originalId, $context->sessionId);
        $this->assertSame([], $context->data);
    }

    public function testDestroyWithSessionIdDestroysSpecificSession(): void
    {
        $session = $this->createMockSession();
        $targetSessionId = 'target_session_id';

        $session->destroy($targetSessionId);

        $this->assertContains($targetSessionId, $session->getDestroyedSessions());
    }

    public function testArrayAccessOffsetExists(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');

        $this->assertTrue(isset($session['key']));
        $this->assertFalse(isset($session['nonexistent']));
    }

    public function testArrayAccessOffsetGet(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');

        $this->assertSame('value', $session['key']);
    }

    public function testArrayAccessOffsetSet(): void
    {
        $session = $this->createMockSession();

        $session['key'] = 'value';

        $this->assertSame('value', $session->get('key'));
    }

    public function testArrayAccessOffsetUnset(): void
    {
        $session = $this->createMockSession();

        $session->set('key', 'value');
        unset($session['key']);

        $this->assertFalse($session->has('key'));
    }

    public function testJsonSerializeReturnsAllData(): void
    {
        $session = $this->createMockSession();
        $session->set('key1', 'value1');
        $session->set('key2', 'value2');

        $data = $session->jsonSerialize();

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $data);
    }

    public function testSerializeAndUnserialize(): void
    {
        $session = $this->createMockSession();
        $data = ['key' => 'value', 'number' => 123, 'nested' => ['a' => 1, 'b' => 2]];

        $serialized = $session->serialize($data);
        $unserialized = $session->unserialize($serialized);

        $this->assertSame($data, $unserialized);
        $this->assertIsString($serialized);
        $this->assertStringContainsString('"key":"value"', $serialized);
    }

    public function testStartDispatchesSessionStartEvent(): void
    {
        // Set up mock EventDispatcher before creating session
        $sessionStartDispatched = false;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$sessionStartDispatched) {
                if ($event instanceof SessionStart) {
                    $sessionStartDispatched = true;
                }
                return $event;
            });

        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->createMockSession();
        $session->all(); // Triggers start()

        $this->assertTrue($sessionStartDispatched, 'SessionStart event should be dispatched');
    }

    public function testStartWithExistingCookieLoadsSessionData(): void
    {
        $sessionId = 'existing_session_123';
        $sessionData = ['key' => 'value'];

        $session = $this->createMockSession(['PHPSESSID' => $sessionId]);
        $session->write($sessionId, $sessionData);

        // Create new session instance to simulate reading from storage
        $session2 = $this->createMockSession(['PHPSESSID' => $sessionId]);
        $session2->write($sessionId, $sessionData);

        $data = $session2->all();
        $context = $session2->getContext();

        $this->assertFalse($context->isNew);
    }

    public function testStartWithoutCookieCreatesNewSession(): void
    {
        $session = $this->createMockSession();

        $session->all(); // Triggers start()

        $context = $session->getContext();
        $this->assertTrue($context->isNew);
    }

    public function testUnserializeFailedDispatchesEvent(): void
    {
        $sessionId = 'test_session_with_invalid_json';
        $invalidJson = 'invalid json string';

        // Set up mock EventDispatcher before creating session
        $unserializeFailedDispatched = false;
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$unserializeFailedDispatched) {
                if ($event instanceof SessionUnserializeFailed) {
                    $unserializeFailedDispatched = true;
                }
                return $event;
            });

        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->createMockSession(['PHPSESSID' => $sessionId]);
        $session->setStorageData($sessionId, $invalidJson);

        $session->all(); // Triggers start() which will attempt to unserialize the invalid JSON

        $this->assertTrue($unserializeFailedDispatched, 'SessionUnserializeFailed event should be dispatched');
        $context = $session->getContext();
        $this->assertFalse($context->isNew);
        $this->assertEmpty($context->data);
    }

    public function testReadReturnsUnserializedData(): void
    {
        $sessionId = 'test_session_123';
        $sessionData = ['key' => 'value'];

        $session = $this->createMockSession();
        $session->write($sessionId, $sessionData);

        $readData = $session->read($sessionId);

        $this->assertSame($sessionData, $readData);
    }

    public function testReadReturnsEmptyArrayForNonExistentSession(): void
    {
        $session = $this->createMockSession();

        $readData = $session->read('nonexistent_session');

        $this->assertSame([], $readData);
    }

    public function testWriteSerializesAndStoresData(): void
    {
        $sessionId = 'test_session_123';
        $sessionData = ['key' => 'value'];

        $session = $this->createMockSession();
        $session->write($sessionId, $sessionData);

        $writtenSessions = $session->getWrittenSessions();
        $this->assertArrayHasKey($sessionId, $writtenSessions);
    }

    public function testOnResponseHeadersSendingReturnsEarlyWhenSessionNotStarted(): void
    {
        $events = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event::class;
                return $event;
            });
        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->createMockSession();
        $response = $this->createMock(ResponseInterface::class);

        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertSame([], $session->getWrittenSessions());
        $this->assertSame([], $session->getTouchedSessions());
        $this->assertNotContains(SessionStart::class, $events);
        $this->assertNotContains(SessionEnd::class, $events);
        $this->assertNotContains(SessionCreate::class, $events);
        $this->assertNotContains(SessionUpdate::class, $events);
    }

    public function testOnResponseHeadersSendingSkipsCreateAndWriteForEmptyNewSession(): void
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->any())->method('get')->willReturn(null);
        $cookies->expects($this->never())->method('set');
        $cookies->expects($this->any())->method('delete')->willReturnSelf();

        $events = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event::class;
                return $event;
            });

        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);
        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->container->make(MockSession::class);
        $session->all();

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertContains(SessionStart::class, $events);
        $this->assertContains(SessionEnd::class, $events);
        $this->assertNotContains(SessionCreate::class, $events);
        $this->assertNotContains(SessionUpdate::class, $events);
        $this->assertSame([], $session->getWrittenSessions());
    }

    public function testOnResponseHeadersSendingCreatesCookieAndWritesForNewDirtySession(): void
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->any())->method('get')->willReturn(null);
        $cookies->expects($this->once())
            ->method('set')
            ->with(
                'PHPSESSID',
                $this->isString(),
                0,
                '',
                '',
                false,
                true
            )
            ->willReturnSelf();
        $cookies->expects($this->any())->method('delete')->willReturnSelf();

        $events = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event::class;
                return $event;
            });

        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);
        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->container->make(MockSession::class);
        $session->set('user_id', 1001);
        $sessionId = $session->getId();

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertContains(SessionCreate::class, $events);
        $this->assertContains(SessionUpdate::class, $events);
        $written = $session->read($sessionId);
        $this->assertSame(1001, $written['user_id'] ?? null);
        $this->assertArrayHasKey('__T', $written);
    }

    public function testOnResponseHeadersSendingCreatesCookieAndWritesForRotatedSessionId(): void
    {
        $existingId = 'existing_session_123';
        $newId = 'rotated_session_456';

        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->any())->method('get')->willReturn($existingId);
        $cookies->expects($this->once())
            ->method('set')
            ->with(
                'PHPSESSID',
                $newId,
                0,
                '',
                '',
                false,
                true
            )
            ->willReturnSelf();
        $cookies->expects($this->any())->method('delete')->willReturnSelf();

        $events = [];
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event::class;
                return $event;
            });

        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);
        $this->container->remove(EventDispatcherInterface::class);
        $this->container->set(EventDispatcherInterface::class, $eventDispatcher);

        $session = $this->container->make(MockSession::class);
        $session->write($existingId, ['user_id' => 1001]);
        $this->assertSame(1001, $session->all()['user_id'] ?? null);

        $session->setId($newId);

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertContains(SessionCreate::class, $events);
        $this->assertContains(SessionUpdate::class, $events);
        $written = $session->read($newId);
        $this->assertSame(1001, $written['user_id'] ?? null);
        $this->assertArrayHasKey('__T', $written);
    }

    public function testOnResponseHeadersSendingSkipsWriteWhenLazyWindowNotExpired(): void
    {
        $session = $this->createMockSession();
        $context = $session->getContext();
        $context->started = true;
        $context->isNew = false;
        $context->isDirty = false;
        $context->sessionId = 'existing_session_lazy';
        $context->data = ['foo' => 'bar', '__T' => time()];

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertSame([], $session->getWrittenSessions());
        $this->assertSame([], $session->getTouchedSessions());
    }

    public function testOnResponseHeadersSendingTouchesSessionWhenLazyDisabledAndNotDirty(): void
    {
        $session = $this->createMockSession(parameters: ['lazy' => 0]);

        $context = $session->getContext();
        $context->started = true;
        $context->isNew = false;
        $context->isDirty = false;
        $context->sessionId = 'existing_session_touch';
        $context->data = ['foo' => 'bar'];

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertArrayHasKey('existing_session_touch', $session->getTouchedSessions());
        $this->assertSame([], $session->getWrittenSessions());
    }

    public function testOnResponseHeadersSendingWritesWhenLazyWindowExpired(): void
    {
        $session = $this->createMockSession();
        $session->setTtl(90);

        $context = $session->getContext();
        $context->started = true;
        $context->isNew = false;
        $context->isDirty = false;
        $context->sessionId = 'existing_session_lazy_expired';
        $context->data = ['foo' => 'bar', '__T' => time() - 120];

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $written = $session->getWrittenSessions();
        $this->assertArrayHasKey('existing_session_lazy_expired', $written);
        $this->assertSame(90, $written['existing_session_lazy_expired']['ttl']);

        $stored = $session->read('existing_session_lazy_expired');
        $this->assertSame('bar', $stored['foo'] ?? null);
        $this->assertArrayHasKey('__T', $stored);
        $this->assertIsInt($stored['__T']);
        $this->assertGreaterThanOrEqual(time() - 2, $stored['__T']);
    }

    public function testOnResponseHeadersSendingWritesWhenTouchFailsAndLazyDisabled(): void
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->any())->method('get')->willReturn(null);
        $cookies->expects($this->any())->method('set')->willReturnSelf();
        $cookies->expects($this->any())->method('delete')->willReturnSelf();

        $this->container->remove(CookiesInterface::class);
        $this->container->set(CookiesInterface::class, $cookies);

        $session = $this->container->make(TouchFailingMockSession::class, ['lazy' => 0]);
        $session->setTtl(45);

        $context = $session->getContext();
        $context->started = true;
        $context->isNew = false;
        $context->isDirty = false;
        $context->sessionId = 'touch_failed_session';
        $context->data = ['foo' => 'bar'];

        $response = $this->createMock(ResponseInterface::class);
        $session->onResponseHeadersSending(new HeadersSending($response));

        $this->assertArrayHasKey('touch_failed_session', $session->getTouchedSessions());
        $written = $session->getWrittenSessions();
        $this->assertArrayHasKey('touch_failed_session', $written);
        $this->assertSame(45, $written['touch_failed_session']['ttl']);

        $stored = $session->read('touch_failed_session');
        $this->assertSame('bar', $stored['foo'] ?? null);
        $this->assertArrayNotHasKey('__T', $stored, 'Lazy disabled should not write heartbeat marker');
    }

}
