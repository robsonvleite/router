<?php

namespace CoffeeCode\Router;

/**
 * Class CoffeeCode Dispatch
 *
 * @author Robson V. Leite <https://github.com/robsonvleite>
 * @package CoffeeCode\Router
 */
abstract class Dispatch
{
    use RouterTrait;

    /** @var string */
    protected string $projectUrl;

    /** @var string */
    protected string $httpMethod;

    /** @var string */
    protected string $path;

    /** @var array|null */
    protected ?array $route = null;

    /** @var array */
    protected array $routes;

    /** @var string */
    protected string $separator;

    /** @var string|null */
    protected ?string $namespace = null;

    /** @var string|null */
    protected ?string $group = null;

    /** @var array|null */
    protected ?array $middleware = null;

    /** @var array|null */
    protected ?array $data = null;

    /** @var int */
    protected ?int $error = null;

    /** @var string|null */
    protected ?string $assetsPath = null;

    /** @var bool Debug response headers toggle */
    protected bool $debugHeaders = false;

    /** @const int Bad Request */
    public const BAD_REQUEST = 400;

    /** @const int Not Found */
    public const NOT_FOUND = 404;

    /** @const int Method Not Allowed */
    public const METHOD_NOT_ALLOWED = 405;

    /** @const int Not Implemented */
    public const NOT_IMPLEMENTED = 501;
    
    /**
     * Dispatch constructor.
     *
     * @param string $projectUrl
     * @param null|string $separator
     */
    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        $this->projectUrl = (substr($projectUrl, "-1") == "/" ? substr($projectUrl, 0, -1) : $projectUrl);
        $this->separator = ($separator ?? ":");

        // Detect method with support for X-HTTP-Method-Override
        $this->httpMethod = $this->detectHttpMethod();

        // Resolve path autonomously (REQUEST_URI), but keep compatibility with ?route=
        $this->path = $this->resolvePath();

