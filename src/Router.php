<?php

namespace CoffeeCode\Router;

use Closure;

/**
 * Class CoffeeCode Router
 *
 * @author Robson V. Leite <https://github.com/robsonvleite>
 * @package CoffeeCode\Router
 */
class Router extends Dispatch
{
    /**
     * Router constructor.
     *
     * @param string $projectUrl
     * @param null|string $separator
     */
    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        parent::__construct($projectUrl, $separator);
    }

    /**
     * @param string $route
     * @param Closure|string $handler
     * @param string|null $name
     */
    public function post(string $route, Closure|string $handler, string $name = null): void
    {
        $this->addRoute("POST", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param Closure|string $handler
     * @param string|null $name
     */
    public function get(string $route, Closure|string $handler, string $name = null): void
    {
        $this->addRoute("GET", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param Closure|string $handler
     * @param string|null $name
     */
    public function put(string $route, Closure|string $handler, string $name = null): void
    {
        $this->addRoute("PUT", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param Closure|string $handler
     * @param string|null $name
     */
    public function patch(string $route, Closure|string $handler, string $name = null): void
    {
        $this->addRoute("PATCH", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param Closure|string $handler
     * @param string|null $name
     */
    public function delete(string $route, Closure|string $handler, string $name = null): void
    {
        $this->addRoute("DELETE", $route, $handler, $name);
    }
}