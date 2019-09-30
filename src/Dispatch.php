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

    /** @var null|array */
    protected $route;

    /** @var bool|string */
    protected $projectUrl;

    /** @var string */
    protected $separator;

    /** @var null|string */
    protected $namespace;

    /** @var null|string */
    protected $group;

    /** @var null|array */
    protected $data;

    /** @var int */
    protected $error;

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
        $this->patch = (filter_input(INPUT_GET, "route", FILTER_DEFAULT) ?? "/");
        $this->separator = ($separator ?? ":");
        $this->httpMethod = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return $this->routes;
    }

    /**
     * @param null|string $namespace
     * @return Dispatch
     */
    public function namespace(?string $namespace): Dispatch
    {
        $this->namespace = ($namespace ? ucwords($namespace) : null);
        return $this;
    }

    /**
     * @param null|string $group
     * @return Dispatch
     */
    public function group(?string $group): Dispatch
    {
        $this->group = ($group ? str_replace("/", "", $group) : null);
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
        if (empty($this->routes) || empty($this->routes[$this->httpMethod])) {
            $this->error = self::NOT_IMPLEMENTED;
            return false;
        }

        $this->route = null;
        foreach ($this->routes[$this->httpMethod] as $key => $route) {
            if (preg_match("~^" . $key . "$~", $this->patch, $found)) {
                $this->route = $route;
            }
        }

        return $this->execute();
    }

    /**
     * @return bool
     */
    private function execute()
    {
        if ($this->route) {
            if (is_callable($this->route['handler'])) {
                call_user_func($this->route['handler'], ($this->route['data'] ?? []));
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
     * httpMethod form spoofing
     */
    protected function formSpoofing(): void
    {
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($post['_method']) && in_array($post['_method'], ["PUT", "PATCH", "DELETE"])) {
            $this->httpMethod = $post['_method'];
            $this->data = $post;

            unset($this->data["_method"]);
            return;
        }

        if ($this->httpMethod == "POST") {
            $this->data = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            unset($this->data["_method"]);
            return;
        }

        if (in_array($this->httpMethod, ["PUT", "PATCH", "DELETE"]) && !empty($_SERVER['CONTENT_LENGTH'])) {
            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
            $this->data = $putPatch;

            unset($this->data["_method"]);
            return;
        }

        $this->data = [];
        return;
    }
}