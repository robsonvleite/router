<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use CoffeeCode\Router\Router;

// Build a dynamic base URL (works in subdirectories and any host)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scheme . '://' . $host . ($basePath ?: '');

$router = new Router($base);

// Optional: enable debug headers for inspection
// $router->setDebugHeaders(true);

// API v1 group
$router->group('api/v1');

// Index of API
$router->get('/', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'name' => 'CoffeeCode Router API',
        'version' => 'v1',
        'endpoints' => [
            'GET  /api/v1/users/{id}' => 'Get user by numeric id',
            'POST /api/v1/users' => 'Create user with form/json data',
            'GET  /api/v1/health' => 'Health status',
        ]
    ]);
});

// Health endpoint (GET/HEAD/OPTIONS available)
$router->get('/health', function () {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
});
$router->head('/health', function () {
    header('Content-Type: application/json');
});
$router->options('/health', function () {
    // Allow header is also auto-generated when needed
});

// Get user by id with constraint {id:\d+}
$router->get('/users/{id:\d+}', function (array $data) {
    header('Content-Type: application/json');
    $id = (int)($data['id'] ?? 0);
    echo json_encode([
        'id' => $id,
        'name' => 'User ' . $id,
        'links' => [
            'self' => '/api/v1/users/' . $id
        ]
    ]);
});

// Create user (accepts form data by default; if JSON body is sent, parse it)
$router->post('/users', function (array $data) {
    header('Content-Type: application/json');

    // Try to parse JSON if Content-Type is application/json
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ctype, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $data = array_merge($data, $json);
        }
    }

    http_response_code(201);
    echo json_encode([
        'created' => true,
        'data' => $data
    ]);
});

// Execute routes
$router->dispatch();

// Basic error handling
if ($router->error()) {
    http_response_code($router->error());
    header('Content-Type: application/json');
    echo json_encode(['error' => $router->error()]);
}