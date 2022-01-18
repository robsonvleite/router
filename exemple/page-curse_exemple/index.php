<?php
require __DIR__ . "/vendor/autoload.php";
define("URL_BASE", "http://localhost");
require("./routes.php");

use CoffeeCode\Router\Router;

$router = new Router(URL_BASE);

$router->namespace("RoutesController");

$router->get("/", "web:home");
$router->get("/home", "web:home");
$router->get("/about", "web:about");
$router->get("/contact", "web:contact");
$router->get("/courses", "web:courses");
$router->get("/course/{id}", "web:course");

$router->group("error")->namespace("RoutesController");
$router->get("/{errcode}", "web:http_404");


$router->dispatch();

if ($router->error()) {
    $router->redirect(URL_BASE . "/error/{$router->error()}");
}