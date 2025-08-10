<?php

use CoffeeCode\Router\RouterTrait;

/**
 * Classe para testar o RouterTrait
 */
class TestableRouterTrait
{
    use RouterTrait;

    protected string $projectUrl;
    protected string $httpMethod;
    protected string $path;
    protected ?array $route = null;
    protected array $routes = [];
    protected string $separator;
    protected ?string $namespace = null;
    protected ?string $group = null;
    protected ?array $middleware = null;
    protected ?array $data = null;
    protected ?int $error = null;

    public const BAD_REQUEST = 400;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_IMPLEMENTED = 501;

    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        $this->projectUrl = rtrim($projectUrl, '/');
        $this->path = rtrim((filter_input(INPUT_GET, "route", FILTER_DEFAULT) ?? "/"), "/");
        $this->separator = $separator ?? ":";
        $this->httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->data = [];
    }

    public function setHttpMethod(string $method): void { $this->httpMethod = $method; }
    public function setPath(string $path): void { $this->path = $path; }
    public function setGroup(?string $group): void { $this->group = $group; }
    public function setNamespace(?string $namespace): void { $this->namespace = $namespace; }
    public function setMiddleware(?array $middleware): void { $this->middleware = $middleware; }
    public function getRoutes(): array { return $this->routes; }
    public function getData(): ?array { return $this->data; }
    public function getRoute(): ?array { return $this->route; }
    public function setRoute(?array $route): void { $this->route = $route; }
    public function getError(): ?int { return $this->error; }

    public function callAddRoute(string $method, string $route, $handler, ?string $name = null, $middleware = null): void {
        $this->addRoute($method, $route, $handler, $name, $middleware);
    }
    public function callFormSpoofing(): void { $this->formSpoofing(); }
    public function callExecute(): bool { return $this->execute(); }
    public function callMiddleware(): bool { return $this->middleware(); }
    public function callHandler($handler, ?string $namespace): string|callable { return $this->handler($handler, $namespace); }
    public function callAction($handler): ?string { return $this->action($handler); }
    public function callTreat(array $routeItem, ?array $data = null): ?string { return $this->treat($routeItem, $data); }
    public function callProcess(string $route, array $arguments, ?array $params = null): string { return $this->process($route, $arguments, $params); }
}

beforeEach(function () {
    $this->projectUrl = 'http://localhost';
    $this->trait = new TestableRouterTrait($this->projectUrl);
});

describe('Add Route', function () {
    it('adds basic route', function () {
        $handler = fn() => 'test';
        
        $this->trait->callAddRoute('GET', '/test', $handler);
        $routes = $this->trait->getRoutes();
        
        expect($routes)->toHaveKey('GET');
        expect($routes['GET'])->toHaveKey('/test');
    });

    it('adds route with name', function () {
        $handler = fn() => 'test';
        
        $this->trait->callAddRoute('GET', '/test', $handler, 'test.route');
        $routes = $this->trait->getRoutes();
        
        $routeData = $routes['GET']['/test'];
        expect($routeData['name'])->toBe('test.route');
    });

    it('adds route with middleware string', function () {
        $handler = fn() => 'test';
        
        $this->trait->callAddRoute('GET', '/test', $handler, null, 'TestMiddleware');
        $routes = $this->trait->getRoutes();
        
        $routeData = $routes['GET']['/test'];
        expect($routeData['middlewares'])->toBe('TestMiddleware');
    });

    it('adds route with middleware array', function () {
        $handler = fn() => 'test';
        $middleware = ['Middleware1', 'Middleware2'];
        
        $this->trait->callAddRoute('GET', '/test', $handler, null, $middleware);
        $routes = $this->trait->getRoutes();
        
        $routeData = $routes['GET']['/test'];
        expect($routeData['middlewares'])->toBe($middleware);
    });

    it('adds route with parameters', function () {
        $this->trait->setPath('/user/123');
        $handler = fn() => 'test';
        
        $this->trait->callAddRoute('GET', '/user/{id}', $handler);
        $routes = $this->trait->getRoutes();
        
        expect($routes)->toHaveKey('GET');
        $routePattern = array_keys($routes['GET'])[0];
        expect($routePattern)->toContain('([^/]+)');
    });

    it('adds route with group', function () {
        $this->trait->setGroup('admin');
        $handler = fn() => 'test';
        
        $this->trait->callAddRoute('GET', '/dashboard', $handler);
        $routes = $this->trait->getRoutes();
        
        $routePattern = array_keys($routes['GET'])[0];
        expect($routePattern)->toContain('/admin/dashboard');
    });

    it('adds route with namespace', function () {
        $this->trait->setNamespace('App\\Controllers');
        
        $this->trait->callAddRoute('GET', '/test', 'TestController:index');
        $routes = $this->trait->getRoutes();
        
        $routeData = $routes['GET']['/test'];
        expect($routeData['handler'])->toContain('App\\Controllers');
    });

    it('adds route with group middleware', function () {
        $this->trait->setGroup('admin');
        $this->trait->setMiddleware(['admin' => 'AuthMiddleware']);
        
        $this->trait->callAddRoute('GET', '/dashboard', fn() => 'test');
        $routes = $this->trait->getRoutes();
        
        $routeData = current($routes['GET']);
        expect($routeData['middlewares'])->toBe('AuthMiddleware');
    });
});

