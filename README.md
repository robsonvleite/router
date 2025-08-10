# Router @HellFiveOsborn (Fork of CoffeeCode Router)

[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-777bb4.svg?style=flat-square)](composer.json)

A next‑gen, production‑ready PHP router. Autonomous (no .htaccess/Nginx required), secure static asset serving with ETag/Last‑Modified/Range, robust HTTP semantics (HEAD/OPTIONS/405), route constraints, CLI tools for DX, and global helpers.

This project is a modern fork of the original CoffeeCode Router. Credits to the initial version by Robson V. Leite and UpInside (see Credits). This fork evolves the design with reliability, security, and developer experience in mind, while preserving compatibility.

## Highlights

- Autonomous routing (no rewrite rules required), still compatible with ?route= if rewrites exist
- HTTP verbs: GET, POST, PUT, PATCH, DELETE, with HEAD/OPTIONS support
- 405 Method Not Allowed with Allow header when path matches other methods
- Route constraints, e.g. `/user/{id:\d+}` and `/post/{slug:[a-z0-9-]+}`
- Middleware chaining with class middlewares
- Secure assets serving: ETag/Last‑Modified/Cache‑Control, HEAD, byte‑range (206), root‑anchored realpath
- Assets hardening: allowlist extensions and symlink policy control
- Global helpers (router_auto, router_url, router_json, …)
- CLI route lister (vendor/bin/list-routes)
- Backward compatibility with original usage patterns

## Installation

Via Composer (recommended):

```bash
// In composer.json add:
{
  "repositories": [
    {"type": "vcs", "url": "https://github.com/HellFiveOsborn/router"}
  ],
  "require": {
    "hellfiveosborn/router": "dev-master"
  }
}
composer update
```

PHP requirement: >= 8.0

## Quick Start (Autonomous mode)

The router resolves the path directly from REQUEST_URI (and honors SCRIPT_NAME, project base path, proxies like X-Forwarded-Prefix). No rewrite rules are needed. If ?route= is present (Apache/Nginx), it will be used for compatibility.

Minimal front controller:

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use CoffeeCode\Router\Router;

// Create router with auto-detected base URL
$router = Router::auto();

// Simple route
$router->get('/', function () {
    echo "Hello from Router";
});

// Execute
$router->dispatch();

// Error handling (404, 405, 501, 400)
if ($router->error()) {
    http_response_code($router->error());
    echo "Error: " . $router->error();
}
```

Local development (PHP built-in server):

```bash
php -S 0.0.0.0:8000 -t . index.php
```

Apache/Nginx (optional): existing rewrite snippets continue to work; when `?route=` is present, the router honors it.

### Specifying a custom base URL (domain and/or subpath)

If you prefer to explicitly set the base URL (e.g., for fixed domain, subdomain, or subpath deployments), instantiate the router with your URL. The router will normalize and route correctly in both cases:

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use CoffeeCode\Router\Router;

// 1) Domain or subdomain
$router = new Router('https://api.example.com');

// 2) Domain with subpath (application mounted at /myapp)
$router = new Router('https://example.com/myapp');

// Routes work the same way
$router->get('/', fn () => print 'Home');
$router->get('/health', fn () => print 'OK');

// Execute
$router->dispatch();

if ($router->error()) {
    http_response_code($router->error());
    echo "Error: " . $router->error();
}
```

Notes:
- Subpaths are handled internally: [Dispatch.resolvePath()](src/Dispatch.php:132) removes the project base path so your routes remain clean (e.g., `$router->get('/health', ...)` works for `https://example.com/myapp/health`).
- Behind proxies/load balancers, prefer [Router.auto()](src/Router.php:62) (which honors `X-Forwarded-*`) or set `--base` in tooling/CLI (see the CLI section).
- You may also externalize the base URL, e.g. via ENV:
  ```php
  $base = getenv('APP_URL') ?: Router::autoBaseUrl();
  $router = new Router(rtrim($base, '/'));
  ```
### Helpers (autoloaded)

Global helpers are available to reduce boilerplate:

```php
// Create Router automatically
$router = router_auto();

// URLs
$base = router_base_url();         // https://host/subapp
echo router_url('docs');           // https://host/subapp/docs
echo router_asset('css/app.css');  // https://host/subapp/css/app.css

// Request info
if (router_is_method('POST')) { /* ... */ }
$method = router_request_method();
$path   = router_request_path();

// Responses
router_json(['status' => 'ok'], 200);
// router_redirect('/login', 302);
```

