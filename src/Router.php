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
    public function resource(string $resource_name, $handler, string $name = null, string $model_id_name = null): void
    {
        $resource_name = substr($resource_name, 0, 1) == '/' ? $resource_name : "/".$resource_name;
        $sanitRoute = substr_replace($resource_name, '', 0, 1);
        $sanitRoute = (explode('/', $sanitRoute))[0];
        $model_id_name = $model_id_name ?? singularize($sanitRoute)."_id";

        $this->addRoute("GET", $resource_name, $handler.":list", ($name ? "{$name}.list" : null));
        $this->addRoute("GET", "{$resource_name}/home", $handler.":list", ($name ? "{$name}.home" : null));
        $this->addRoute("GET", "{$resource_name}/home/{search}/{page}", $handler.":list", ($name ? "{$name}.searchGet" : null));
        $this->addRoute("GET", singularize($resource_name), $handler.":create", ($name ? "{$name}.create" : null));
        $this->addRoute("GET", singularize($resource_name)."/{{$model_id_name}}", $handler.":edit", ($name ? "{$name}.edit" : null));
        $this->addRoute("POST", "{$resource_name}/search", $handler.":search", ($name ? "{$name}.searchPost" : null));
        $this->addRoute("POST", singularize($resource_name), $handler.":store", ($name ? "{$name}.store" : null));
        $this->addRoute("POST", singularize($resource_name)."/{{$model_id_name}}", $handler.":update", ($name ? "{$name}.update" : null));
        $this->addRoute("DELETE", singularize($resource_name)."/{{$model_id_name}}", $handler.":delete", ($name ? "{$name}.delete" : null));
    }
}