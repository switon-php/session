# Switon Session Package

[![Session CI](https://img.shields.io/github/actions/workflow/status/switon-php/session/ci.yml?branch=main&label=Session%20CI)](https://github.com/switon-php/session/actions/workflows/ci.yml) [![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4)](https://www.php.net/)

Switon's HTTP session layer for request-scoped state, Redis-backed persistence, and session lookup, update, and
invalidation by ID.

## Highlights

- **Session state:** `SessionInterface` covers read, write, remove, and destroy flows for request data.
- **Lifecycle visibility:** session start, update, destroy, and failure states can be observed.
- **Redis storage:** session payloads can be stored in Redis with app-aware keys.
- **Session bags:** `BagInterface` keeps component-specific keys under one namespace.
- **Session control:** apps can look up, update, or revoke any session by ID.

## Installation

```bash
composer require switon/session
```

## Quick Start

```php
use Switon\Core\Attribute\Autowired;
use Switon\Session\SessionInterface;

class AuthController
{
    #[Autowired] protected SessionInterface $session;

    public function login(int $userId): void
    {
        $this->session->set('user_id', $userId);
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
```

Docs: https://docs.switon.dev/latest/session

## License

MIT.