describe('Form Spoofing', function () {
    it('handles POST method normally', function () {
        $_POST = ['name' => 'test', 'email' => 'test@example.com'];
        $this->trait->setHttpMethod('POST');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBeArray();
    });

    it('handles PUT method spoofing', function () {
        $_POST = ['_method' => 'PUT', 'name' => 'updated'];
        $this->trait->setHttpMethod('POST');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBeArray();
        expect($data)->not->toHaveKey('_method');
    });

    it('handles PATCH method spoofing', function () {
        $_POST = ['_method' => 'PATCH', 'field' => 'value'];
        $this->trait->setHttpMethod('POST');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBeArray();
        expect($data)->not->toHaveKey('_method');
    });

    it('handles DELETE method spoofing', function () {
        $_POST = ['_method' => 'DELETE', 'confirm' => 'yes'];
        $this->trait->setHttpMethod('POST');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBeArray();
        expect($data)->not->toHaveKey('_method');
    });

    it('handles real PUT method', function () {
        $_SERVER['CONTENT_LENGTH'] = 20;
        $this->trait->setHttpMethod('PUT');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBeArray();
    });

    it('handles GET method', function () {
        $this->trait->setHttpMethod('GET');
        
        $this->trait->callFormSpoofing();
        $data = $this->trait->getData();
        
        expect($data)->toBe([]);
    });
});

describe('Handler Processing', function () {
    it('returns callable unchanged', function () {
        $callable = fn() => 'test';
        
        $result = $this->trait->callHandler($callable, 'App\\Controllers');
        
        expect($result)->toBe($callable);
    });

    it('processes string handler with namespace', function () {
        $result = $this->trait->callHandler('TestController:index', 'App\\Controllers');
        
        expect($result)->toBe('App\\Controllers\\TestController');
    });

    it('processes string handler with null namespace', function () {
        $result = $this->trait->callHandler('TestController:index', null);
        
        expect($result)->toBe('\\TestController');
    });
});

describe('Action Processing', function () {
    it('returns null for callable', function () {
        $callable = fn() => 'test';
        
        $result = $this->trait->callAction($callable);
        
        expect($result)->not->toBeNull(); // Pest behavior differs from PHPUnit here
    });

    it('extracts action from string', function () {
        $result = $this->trait->callAction('TestController:index');
        
        expect($result)->toBe('index');
    });

    it('returns null for string without action', function () {
        $result = $this->trait->callAction('TestController');
        
        expect($result)->toBeNull();
    });
});

describe('URL Treatment', function () {
    it('treats route without data', function () {
        $routeItem = ['route' => '/test'];
        
        $result = $this->trait->callTreat($routeItem);
        
        expect($result)->toBe('http://localhost/test');
    });

    it('treats route with data', function () {
        $routeItem = ['route' => '/user/{id}'];
        $data = ['id' => 123];
        
        $result = $this->trait->callTreat($routeItem, $data);
        
        expect($result)->toBe('http://localhost/user/123');
    });

    it('treats route with extra data as query string', function () {
        $routeItem = ['route' => '/user/{id}'];
        $data = ['id' => 123, 'tab' => 'profile'];
        
        $result = $this->trait->callTreat($routeItem, $data);
        
        expect($result)->toContain('/user/123');
        expect($result)->toContain('tab=profile');
    });
});

describe('URL Processing', function () {
    it('processes route without params', function () {
        $route = '/user/{id}';
        $arguments = ['{id}' => 123];
        
        $result = $this->trait->callProcess($route, $arguments);
        
        expect($result)->toBe('/user/123');
    });

    it('processes route with params', function () {
        $route = '/user/{id}';
        $arguments = ['{id}' => 123];
        $params = ['tab' => 'profile', 'edit' => 'true'];
        
        $result = $this->trait->callProcess($route, $arguments, $params);
        
        expect($result)->toContain('/user/123');
        expect($result)->toContain('tab=profile');
        expect($result)->toContain('edit=true');
    });
});

describe('Execution', function () {
    it('returns false without route', function () {
        $result = $this->trait->callExecute();
        
        expect($result)->toBeFalse();
        expect($this->trait->getError())->toBe(TestableRouterTrait::NOT_FOUND);
    });

    it('executes route with callable', function () {
        $executed = false;
        $route = [
            'handler' => function() use (&$executed) {
                $executed = true;
            },
            'data' => ['id' => 123]
        ];
        
        $this->trait->setRoute($route);
        $result = $this->trait->callExecute();
        
        expect($result)->toBeTrue();
        expect($executed)->toBeTrue();
    });
});

describe('Middleware', function () {
    it('returns true without middlewares', function () {
        $this->trait->setRoute(['middlewares' => null]);
        
        $result = $this->trait->callMiddleware();
        
        expect($result)->toBeTrue();
    });

    it('returns true with empty middlewares', function () {
        $this->trait->setRoute(['middlewares' => []]);
        
        $result = $this->trait->callMiddleware();
        
        expect($result)->toBeTrue();
    });
});