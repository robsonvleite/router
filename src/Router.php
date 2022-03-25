<?php

namespace CoffeeCode\Router;

use function ICanBoogie\singularize;

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
     * @param $handler
     * @param string|null $name
     */
    public function post(string $route, $handler, string $name = null): void
    {
        $this->addRoute("POST", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param $handler
     * @param string|null $name
     */
    public function get(string $route, $handler, string $name = null): void
    {
        $this->addRoute("GET", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param $handler
     * @param string|null $name
     */
    public function put(string $route, $handler, string $name = null): void
    {
        $this->addRoute("PUT", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param $handler
     * @param string|null $name
     */
    public function patch(string $route, $handler, string $name = null): void
    {
        $this->addRoute("PATCH", $route, $handler, $name);
    }

    /**
     * @param string $route
     * @param $handler
     * @param string|null $name
     */
    public function delete(string $route, $handler, string $name = null): void
    {
        $this->addRoute("DELETE", $route, $handler, $name);
    }

    /**
     * @param string      $route
     * @param             $handler
     * @param string|null $name
     */
    public function resource(string $route, $handler, string $name = null, string $model_id_name = null): void
    {
        $sanitRoute = substr($route, 0, 1) == '/' ? substr_replace($route, '', 0, 1) : $route;
        $sanitRoute = (explode('/', $sanitRoute))[0];
        $singularized_route = singularize($sanitRoute);
        $model_id_name = $model_id_name ?? $singularized_route;

        $this->addRoute("GET", $route, $handler.":list", ($name ? "{$name}.list" : null));
        $this->addRoute("GET", "{$route}/home", $handler.":list", ($name ? "{$name}.home" : null));
        $this->addRoute("GET", "{$route}/home/{search}/{page}", $handler.":list", ($name ? "{$name}.searchGet" : null));
        $this->addRoute("GET", $singularized_route, $handler.":create", ($name ? "{$name}.create" : null));
        $this->addRoute("GET", singularize($route)."/{".$model_id_name."}", $handler.":edit", ($name ? "{$name}.edit" : null));
        $this->addRoute("POST", "{$route}/search", $handler.":search", ($name ? "{$name}.searchPost" : null));
        $this->addRoute("POST", $singularized_route, $handler.":store", ($name ? "{$name}.store" : null));
        $this->addRoute("POST", singularize($route)."/{".$model_id_name."}", $handler.":update", ($name ? "{$name}.update" : null));
        $this->addRoute("DELETE", singularize($route)."/{".$model_id_name."}", $handler.":delete", ($name ? "{$name}.delete" : null));
    }
}