<?php

namespace Test;

class Name
{
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

    public function hello(): void
    {
        echo "<h1>Hello World</h1>";
    }

    public function redirect(): void
    {
        $this->router->redirect("name.hello");
    }
}