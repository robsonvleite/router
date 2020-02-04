<?php

namespace CoffeeCode\Router;

/**
 * Trait CoffeeCode RouterTrait
 *
 * @author  Robson V. Leite <https://github.com/robsonvleite>
 * @package CoffeeCode\Router
 */
trait RouterTrait
{
    /** @var array */
    protected $routes;
    
    /** @var string */
    protected $patch;
    
    /** @var string */
    protected $httpMethod;
    
    /**
     * @param string     $name
     * @param array|null $data
     *
     * @return string|null
     */
    public function route(string $name, array $data = null): ?string
    {
        foreach ($this->routes as $http_verb) {
            $routes = [];
            
            foreach ($http_verb as $route_item) {
                if (!empty($route_item["name"]) && $route_item["name"] == $name) {
                    $routes[] = $route_item;
                }
            }
            
            if (!empty($routes)) {
                return $this->treat($routes, $data);
            }
        }
        return null;
    }
    
    /**
     * @param string     $route
     * @param array|null $data
     */
    public function redirect(string $route, array $data = null): void
    {
        if ($name = $this->route($route, $data)) {
            header("Location: {$name}");
            exit;
        }
        
        if (filter_var($route, FILTER_VALIDATE_URL)) {
            header("Location: {$route}");
            exit;
        }
        
        $route = (substr($route, 0, 1) == "/" ? $route : "/{$route}");
        header("Location: {$this->projectUrl}{$route}");
        exit;
    }
    
    /**
     * @param string          $method
     * @param string          $route
     * @param string|callable $handler
     * @param null|string
     */
    protected function addRoute(string $method, string $route, $handler, string $name = null): void
    {
        if ($route == "/") {
            $this->addRoute($method, "", $handler, $name);
        }
        
        preg_match_all("~\{\s* ([a-zA-Z_][a-zA-Z0-9_-]*) \}~x", $route, $keys, PREG_SET_ORDER);
        $routeDiff = array_values(array_diff(explode("/", $this->patch), explode("/", $route)));
        
        $this->formSpoofing();
        $offset = ($this->group ? 1 : 0);
        foreach ($keys as $key) {
            $this->data[$key[1]] = ($routeDiff[$offset++] ?? null);
        }
        
        $route = (!$this->group ? $route : "/{$this->group}{$route}");
        $data = $this->data;
        $namespace = $this->namespace;
        $router = function () use ($method, $handler, $data, $route, $name, $namespace) {
            return [
                "route" => $route,
                "name" => $name,
                "method" => $method,
                "handler" => $this->handler($handler, $namespace),
                "action" => $this->action($handler),
                "data" => $data
            ];
        };
        
        $route = preg_replace('~{([^}]*)}~', "([^/]+)", $route);
        $this->routes[$method][$route] = $router();
    }
    
    /**
     * @param $handler
     * @param $namespace
     *
     * @return string|callable
     */
    private function handler($handler, $namespace)
    {
        return (!is_string($handler) ? $handler : "{$namespace}\\" . explode($this->separator, $handler)[0]);
    }
    
    /**
     * @param $handler
     *
     * @return null|string
     */
    private function action($handler): ?string
    {
        return (!is_string($handler) ?: (explode($this->separator, $handler)[1] ?? null));
    }
    
    /**
     * @param array      $routes
     * @param array|null $data
     *
     * @return string|null
     */
    private function treat(array $routes, array $data = null): ?string
    {
        $route = $routes[0]["route"];
        if (!empty($data)) {
            $arguments = [];
            $params = [];
            foreach ($data as $key => $value) {
                $new_route = $this->bindData($routes, $arguments, $params, $key, $value);
                if ($new_route == null) {
                    $params[$key] = $value;
                    continue;
                }
                $route = $new_route;
            }
        }
        
        return "{$this->projectUrl}{$route}";
    }
    
    /**
     * @param array  $routes
     * @param array  $args
     * @param array  $params
     * @param string $key
     * @param string $value
     *
     * @return string|null
     */
    private function bindData(array $routes, array &$args, array &$params, string $key, string $value): ?string
    {
        foreach ($routes as $route_item) {
            if (strstr($route_item["route"], "{{$key}}")) {
                $arguments["{{$key}}"] = $value;
                return $this->process($route_item["route"], $arguments, $params);
            }
        }
        return null;
    }
    
    /**
     * @param string     $route
     * @param array      $arguments
     * @param array|null $params
     *
     * @return string
     */
    private function process(string $route, array $arguments, array $params = null): string
    {
        $params = (!empty($params) ? "?" . http_build_query($params) : null);
        return str_replace(array_keys($arguments), array_values($arguments), $route) . "{$params}";
    }
}