        $this->routes = [];
    }

    /**
     * Enable/disable debug headers in responses.
     *
     * @param bool $enable
     * @return Dispatch
     */
    public function setDebugHeaders(bool $enable): Dispatch
    {
        $this->debugHeaders = $enable;
        return $this;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return $this->routes;
    }

    /**
     * Detect HTTP method with support for override header.
     *
     * @return string
     */
    private function detectHttpMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Honor X-HTTP-Method-Override for environments without method spoofing
        if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            if (in_array($override, ["GET", "POST", "PUT", "PATCH", "DELETE"], true)) {
                $method = $override;
            }
        }

        return $method;
    }

    /**
     * Resolve current request path autonomously from REQUEST_URI and script base.
     * Falls back to ?route= for compatibility with .htaccess/Nginx rewrites or tests.
     *
     * @return string
     */
    private function resolvePath(): string
    {
        // Backward compatibility: honor explicit ?route=
        $routeParam = filter_input(INPUT_GET, "route", FILTER_DEFAULT);
        if ($routeParam !== null) {
            $path = "/" . ltrim($routeParam, "/");
            $normalized = rtrim($path, "/");
            return ($normalized === "" ? "/" : $normalized);
        }

        // Autonomous resolution: derive from REQUEST_URI minus script base dir and project base path
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? "/", PHP_URL_PATH) ?? "/";
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? "";
        $baseDir = rtrim(str_replace("\\", "/", dirname($scriptName)), "/");

        if ($baseDir && strpos($uriPath, $baseDir) === 0) {
            $uriPath = substr($uriPath, strlen($baseDir));
        }

        // Also honor base path embedded in the configured project URL
        $projectBase = parse_url($this->projectUrl, PHP_URL_PATH) ?: '';
        if ($projectBase && $projectBase !== '/' && strpos($uriPath, $projectBase) === 0) {
            $uriPath = substr($uriPath, strlen($projectBase));
        }

        // Honor proxy forwarded prefix if present (e.g., when mounted under a subpath by a reverse proxy)
        $xfp = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';
        if (is_string($xfp)) {
            $xfp = rtrim($xfp, '/');
            if ($xfp !== '' && strpos($uriPath, $xfp) === 0) {
                $uriPath = substr($uriPath, strlen($xfp));
            }
        }

        $path = "/" . ltrim($uriPath, "/");
        $normalized = rtrim($path, "/");
        return ($normalized === "" ? "/" : $normalized);
    }

    /**
     * @param string $name
     * @param array|null $data
     * @return string|null
     */
    public function route(string $name, ?array $data = null): ?string
    {
        foreach ($this->routes as $http_verb) {
            foreach ($http_verb as $route_item) {
                if (!empty($route_item["name"]) && $route_item["name"] == $name) {
                    return $this->treat($route_item, $data);
                }
            }
        }
        return null;
    }

    /**
     * @param null|string $namespace
     * @return Dispatch
     */
    public function namespace(?string $namespace): Dispatch
    {
        if ($namespace) {
            // Converte cada parte do namespace para ucfirst, mantendo as barras
            $parts = explode('\\', $namespace);
            $parts = array_map('ucfirst', $parts);
            $this->namespace = implode('\\', $parts);
        } else {
            $this->namespace = null;
        }
        return $this;
    }

    /**
     * @param null|string $group
     * @return Dispatch
     */
    public function group(?string $group, array|string|null $middleware = null): Dispatch
    {
        $this->group = ($group ? trim($group, "/") : null);
        $this->middleware = $middleware ? [$this->group => $middleware] : null;
        return $this;
    }

    /**
     * @return null|array
     */
    public function data(): ?array
    {
        return $this->data;
    }

    /**
     * @return object|null
     */
    public function current(): ?object
    {
        return (object)array_merge(
            [
                "namespace" => $this->namespace,
                "group" => $this->group,
                "path" => $this->path
            ],
            $this->route ?? []
        );
    }

    /**
     * @return string
     */
    public function home(): string
    {
        return $this->projectUrl;
    }

    /**
     * @param string $route
     * @param array|null $data
     */
    public function redirect(string $route, ?array $data = null): void
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
     * @return null|int
     */
    public function error(): ?int
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function dispatch(): bool
    {
        if ($this->assetsPath) {
            $this->assets($this->assetsPath);
        }

        // Explicitly block unsafe/unsupported HTTP methods
        if (in_array($this->httpMethod, ['TRACE', 'CONNECT'], true)) {
            $this->error = self::METHOD_NOT_ALLOWED;
            return false;
        }

        // Automatic OPTIONS response advertising allowed methods for the matched resource
        if ($this->httpMethod === 'OPTIONS') {
            $allowed = [];

            // Scan all registered routes to find allowed methods for this path
            foreach ($this->routes as $method => $routes) {
                foreach ($routes as $pattern => $route) {
                    if (preg_match("~^{$pattern}$~", $this->path)) {
                        $allowed[$method] = true;
                    }
                }
            }

            // HEAD is implicitly allowed if GET exists
            if (!empty($allowed['GET'])) {
                $allowed['HEAD'] = true;
            }

            // OPTIONS is always allowed
            $allowed['OPTIONS'] = true;

            if (!empty($allowed)) {
                header('Allow: ' . implode(', ', array_keys($allowed)));
                return true;
            }

            // No routes match this path: preserve current behavior by signaling not implemented
            $this->error = self::NOT_IMPLEMENTED;
            return false;
        }

        // Ensure request payload/state is prepared (POST/body/spoofed methods, etc)
        $this->formSpoofing();

        // Determine routes for method, with HEAD fallback to GET when HEAD is not explicitly registered
        $methodRoutes = $this->routes[$this->httpMethod] ?? [];
        if (empty($methodRoutes) && $this->httpMethod === 'HEAD' && !empty($this->routes['GET'])) {
            $methodRoutes = $this->routes['GET'];
        }

        // If no routes for this method, but the path matches other methods -> 405 with Allow
        if (empty($methodRoutes)) {
            $allowed = [];
            foreach ($this->routes as $m => $routes) {
                foreach ($routes as $pattern => $r) {
                    if (preg_match("~^{$pattern}$~", $this->path)) {
                        $allowed[$m] = true;
                    }
                }
            }

            if (!empty($allowed)) {
                if (!empty($allowed['GET'])) {
                    $allowed['HEAD'] = true;
                }
                $allowed['OPTIONS'] = true;
                header('Allow: ' . implode(', ', array_keys($allowed)));
                $this->error = self::METHOD_NOT_ALLOWED; // 405
                return false;
            }

            // No routes match this path for any method
            $this->error = self::NOT_IMPLEMENTED;
            return false;
        }

        // Try to match route for the current method and capture parameters
        $this->route = null;
        $matchedPattern = null;
        foreach ($methodRoutes as $key => $route) {
            if (preg_match("~^" . $key . "$~", $this->path, $matches)) {
                // Build data from captured params + request data
                $captured = [];
                if (!empty($route['paramNames'])) {
                    array_shift($matches); // remove full match
                    foreach ($route['paramNames'] as $i => $paramName) {
                        $captured[$paramName] = $matches[$i] ?? null;
                    }
                }
                $route['data'] = array_merge($this->data ?? [], $captured);
                $this->route = $route;
                $matchedPattern = $key;
                break;
            }
        }

        if (!$this->route) {
            // Path exists for some method but not this one? Return 405 with Allow
            $allowed = [];
            foreach ($this->routes as $m => $routes) {
                foreach ($routes as $pattern => $r) {
                    if (preg_match("~^{$pattern}$~", $this->path)) {
                        $allowed[$m] = true;
                    }
                }
            }
            if (!empty($allowed)) {
                if (!empty($allowed['GET'])) {
                    $allowed['HEAD'] = true;
                }
                $allowed['OPTIONS'] = true;
                header('Allow: ' . implode(', ', array_keys($allowed)));
                $this->error = self::METHOD_NOT_ALLOWED; // 405
                return false;
            }

            $this->error = self::NOT_FOUND;
            return false;
        }

        // Emit debug headers if enabled
        if ($this->debugHeaders) {
            header('X-Router-Method: ' . $this->httpMethod);
            header('X-Router-Pattern: ' . ($matchedPattern ?? ''));
            if (!empty($this->route['name'])) {
                header('X-Router-Name: ' . $this->route['name']);
            }
        }

        // For HEAD requests, execute the handler logic but suppress body output
        if ($this->httpMethod === 'HEAD') {
            ob_start();
            $ok = $this->execute();
            ob_end_clean();
            return $ok;
        }

        return $this->execute();
    }
}