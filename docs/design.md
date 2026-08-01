# Design Rationale {#design-rationale}

`switon/session` keeps HTTP session state request-scoped, Redis-backed, and explicit about persistence timing.

## See also {#see-also}

- [Session Package](README.md) — usage, configuration, and events
- [FAQ](faq.md#session-faq) — short answers for setup and common issues
- [Redis Package](https://github.com/switon-php/redis/blob/main/docs/README.md) — storage backend used by the default session implementation

---

## Core Problem {#core-problem}

Session state is easy to treat like global mutable memory. That breaks down in worker-based runtimes and concurrent requests.

## Primary Goal {#primary-goal}

`switon/session` gives applications one small session API while keeping isolation, persistence timing, and lifecycle events under framework control.

- request data stays isolated per request
- normal writes are deferred
- direct storage access stays explicit

## Trade-Off {#trade-off}

- **Benefit:** session code stays simple in controllers and services.
- **Benefit:** the same model works across runtime styles.
- **Cost:** callers must respect request lifecycle timing.
- **Cost:** `read()` / `write()` are separate from the normal request path.

## Deliberate Limits {#deliberate-limits}

- small state, not large object storage
- one current session, plus explicit ID-based operations
- lifecycle monitoring through events, not custom controller hooks
