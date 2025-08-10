<?php

/**
 * Global helper functions for CoffeeCode Router.
 * These functions aim to simplify common tasks without requiring boilerplate.
 *
 * Autoloaded via Composer "files" (see composer.json).
 */

use CoffeeCode\Router\Router;

/**
 * Create a Router instance using the detected base URL.
 */
function router_auto(?string $separator = ":"): Router
{
    return Router::auto($separator);
}

/**
 * Detect a robust base URL from the current request context.
 * - Honors proxy headers (X-Forwarded-Proto/Host/Prefix)
 * - Includes script base directory when present (subpath deployments)
 * - Returns WITHOUT trailing slash
 */
function router_base_url(): string
{
    return Router::autoBaseUrl();
}

/**
 * Build an absolute URL based on detected base URL and an optional path.
 * - The returned URL always uses a single slash between base and path.
 */
function router_url(?string $path = null): string
{
    $base = router_base_url();
    if ($path === null || $path === '') {
        return $base;
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Return the effective HTTP method considering X-HTTP-Method-Override.
 * Methods are returned as uppercase (GET, POST, PUT, PATCH, DELETE, etc).
 */
function router_request_method(): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $override = strtoupper((string)$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        if (in_array($override, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
            $method = $override;
        }
    }
    return strtoupper($method);
}

/**
 * Check if the current request method matches the given one (case-insensitive).
 */
function router_is_method(string $method): bool
{
    return strtoupper($method) === router_request_method();
}

/**
 * Resolve the current request path similar to Router internals (best-effort).
 * - Removes script base directory and proxy forwarded prefix.
 * - Returns a normalized path (leading slash, no trailing slash unless root).
 */
function router_request_path(): string
{
    // From REQUEST_URI
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

    // Remove script base dir
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $baseDir = str_replace('\\', '/', dirname($scriptName));
    $baseDir = ($baseDir === '/' ? '' : rtrim($baseDir, '/'));
    if ($baseDir !== '' && strpos($uri, $baseDir) === 0) {
        $uri = substr($uri, strlen($baseDir));
        if ($uri === false) {
            $uri = '/';
        }
    }

    // Remove proxy forwarded prefix if present
    $xfp = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';
    if (is_string($xfp)) {
        $xfp = rtrim($xfp, '/');
        if ($xfp !== '' && strpos($uri, $xfp) === 0) {
            $uri = substr($uri, strlen($xfp));
            if ($uri === false) {
                $uri = '/';
            }
        }
    }

    $path = '/' . ltrim((string)$uri, '/');
    $normalized = rtrim($path, '/');
    return ($normalized === '') ? '/' : $normalized;
}

/**
 * Send a JSON response with status and optional custom headers.
 * Automatically sets Content-Type: application/json; charset=utf-8
 */
function router_json(mixed $data, int $status = 200, array $headers = []): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    foreach ($headers as $name => $value) {
        header($name . ': ' . $value, true);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Issue an HTTP redirect to the given absolute or relative URL.
 * Defaults to 302 (Found).
 */
function router_redirect(string $to, int $status = 302): void
{
    header('Location: ' . $to, true, $status);
    exit;
}

/**
 * Generate a URL for a named route using a Router instance.
 * Returns null when the route name doesn't exist.
 */
function router_route(Router $router, string $name, ?array $data = null): ?string
{
    return $router->route($name, $data);
}

/**
 * Build an absolute asset URL from the detected base URL (or a provided base).
 * Example: router_asset('css/app.css') -> https://host/base/css/app.css
 */
function router_asset(string $relativePath, ?string $base = null): string
{
    $base ??= router_base_url();
    return rtrim($base, '/') . '/' . ltrim($relativePath, '/');
}