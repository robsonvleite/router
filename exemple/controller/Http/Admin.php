<?php

namespace Http;

use CoffeeCode\Router\Router;

class Admin
{
    public function handle(Router $router): bool
    {
        echo "<p><i>O middleware Admin foi executado!</i></p>";

        $admin = filter_input(INPUT_GET, "admin", FILTER_VALIDATE_BOOL);

        if ($admin) {
            return true;
        }

        echo "<h1>Acces Denied!</h1>";
        echo "<a href='{$router->route("coffe.denied", ["admin" => true])}'>Simular Admin</a>";
        return false;
    }
}