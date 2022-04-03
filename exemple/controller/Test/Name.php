<?php

namespace Test;

use CoffeeCode\Router\Router;

class Name
{
    /** @var Router */
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function home(): void
    {
        echo "<h1>Home</h1>";
        echo "<p>", $this->router->route("name.home"), "</p>";
        echo "<p>", $this->router->route("name.hello"), "</p>";
        echo "<p>", $this->router->route("name.redirect"), "</p>";
    }

    public function hello($data): void
    {
        echo "<h1>Hello World</h1>";
        echo "<a href='{$this->router->route("name.params", ["category" => 6, "page" => 1])}'>Route Params</a>";
    }

    public function params(array $data): void
    {
        var_dump($data);
    }

    public function redirect($data): void
    {
        if ($data) {
            //$data = category => *, page => *
            $this->router->redirect("name.params", $data);
        }

        $this->router->redirect("name.hello");
    }
}