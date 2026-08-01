# Session FAQ {#session-faq}

Short answers for setup and common session issues.

> **Prerequisites:** [Session Package](README.md)

## Getting Started {#getting-started}

### When does a session actually start? {#when-session-starts}

On first session access.
If a cookie points to an expired or empty record, a new session ID is generated.

### When does a new session become persistent? {#when-session-persists}

Only after it has data to save.
Persistence happens during response header sending, not immediately after `set()`.

### Does `has()` return true for `null` values? {#has-null-values}

No. `null` is treated as missing.

## Configuration {#configuration}

### Where is Session configured? {#where-configured}

Under `Switon\Session\SessionInterface` in `switon.yml`.

### What is the difference between `ttl` and `params.expire`? {#ttl-vs-expire}

`ttl` controls storage lifetime. `params.expire` controls cookie lifetime.

### What does `lazy` do? {#what-is-lazy}

It reduces rewrite churn for unchanged session data.
When `lazy > 0`, the session keeps an internal `__T` timestamp in the stored data.

### How do I inspect the exact option surface for my version? {#discover-options}

Run `bash bin/console config:options 'Switon\Session\SessionInterface'`.

## Runtime Behavior {#runtime-behavior}

### Why is data not written immediately after `set()`? {#not-written-immediately}

Normal persistence happens near response end.

### What do `read()` and `write()` change? {#direct-storage-boundary}

They work by session ID and do not update the current cookie automatically.
They use JSON payloads and `write()` inherits the current request TTL override.

### What is the difference between `destroy()` and `destroy($sessionId)`? {#destroy-modes}

`destroy()` removes the current session. `destroy($sessionId)` removes one stored session by ID.

## Troubleshooting {#troubleshooting}

### Session is always new. Why? {#always-new}

Check the client cookie, cookie scope, Redis key prefix, and whether the stored session id has expired or been cleared.

### Data disappears after the browser closes. Why? {#browser-close}

Your cookie may be a session cookie.

### Why did `destroy($sessionId)` not remove the current cookie? {#destroy-id-cookie}

It only deletes storage for the specified session ID.

### How can I inspect one stored session quickly? {#inspect-one-session}

Use `read($sessionId)`.
`read()` returns an empty array when the stored payload cannot be unserialized.
