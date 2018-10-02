<?php

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/Test/Coffee.php";

use CoffeeCode\Router\Router;

define("BASE", "https://www.localhost/coffeecode/router/exemple/controller");
$router = new Router(BASE);

/**
 * routes
 */
$router->namespace("Test");

$router->get("/", "Coffee:home");
$router->get("/edit/{id}", "Coffee:edit");
$router->post("/edit/{id}", "Coffee:edit");

/**
 * group by routes and namespace
 */
$router->group("admin")->namespace("Test");

$router->get("/", "Coffee:admin");
$router->get("/user/{id}", "Coffee:admin");
$router->get("/user/{id}/profile", "Coffee:admin");
$router->get("/user/{id}/profile/{photo}", "Coffee:admin");

/**
 * Group Error
 */
$router->group("error")->namespace("Test");
$router->get("/{errcode}", "Coffee:notFound");


/**
 * execute
 */
$router->dispatch();

if ($router->error()) {
    $router->redirect("/error/{$router->error()}");
}