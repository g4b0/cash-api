---
name: flightphp
description: Load Flight PHP v3 conventions for testing, routing, and entry point setup
user-invocable: true
---

# Flight PHP v3 Conventions

## Testing

- Use `new \flight\Engine()` for isolated instances — never the `Flight::` static facade in tests.
- Set `flight.views.path` to a dummy or temp dir if views aren't needed.
- Simulate requests by setting `$app->request()->url` and `$app->request()->method` before calling `$app->start()`.
- Assert on `$app->response()->status()` and response body.

## Route Registration

- Extract route registration into a reusable function (`registerRoutes(\flight\Engine $app)`) so both `index.php` and tests share the same routes.
- Route params use `@` prefix: `$app->route('GET /@communityId/@memberId', $callback)`.
- **Optional params**: Wrap segments in parentheses: `$app->route('GET /path/@required(/@optional1(/@optional2))', $callback)`. Optional params are passed as `null` if not provided. Use nullable types (`?string`, `?int`) in method signatures.
  - Example: `/transactions/@communityId/@memberId(/@num(/@page))` matches `/transactions/1/2`, `/transactions/1/2/25`, and `/transactions/1/2/25/3`.
- JSON responses: `$app->json($data, $statusCode)`.

## Controllers

- Flight auto-injects `Engine` into controllers: `new ControllerClass($engine)` (see `Dispatcher.php:384-385`).
- Route syntax: `$app->route('GET /path', [Controller::class, 'method'])`.
- Access services/DB via `$this->app->get('key')` where `$this->app` is the injected `Engine`.
- No DI container needed — store shared objects with `$app->set('key', $value)`.

## Auth Middleware via `map('start', ...)`

- **Do NOT use `addMiddleware()` for custom status codes.** Flight v3 hardcodes `halt(403, 'Forbidden')` when any middleware's `before()` returns false (`Engine.php:652-653`). There is no way to return 401 instead.
- **Do NOT use `before('start', ...)` to prevent route execution.** `Dispatcher::runPreFilters()` discards the `$output` variable — setting it in a filter callback has no effect on whether `_start()` runs.
- **Use `$app->map('start', $callback)` to override the start event.** This replaces `_start()` in the Dispatcher. The custom callback performs auth checks, then delegates to `$app->_start()` for route processing. Before/after filters registered by Flight (error handlers, etc.) still run because the Dispatcher's `run()` method calls `runPreFilters` first.
- Pattern:
  ```php
  $app->map('start', function () use ($app) {
      if (!$publicRoute && !$validAuth) {
          $app->json(['error' => 'Unauthorized'], 401);
          return; // skips _start(), returns 401
      }
      $app->_start(); // proceed with route processing
  });
  ```

## Testing POST Data

- Simulate POST body: `$app->request()->data->setData(['key' => 'value'])`
- Simulate auth header: `$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token`
- Clean up in `tearDown()`: `unset($_SERVER['HTTP_AUTHORIZATION'])`
- `Request::getHeader('Authorization')` reads `$_SERVER['HTTP_AUTHORIZATION']` (static method)

## Entry Point

- `index.php`: require autoload, create or get app, set up shared services (`$app->set('db', $pdo)`), register routes, call `$app->start()`.

## Known Issues & Limitations (Potential PRs)

These are Flight PHP v3 limitations that could be improved with upstream PRs:

### Issue 1: `addMiddleware()` Hardcodes 403 Response

**Problem**: `Engine::addMiddleware()` hardcodes `halt(403, 'Forbidden')` when middleware's `before()` returns false (see `Engine.php:652-653`). This makes it impossible to return custom HTTP status codes like 401 Unauthorized.

**Code location**: `Engine.php:652-653`
```php
if (!$result) {
    $this->halt(403, 'Forbidden');  // ❌ Hardcoded, not configurable
}
```

**Current workaround**: Use `$app->map('start', ...)` to override the start event instead of using middleware. See "Auth Middleware via map('start')" section above.

**Potential fix**: Allow middleware to throw exceptions or return status codes:
```php
if (!$result) {
    if ($result instanceof Exception) {
        throw $result;  // Let exception handler decide status
    }
    $this->halt(403, 'Forbidden');
}
```

### Issue 2: Output Buffering Leaves Buffers Open in Tests

**Problem**: Flight PHP's exception handling leaves output buffers open when exceptions are thrown during tests, causing PHPUnit to mark tests as "risky" with the warning: _"Test code or tested code did not (only) close its own output buffers"_

**Root cause**:
1. Flight uses output buffering to capture response bodies
2. Exception handlers (`map('error', ...)`) output JSON directly
3. Buffers may remain open when execution stops
4. PHPUnit checks buffer levels and marks tests as risky

**Impact**: Tests pass correctly but PHPUnit reports warnings. Doesn't affect test correctness or production code.

**Current workaround**: Configure PHPUnit to not fail on risky tests:
```xml
<!-- phpunit.xml -->
<phpunit
    failOnRisky="false"
    beStrictAboutOutputDuringTests="false">
    <!-- ... -->
</phpunit>
```

Or run with flag:
```bash
php vendor/bin/phpunit --dont-report-useless-tests
```

**Potential fix**: Ensure exception handlers properly clean up output buffers:
```php
// In exception handler
while (ob_get_level() > 1) {  // Keep level 1 for PHPUnit
    ob_end_clean();
}
// Then output error response
```

### Issue 3: `before('start', ...)` Filter Output Discarded

**Problem**: `Dispatcher::runPreFilters()` discards the `$output` variable, so setting it in a `before('start')` filter has no effect on whether `_start()` runs.

**Code location**: `Dispatcher.php` (the `$output` reference is not used to skip execution)

**Current workaround**: Use `$app->map('start', ...)` instead, which fully replaces the start event handler.

**Potential fix**: Check `$output` after running preFilters and skip `_start()` if output was generated:
```php
public function run(string $event, array $params): void {
    $output = null;
    $this->runPreFilters($event, $params, $output);
    if ($output !== null) {
        return;  // Skip event execution if filter produced output
    }
    // Continue with event...
}
```

---

**Note**: These issues are documented here for future reference. Consider contributing PRs to Flight PHP v3 to address these limitations.

## Docs

- Flight PHP v3 docs: https://docs.flightphp.com/en/v3/learn
- Flight PHP GitHub: https://github.com/mikecao/flight