## Routes

```php
use CoffeeCode\Router\Router;

$router = Router::auto();

// Namespaces and groups
$router->namespace('App');          // Controllers in App namespace

// Simple routes
$router->get('/route', 'Controller:method');
$router->post('/route/{id}', 'Controller:method');

// Constraints: digits and slug
$router->get('/user/{id:\d+}', 'UserController:show');
$router->get('/post/{slug:[a-z0-9-]+}', 'PostController:show');

// Groups
$router->group('admin')->namespace('Dash');
$router->get('/route', 'Controller:method');
$router->post('/route/{id}', 'Controller:method');

// Error group (example)
$router->group('error')->namespace('App');
$router->get('/{errcode}', 'ErrorController:notFound');

// Execute
$router->dispatch();

if ($router->error()) {
    $router->redirect("/error/{$router->error()}");
}
```

### Named Routes

```php
$router->namespace('App')->group('name');

$router->get('/', 'Name:home', 'name.home');
$router->get('/hello', 'Name:hello', 'name.hello');
$router->get('/redirect', 'Name:redirect', 'name.redirect');

// Generate URL by name
$url = $router->route('name.hello');                      // absolute URL
$url = $router->route('name.redirect', ['id' => 42]);     // with params
```

### Middleware

```php
// single
$router->get('/edit/{id}', 'Coffee:edit', middleware: \Http\Guest::class);

// multiple
$router->get('/logged', 'Coffee:logged', middleware: [\Http\Guest::class, \Http\Group::class]);

// middleware group
$router->group('name', \Http\Guest::class);
$router->get('/', 'Name:home', 'name.home');
```

Middleware classes must implement a `handle(Router $router): bool` method and return true to continue or false to stop.

### Form Spoofing

PUT, PATCH, DELETE via POST using field `_method` are supported. The router also honors `X-HTTP-Method-Override`.

```html
<form action="" method="POST">
  <select name="_method">
    <option>POST</option>
    <option>PUT</option>
    <option>PATCH</option>
    <option>DELETE</option>
  </select>
  <button>Submit</button>
</form>
```

## Serving Static Assets (secure)

```php
$router = Router::auto();

$router->setAssets(__DIR__ . '/public')->setCache(3600); // 1h cache
$router->setAssetAllowExtensions(['css','js','png','jpg']); // allowlist
$router->setAssetFollowSymlinks(false); // default false

// Now requests for /style.css, /app.js under public/ are served:
// - ETag/Last-Modified caching
// - Cache-Control, 304 Not Modified
// - HEAD support (headers only)
// - Byte-range (206 Partial Content)
```

## HEAD, OPTIONS, 405

- HEAD automatically supported (handlers execute with suppressed body; assets send headers only)
- OPTIONS auto-responds with `Allow` header for matching resource
- 405 Method Not Allowed returned when path exists for other methods; `Allow` header lists supported verbs

## CLI – List Routes

A simple CLI tool to inspect routes:

```bash
# if installed as dependency:
vendor/bin/list-routes --bootstrap=example/cli/routes.php --format=table

# standalone from repo:
php bin/list-routes --bootstrap=example/cli/routes.php --format=json
```

Options:
- `--bootstrap=FILE` file that registers routes (uses provided `$router` variable or returns Router)
- `--base=URL` override detected base (useful in CLI)
- `--format=table|json` output format

Example bootstrap is available at `example/cli/routes.php`.

## Examples

We include production-ready examples you can run without rewrites:

```bash
# Front unificado com exemplos
php -S 0.0.0.0:8000 -t example example/index.php
```

- API: `/api/` (JSON, constraints)
- Webhook: `/webhook/` (POST + demo HMAC)
- Assets: `/assets/` (serving CSS/JS via Router)
- Health: `/health`

## Testing

Pest is configured with a comprehensive unit/integration suite. See TESTING.md for details.

```bash
composer install
./vendor/bin/pest
```

## Credits

- Original Router: CoffeeCode Router by [Robson V. Leite](https://github.com/robsonvleite) and [UpInside](https://github.com/upinside)
- This fork: HellFiveOsborn

## Author & Support

- Author: HellFiveOsborn — anonimo@mail.com
- Lightning: cuttinggate97@walletofsatoshi.com
- GitHub: https://github.com/HellFiveOsborn/router
- Issues: https://github.com/HellFiveOsborn/router/issues

## License

MIT. See [LICENSE](LICENSE).
