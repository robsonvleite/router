<?php

namespace Http;

use CoffeeCode\Router\Router;

class User
{
    public function handle(Router $router): bool
    {
        echo "<p><i>O middleware User foi executado!</i></p>";

        $user = true;
        if ($user) {
            return true;
        }
        return false;
    }
}
