<?php

namespace CoffeeCode\Router;

/**
 * Trait RouterTrait
 * @package CoffeeCode\Router
 */
trait RouterTrait
{
    /**
     * @param string $method
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    protected function addRoute(
        string $method,
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $route = rtrim($route, "/");
        if ($route === "") {
            $route = "/";
        }

        // Apply group prefix to the route for matching and storage
        $fullRoute = (!$this->group ? $route : "/{$this->group}{$route}");
        // Normalize double slashes and ensure root stays "/"
        $fullRoute = preg_replace('~//+~', '/', $fullRoute);
        if ($fullRoute === "") {
            $fullRoute = "/";
        }

        // Extract parameter names and optional constraints {name:pattern}
        preg_match_all('~{([a-zA-Z_][a-zA-Z0-9_-]*)(?::([^}]+))?}~', $fullRoute, $tokens, PREG_SET_ORDER);
        $paramNames = [];
        $regex = preg_replace_callback(
            '~{([a-zA-Z_][a-zA-Z0-9_-]*)(?::([^}]+))?}~',
            static function ($m) use (&$paramNames) {
                $paramNames[] = $m[1];
                $pattern = isset($m[2]) && $m[2] !== '' ? $m[2] : '[^/]+';
                return '(' . $pattern . ')';
            },
            $fullRoute
        );

        // Prepare route payload (data will be resolved at dispatch time)
        $namespace = $this->namespace;
        $resolvedMiddleware = $middleware ?? (!empty($this->middleware[$this->group]) ? $this->middleware[$this->group] : null);

        $router = function () use ($method, $handler, $fullRoute, $name, $namespace, $resolvedMiddleware, $paramNames) {
            return [
                "route" => $fullRoute,
                "name" => $name,
                "method" => $method,
                "middlewares" => $resolvedMiddleware,
                "handler" => $this->handler($handler, $namespace),
                "action" => $this->action($handler),
                "data" => null,
                "paramNames" => $paramNames
            ];
        };

        // Store compiled regex as key so dispatch can match quickly
        $this->routes[$method][$regex] = $router();
    }

    /**
     * httpMethod form spoofing
     */
    protected function formSpoofing(): void
    {
        // Usa $_POST diretamente se filter_input_array retornar null (útil para testes)
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? $_POST ?? [];

        if (!empty($post['_method']) && in_array($post['_method'], ["PUT", "PATCH", "DELETE"])) {
            $this->httpMethod = $post['_method'];
            $this->data = $post;

            unset($this->data["_method"]);
            return;
        }

        if ($this->httpMethod == "POST") {
            $this->data = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? $_POST ?? [];

            if (isset($this->data["_method"])) {
                unset($this->data["_method"]);
            }
            return;
        }

        if (in_array($this->httpMethod, ["PUT", "PATCH", "DELETE"]) && !empty($_SERVER['CONTENT_LENGTH'])) {
            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
            $this->data = $putPatch;

            if (isset($this->data["_method"])) {
                unset($this->data["_method"]);
            }
            return;
        }

        $this->data = [];
    }

    /**
     * @return bool
     */
    private function execute(): bool
    {
        if ($this->route) {
            if (!$this->middleware()) {
                return false;
            }

            if (is_callable($this->route['handler'])) {
                call_user_func($this->route['handler'], ($this->route['data'] ?? []), $this);
                return true;
            }

            $controller = $this->route['handler'];
            $method = $this->route['action'];

            if (class_exists($controller)) {
                $newController = new $controller($this);
                if (method_exists($controller, $method)) {
                    $newController->$method(($this->route['data'] ?? []));
                    return true;
                }

                $this->error = self::METHOD_NOT_ALLOWED;
                return false;
            }

            $this->error = self::BAD_REQUEST;
            return false;
        }

        $this->error = self::NOT_FOUND;
        return false;
    }

    /**
     * @return bool
     */
    private function middleware(): bool
    {
        if (empty($this->route["middlewares"])) {
            return true;
        }

        $middlewares = is_array(
            $this->route["middlewares"]
        ) ? $this->route["middlewares"] : [$this->route["middlewares"]];

        foreach ($middlewares as $middleware) {
            if (class_exists($middleware)) {
                $newMiddleware = new $middleware;
                if (method_exists($newMiddleware, "handle")) {
                    if (!$newMiddleware->handle($this)) {
                        return false;
                    }
                } else {
                    $this->error = self::METHOD_NOT_ALLOWED;
                    return false;
                }
            } else {
                $this->error = self::NOT_IMPLEMENTED;
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable|string $handler
     * @param string|null $namespace
     * @return callable|string
     */
    private function handler(callable|string $handler, ?string $namespace): callable|string
    {
        return (!is_string($handler) ? $handler : "{$namespace}\\" . explode($this->separator, $handler)[0]);
    }

    /**
     * @param callable|string $handler
     * @return string|null
     */
    private function action(callable|string $handler): ?string
    {
        return (!is_string($handler) ?: (explode($this->separator, $handler)[1] ?? null));
    }

    /**
     * @param array $route_item
     * @param array|null $data
     * @return string|null
     */
    private function treat(array $route_item, ?array $data = null): ?string
    {
        $route = $route_item["route"];
        if (!empty($data)) {
            $arguments = [];
            $params = [];
            foreach ($data as $key => $value) {
                if (!strstr($route, "{{$key}}")) {
                    $params[$key] = $value;
                }
                $arguments["{{$key}}"] = $value;
            }
            $route = $this->process($route, $arguments, $params);
        }

        return "{$this->projectUrl}{$route}";
    }

    /**
     * @param string $route
     * @param array $arguments
     * @param array|null $params
     * @return string
     */
    private function process(string $route, array $arguments, ?array $params = null): string
    {
        $params = (!empty($params) ? "?" . http_build_query($params) : null);
        return str_replace(array_keys($arguments), array_values($arguments), $route) . "{$params}";
    }
}