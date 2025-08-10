<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use CoffeeCode\Router\Router;

// Build a dynamic base URL (works in subdirectories and any host)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scheme . '://' . $host . ($basePath ?: '');

$router = new Router($base);

// Webhook docs (GET)
$router->get('/', function (array $data, Router $r) {
    header('Content-Type: text/html; charset=utf-8');
    $endpoint = $r->home() . '/incoming';
    echo "<h1>Webhook Example</h1>";
    echo "<p>POST JSON to <code>{$endpoint}</code> with optional HMAC header:</p>";
    echo "<pre>curl -X POST '{$endpoint}' \\
  -H 'Content-Type: application/json' \\
  -H 'X-Signature: sha256=<hmac>' \\
  -d '{\"event\":\"order.created\",\"id\":123}'</pre>";
});

// Webhook receiver (POST)
// Optional simple HMAC validation using a demo secret
$router->post('/incoming', function (array $data) {
    header('Content-Type: application/json');

    // Parse JSON if provided
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $payload = $data;
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $payload = $json;
        }
    }

    // Demo HMAC using a test secret
    $secret = 'demo_secret_change_me';
    $provided = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

    $verified = hash_equals($expected, $provided);

    // Process event (no-op)
    http_response_code(200);
    echo json_encode([
        'received' => true,
        'verified' => $verified,
        'event' => $payload['event'] ?? null,
        'payload' => $payload
    ]);
});

// Execute
$router->dispatch();

// Errors
if ($router->error()) {
    http_response_code($router->error());
    header('Content-Type: application/json');
    echo json_encode(['error' => $router->error()]);
}