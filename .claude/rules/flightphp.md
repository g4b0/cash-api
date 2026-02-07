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

## Entry Point

- `index.php`: require autoload, create or get app, register routes, call `$app->start()`.

## Docs

- Flight PHP v3 docs: https://docs.flightphp.com/en/v3/learn
