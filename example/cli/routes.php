<?php

use CoffeeCode\Router\Router;

/**
 * Example route bootstrap for the route lister (bin/list-routes).
 *
 * How this file is used:
 * - When invoked by bin/list-routes, a $router instance is provided in scope.
 * - If not provided, we create one via Router::auto() to allow standalone usage.
 *
 * This file MUST NOT call $router->dispatch(). It should only register routes
 * and either return $router or rely on the provided $router variable being used.
 */

/** @var Router|null $router */
if (!isset($router) || !$router instanceof Router) {
    // Fallback for standalone inclusion
    $router = Router::auto();
}

$router->namespace('App');

// Root
$router->get('/', function () { /* ... */ }, 'home');

// Health
$router->get('/health', function () { /* ... */ }, 'health');
$router->head('/health', function () { /* ... */ });
$router->options('/health', function () { /* ... */ });

// Users with constraints
$router->group('api/v1');
$router->get('/users/{id:\d+}', 'UserController:show', 'users.show');
$router->post('/users', 'UserController:create', 'users.create');

// Admin area
$router->group('admin');
$router->get('/dashboard', 'AdminController:index', 'admin.dashboard');

// Return router instance for consumers that expect a return value
return $router;