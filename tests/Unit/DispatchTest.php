<?php

use CoffeeCode\Router\Dispatch;

/**
 * Implementação concreta de Dispatch para testes
 */
class TestableDispatch extends Dispatch
{
    public function assets(string $assetsPath): void
    {
        // Implementação vazia para testes
    }

    public function get(string $route, callable|string $handler, ?string $name = null, array|string|null $middleware = null): void
    {
        $this->addRoute("GET", $route, $handler, $name, $middleware);
    }

    public function post(string $route, callable|string $handler, ?string $name = null, array|string|null $middleware = null): void
    {
        $this->addRoute("POST", $route, $handler, $name, $middleware);
    }

    public function put(string $route, callable|string $handler, ?string $name = null, array|string|null $middleware = null): void
    {
        $this->addRoute("PUT", $route, $handler, $name, $middleware);
    }

    public function patch(string $route, callable|string $handler, ?string $name = null, array|string|null $middleware = null): void
    {
        $this->addRoute("PATCH", $route, $handler, $name, $middleware);
    }

    public function delete(string $route, callable|string $handler, ?string $name = null, array|string|null $middleware = null): void
    {
        $this->addRoute("DELETE", $route, $handler, $name, $middleware);
    }
}

beforeEach(function () {
    $this->projectUrl = 'http://localhost';
    $this->dispatch = new TestableDispatch($this->projectUrl);
});

describe('Dispatch Constructor', function () {
    it('creates dispatch with project URL', function () {
        $dispatch = new TestableDispatch('http://example.com');
        expect($dispatch)->toBeInstanceOf(Dispatch::class);
        expect($dispatch->home())->toBe('http://example.com');
    });

    it('removes trailing slash from project URL', function () {
        $dispatch = new TestableDispatch('http://example.com/');
        expect($dispatch->home())->toBe('http://example.com');
    });

    it('accepts custom separator', function () {
        $dispatch = new TestableDispatch($this->projectUrl, '@');
        expect($dispatch)->toBeInstanceOf(Dispatch::class);
    });

    it('uses default separator when null provided', function () {
        $dispatch = new TestableDispatch($this->projectUrl, null);
        expect($dispatch)->toBeInstanceOf(Dispatch::class);
    });
});

describe('Basic Methods', function () {
    it('returns home URL', function () {
        expect($this->dispatch->home())->toBe($this->projectUrl);
    });

    it('returns debug info as routes array', function () {
        $this->dispatch->get('/test', fn() => 'test');
        $this->dispatch->post('/test', fn() => 'test');
        
        $debug = $this->dispatch->__debugInfo();
        expect($debug)->toBeArray();
        expect($debug)->toHaveKey('GET');
        expect($debug)->toHaveKey('POST');
    });
});

describe('Namespace Management', function () {
    it('sets namespace', function () {
        $result = $this->dispatch->namespace('App\\Controllers');
        expect($result)->toBeInstanceOf(Dispatch::class);
        
        $current = $this->dispatch->current();
        expect($current->namespace)->toBe('App\\Controllers');
    });

    it('sets namespace to null', function () {
        $result = $this->dispatch->namespace(null);
        expect($result)->toBeInstanceOf(Dispatch::class);
        
        $current = $this->dispatch->current();
        expect($current->namespace)->toBeNull();
    });

    it('formats namespace correctly', function () {
        $this->dispatch->namespace('app\\controllers');
        $current = $this->dispatch->current();
        expect($current->namespace)->toBe('App\\Controllers');
    });
});

describe('Group Management', function () {
    it('sets group', function () {
        $result = $this->dispatch->group('admin');
        expect($result)->toBeInstanceOf(Dispatch::class);
        
        $current = $this->dispatch->current();
        expect($current->group)->toBe('admin');
    });

    it('sets group to null', function () {
        $result = $this->dispatch->group(null);
        expect($result)->toBeInstanceOf(Dispatch::class);
        
        $current = $this->dispatch->current();
        expect($current->group)->toBeNull();
    });

    it('trims slashes from group', function () {
        $this->dispatch->group('/admin/');
        $current = $this->dispatch->current();
        expect($current->group)->toBe('admin');
    });

    it('sets group with middleware', function () {
        $middleware = 'AuthMiddleware';
        $this->dispatch->group('admin', $middleware);
        
        $current = $this->dispatch->current();
        expect($current->group)->toBe('admin');
    });

    it('sets group with middleware array', function () {
        $middleware = ['AuthMiddleware', 'AdminMiddleware'];
        $this->dispatch->group('admin', $middleware);
        
        $current = $this->dispatch->current();
        expect($current->group)->toBe('admin');
    });
});

describe('Data and State', function () {
    it('returns null data initially', function () {
        expect($this->dispatch->data())->toBeNull();
    });

    it('returns null error initially', function () {
        expect($this->dispatch->error())->toBeNull();
    });

    it('returns current state object', function () {
        $this->dispatch->namespace('App\\Controllers');
        $this->dispatch->group('admin');
        
        $current = $this->dispatch->current();
        
        expect($current)->toBeObject();
        expect($current->namespace)->toBe('App\\Controllers');
        expect($current->group)->toBe('admin');
        expect($current)->toHaveProperty('path');
    });
});

