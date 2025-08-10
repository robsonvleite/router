<?php

namespace Http;

use CoffeeCode\Router\Router;

class User
{
    public function handle(Router $router): bool
    {
        echo "<p><i>O middleware <b>User</b> foi executado!</i></p>";

        $user = filter_input(INPUT_GET, "user", FILTER_VALIDATE_BOOL);
        if ($user) {
            return true;
        }

        echo "<h1>Acces Denied!</h1>";
        echo "<a href='{$router->route("coffe.denied", ["user" => true])}'>Simular Usuário</a>";
        return false;
    }
}