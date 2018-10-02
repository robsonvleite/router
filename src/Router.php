<?php

namespace CoffeeCode\Router;

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
     * @param string|callable $handler
     */
    public function post(string $route, $handler): void
    {
        $this->addRoute("POST", $route, $handler);
    }

    /**
     * @param string $route
     * @param string|callable $handler
     */
    public function get(string $route, $handler): void
    {
        $this->addRoute("GET", $route, $handler);
    }

    /**
     * @param string $route
     * @param string|callable $handler
     */
    public function put(string $route, $handler): void
    {
        $this->addRoute("PUT", $route, $handler);
    }

    /**
     * @param string $route
     * @param string|callable $handler
     */
    public function patch(string $route, $handler): void
    {
        $this->addRoute("PATCH", $route, $handler);
    }

    /**
     * @param string $route
     * @param string|callable $handler
     */
    public function delete(string $route, $handler): void
    {
        $this->addRoute("DELETE", $route, $handler);
    }
}