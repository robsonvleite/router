<?php

require dirname(__DIR__, 2) . "/vendor/autoload.php";
require __DIR__ . "/Test/Coffee.php";
require __DIR__ . "/Test/Name.php";

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
$router->group("admin");

$router->get("/", "Coffee:admin");
$router->get("/user/{id}", "Coffee:admin");
$router->get("/user/{id}/profile", "Coffee:admin");
$router->get("/user/{id}/profile/{photo}", "Coffee:admin");

/**
 * named routes
 */
$router->group("name");
$router->get("/", "Name:home", "name.home");
$router->get("/hello", "Name:hello", "name.hello");

$router->get("/redirect", "Name:redirect", "name.redirect");
$router->get("/redirect/{category}/{page}", "Name:redirect", "name.redirect");
$router->get("/params/{category}/page/{page}", "Name:params", "name.params");

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
    var_dump($router->error());
    //router->redirect("/error/{$router->error()}");
}