# Session Package {#session-package}

`switon/session` keeps small HTTP session state across requests and stores it in Redis as JSON. Choose it for short user state (auth, flash, light preferences); it is not large object storage and does not replace persistent storage.

**Key features:**

- **Request-scoped state:** session data stays isolated per request even in long-lived runtimes.
- **Redis storage:** `Switon\Session\Session` persists JSON payloads through `Switon\Redis\ClientInterface`.
- **Deferred writes:** `get()` / `set()` update request state first; persistence runs near response end on `HeadersSending`.
- **Direct storage access:** `read($sessionId)` and `write($sessionId, $data)` operate by session id without touching the cookie.
- **Lifecycle events:** `SessionStart`, `SessionEnd`, `SessionCreate`, `SessionUpdate`, `SessionDestroy`, and `SessionUnserializeFailed`.

---

## Installation {#installation}

**Requirements:** PHP 8.3+ plus the framework pieces that provide HTTP headers, Redis storage, and events (`switon/http`, `switon/redis`, `switon/event`).

```bash
composer require switon/session
```

---

## Quick Start {#quick-start}

Inject `SessionInterface` and use the request-scoped API.

```php
namespace App\Controller;

use Switon\Core\Attribute\Autowired;
use Switon\Session\SessionInterface;

final class AuthController
{
    #[Autowired] protected SessionInterface $session;

    public function login(int $userId): void
    {
        $this->session->set('user_id', $userId);
    }

    public function me(): ?int
    {
        return $this->session->get('user_id');
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
```

---

## Core Concepts {#core-concepts}

### Request Model {#request-model}

Session data lives inside the current request context. `get()`, `set()`, `has()`, and `remove()` are the normal request API; `has()` follows `isset()` semantics, so `null` is treated as missing. `Switon\Session\BagInterface` groups related keys under one session key when you want a sub-namespace.

### Persistence {#persistence}

`set()` and `remove()` update request state first. Persistence runs during the `Switon\Http\Event\HeadersSending` event near the end of the response, so a request that errors before flushing headers does not leave half-written data.

`read($sessionId)` and `write($sessionId, $data)` go straight to storage and do not update the current cookie.

### Session IDs {#session-ids}

- `getId()` returns the current session id.
- `setId($sessionId)` switches the current request to a different id.
- `destroy()` removes the current session; `destroy($sessionId)` removes one stored session without touching the current cookie.

---

## Configuration {#configuration}

Most apps only set the cookie name, TTL, and lazy window.

```yaml
Switon\Session\SessionInterface:
  name: PHPSESSID
  ttl: 3600
  lazy: 60
```

### Option Discovery {#option-discovery}

Use the current option surface directly:

```bash
bash bin/console config:options 'Switon\Session\SessionInterface'
```

---

## Keep in Mind {#keep-in-mind}

- `ttl` controls storage lifetime; cookie lifetime is `params.expire`.
- `lazy > 0` keeps an internal `__T` timestamp in the payload to skip rewriting unchanged data; `lazy = 0` always uses touch-first persistence.
- Stored payloads are JSON, not PHP `serialize()`; an unserialize failure raises `SessionUnserializeFailed` and leaves an empty array to the caller.
- `write($sessionId, ...)` inherits the current request TTL override.

---

## Next {#next}

- **Common questions:** [FAQ](faq.md#session-faq)
- **Design trade-offs:** [Design Rationale](design.md#design-rationale)
- **Redis details:** [Redis Package](../../redis/docs/README.md)
