<?php

require dirname(__DIR__, 2) . "/vendor/autoload.php";
require __DIR__ . "/Test/Coffee.php";
require __DIR__ . "/Test/Name.php";

/*
 * Middleware example classes
 */
require __DIR__ . "/Http/Guest.php";
require __DIR__ . "/Http/User.php";
require __DIR__ . "/Http/Group.php";

use CoffeeCode\Router\Router;

const BASE = "https://www.localhost/coffeecode/router/exemple/controller";
$router = new Router(BASE);

/**
 * routes
 */
$router->namespace("Test");

$router->get("/", "Coffee:home");
$router->get("/edit/{id}", "Coffee:edit", middleware: \Http\Guest::class);
$router->post("/edit/{id}", "Coffee:edit");
$router->get("/logado", "Coffee:logged", middleware: [\Http\Guest::class, \Http\User::class]);
$router->get("/negado", "Coffee:denied", "coffe.denied", \Http\User::class);

/**
 * group by routes and namespace
 */
$router->group("admin", \Http\Group::class);

$router->get("/", "Coffee:admin");
$router->get("/user/{id}", "Coffee:admin");
$router->get("/user/{id}/profile", "Coffee:admin", \Http\Guest::class);
$router->get("/user/{id}/profile/{photo}", "Coffee:admin");

/**
 * named routes and middlewares
 */
$router->group("name");

$router->get("/", "Name:home", "name.home");
$router->get("/hello", "Name:hello", "name.hello", \Http\Guest::class);
$router->get("/redirect", "Name:redirect", "name.redirect", \Http\Guest::class);
$router->get("/redirect/{category}/{page}", "name:redirect", "name.redirect.params");
$router->get("/params/{category}/page/{page}", "name:params", "name.params");

/**
 * call route and group middleware
 */
$router->group("call", \Http\Guest::class);
$router->get(
    "/",
    function ($data, Router $route) {
        var_dump($data, $route->current());

        echo "<a href='{$route->home()}' title='voltar'>voltar</a>";
    }
);
$router->get(
    "/{app}/",
    function ($data, Router $route) {
        var_dump($data, $route->current());

        echo "<a href='{$route->home()}' title='voltar'>voltar</a>";
    }
);

/**
 * Group Error
 */
$router->namespace("Test")->group("error");
$router->get("/{errcode}", "Coffee:notFound");

/**
 * execute
 */
$router->dispatch();

if ($router->error()) {
    //var_dump($router->error());
    $router->redirect("/error/{$router->error()}");
}