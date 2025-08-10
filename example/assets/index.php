<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use CoffeeCode\Router\Router;

// Build a dynamic base URL (works in subdirectories and any host)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scheme . '://' . $host . ($basePath ?: '');

$router = new Router($base);

// Serve static files from ./public with cache enabled (1 hour)
$router->setAssets(__DIR__ . '/public')->setCache(3600);

// Optional hardening: allow only css/js and block symlinks (defaults already block traversal)
$router->setAssetAllowExtensions(['css', 'js']);
$router->setAssetFollowSymlinks(false);

// Home page demonstrating CSS/JS served by the Router
$router->get('/', function () {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>Assets Example</title>
        <link rel="stylesheet" href="style.css"/>
    </head>
    <body>
        <main class="container">
            <h1>Assets via CoffeeCode Router</h1>
            <p>This page is styled by <code>style.css</code> and powered by <code>app.js</code> served by the Router.</p>
            <section>
                <h2>Interactive Counter</h2>
                <div id="counter" data-count="0" class="counter">0</div>
                <button id="incBtn">Increment</button>
                <button id="headBtn">HEAD /style.css</button>
            </section>
            <section>
                <h2>Notes</h2>
                <ul>
                    <li>ETag/Last-Modified emitted; 304 Not Modified handled.</li>
                    <li>HEAD requests send headers only (no body).</li>
                    <li>Range requests supported (bytes= start-end ).</li>
                </ul>
            </section>
        </main>
        <script src="app.js" defer></script>
    </body>
    </html>
    <?php
});

// Optional diagnostics endpoint to verify headers quickly
$router->head('/style.css', function () {
    // The Router will still serve HEAD for assets automatically,
    // this is here just as an explicit example endpoint.
});

// Execute routes
$router->dispatch();

// Basic error handling
if ($router->error()) {
    http_response_code($router->error());
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error: " . $router->error();
}