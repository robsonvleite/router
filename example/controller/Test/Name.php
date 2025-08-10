<?php

namespace Test;

use CoffeeCode\Router\Router;

class Name
{
    /** @var Router */
    private Router $router;

    public function __construct($router)
    {
        $this->router = $router;
        $params = ["category" => 23213, "page" => 2];

        echo "<p>Named routes:</p>";
        echo "<nav>
            <a href='{$this->router->route("name.home")}'>Home</a> | 
            <a href='{$this->router->route("name.hello")}'>Hello</a> | 
            <a href='{$this->router->route("name.params", $params)}'>Params</a> | 
            <a href='{$this->router->route("name.redirect")}'>Redirect Back</a>
        </nav>";
    }

    public function home(): void
    {
        echo "<h3>Home</h3>";
        echo "<p>", $this->router->route("name.home"), "</p>";
        echo "<p>", $this->router->route("name.hello"), "</p>";
        echo "<p>", $this->router->route("name.redirect"), "</p>";
    }

    public function hello(): void
    {
        echo "<h3>Hello World</h3>";
        echo "<a href='{$this->router->route("name.params", ["category" => 6, "page" => 1])}'>Route Params</a>";
    }

    public function params(array $data): void
    {
        echo "<h3>Params</h3>";
        var_dump($data, $this->router->current());
    }

    public function redirect(array $data): void
    {
        if ($data) {
            $this->router->redirect("name.params", $data);
        }

        $this->router->redirect(BASE);
    }
}