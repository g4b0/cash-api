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
- Route params use `@` prefix: `$app->route('GET /@community_id/@member_id', $callback)`.
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

## Docs

- Flight PHP v3 docs: https://docs.flightphp.com/en/v3/learn
