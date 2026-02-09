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

## Entry Point

- `index.php`: require autoload, create or get app, set up shared services (`$app->set('db', $pdo)`), register routes, call `$app->start()`.

## Docs

- Flight PHP v3 docs: https://docs.flightphp.com/en/v3/learn