describe('Error Constants', function () {
    it('defines correct error constants', function () {
        expect(Dispatch::BAD_REQUEST)->toBe(400);
        expect(Dispatch::NOT_FOUND)->toBe(404);
        expect(Dispatch::METHOD_NOT_ALLOWED)->toBe(405);
        expect(Dispatch::NOT_IMPLEMENTED)->toBe(501);
    });
});

describe('Route Management', function () {
    it('returns null for non-existent route', function () {
        $result = $this->dispatch->route('nonexistent');
        expect($result)->toBeNull();
    });

    it('returns URL for existing route', function () {
        $this->dispatch->get('/test', fn() => 'test', 'test.route');
        
        $result = $this->dispatch->route('test.route');
        expect($result)->toBeString();
        expect($result)->toContain('/test');
    });

    it('returns URL for route with data', function () {
        $this->dispatch->get('/user/{id}', fn() => 'test', 'user.show');
        
        $result = $this->dispatch->route('user.show', ['id' => 123]);
        expect($result)->toBeString();
        expect($result)->toContain('123');
    });

    it('returns URL with query string for additional data', function () {
        $this->dispatch->get('/user/{id}', fn() => 'test', 'user.show');
        
        $result = $this->dispatch->route('user.show', ['id' => 123, 'tab' => 'profile']);
        expect($result)->toBeString();
        expect($result)->toContain('123');
        expect($result)->toContain('tab=profile');
    });
});

describe('Redirects', function () {
    it('redirects by route name', function () {
        $this->dispatch->get('/test', fn() => 'test', 'test.route');
        
        expect(fn() => $this->dispatch->redirect('test.route'))
            ->not->toThrow(Exception::class);
    });

    it('redirects with valid URL', function () {
        expect(fn() => $this->dispatch->redirect('https://example.com'))
            ->not->toThrow(Exception::class);
    });

    it('redirects with relative path', function () {
        expect(fn() => $this->dispatch->redirect('/admin/dashboard'))
            ->not->toThrow(Exception::class);
    });

    it('redirects with path without leading slash', function () {
        expect(fn() => $this->dispatch->redirect('admin/dashboard'))
            ->not->toThrow(Exception::class);
    });
});

describe('Dispatch Process', function () {
    it('returns false when no routes defined', function () {
        $result = $this->dispatch->dispatch();
        expect($result)->toBeFalse();
        expect($this->dispatch->error())->toBe(Dispatch::NOT_IMPLEMENTED);
    });

    it('returns false when no routes for current method', function () {
        simulateRequest('POST', '/test');
        
        // Adiciona apenas rota GET
        $this->dispatch->get('/test', fn() => 'test');
        
        $result = $this->dispatch->dispatch();
        expect($result)->toBeFalse();
        expect($this->dispatch->error())->toBe(Dispatch::METHOD_NOT_ALLOWED);
    });

    it('returns false when route not found', function () {
        simulateRequest('GET', '/nonexistent');
        
        $this->dispatch->get('/test', fn() => 'test');
        
        $result = $this->dispatch->dispatch();
        expect($result)->toBeFalse();
        expect($this->dispatch->error())->toBe(Dispatch::NOT_FOUND);
    });

    it('executes found route with callable', function () {
        simulateRequest('GET', '/test');
        
        $executed = false;
        $this->dispatch->get('/test', function() use (&$executed) {
            $executed = true;
        });
        
        $result = $this->dispatch->dispatch();
        expect($result)->toBeTrue();
        expect($executed)->toBeTrue();
    });

    it('handles assets path when defined', function () {
        simulateRequest('GET', '/style.css');
        
        // Define assets path usando reflection para acessar propriedade protegida
        $reflection = new ReflectionClass($this->dispatch);
        $assetsPathProperty = $reflection->getProperty('assetsPath');
        $assetsPathProperty->setAccessible(true);
        $assetsPathProperty->setValue($this->dispatch, '/assets');
        
        // O método assets() será chamado, mas nossa implementação é vazia
        $result = $this->dispatch->dispatch();
        
        // Como não temos rotas definidas, deve retornar false com NOT_IMPLEMENTED
        expect($result)->toBeFalse();
    });
});

describe('Fluent Interface', function () {
    it('supports method chaining', function () {
        $result = $this->dispatch->namespace('App\\Controllers')
                                ->group('admin');
        
        expect($result)->toBeInstanceOf(Dispatch::class);
        expect($result)->toBe($this->dispatch);
    });

    it('combines namespace and group correctly', function () {
        $this->dispatch->namespace('App\\Controllers')
                      ->group('admin', 'AuthMiddleware');
        
        $current = $this->dispatch->current();
        expect($current->namespace)->toBe('App\\Controllers');
        expect($current->group)->toBe('admin');
    });
});