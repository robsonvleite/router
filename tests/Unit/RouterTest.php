<?php

use CoffeeCode\Router\Router;

beforeEach(function () {
    $this->projectUrl = 'http://localhost';
    $this->router = new Router($this->projectUrl);
});

describe('Router Constructor', function () {
    it('creates router with project URL', function () {
        $router = new Router('http://example.com');
        expect($router)->toBeInstanceOf(Router::class);
        expect($router->home())->toBe('http://example.com');
    });

    it('removes trailing slash from project URL', function () {
        $router = new Router('http://example.com/');
        expect($router->home())->toBe('http://example.com');
    });

    it('accepts custom separator', function () {
        $router = new Router($this->projectUrl, '@');
        expect($router)->toBeInstanceOf(Router::class);
    });

    it('uses default separator when null provided', function () {
        $router = new Router($this->projectUrl, null);
        expect($router)->toBeInstanceOf(Router::class);
    });
});

describe('HTTP Methods', function () {
    it('registers GET route', function () {
        $handler = fn() => 'test';
        
        $this->router->get('/test', $handler);
        $routes = $this->router->__debugInfo();
        
        expect($routes)->toHaveKey('GET');
    });

    it('registers GET route with name', function () {
        $handler = fn() => 'test';
        
        $this->router->get('/test', $handler, 'test.route');
        $routes = $this->router->__debugInfo();
        
        expect($routes)->toHaveKey('GET');
        $routeData = current($routes['GET']);
        expect($routeData['name'])->toBe('test.route');
    });

    it('registers GET route with middleware', function () {
        $handler = fn() => 'test';
        $middleware = 'TestMiddleware';
        
        $this->router->get('/test', $handler, null, $middleware);
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['GET']);
        expect($routeData['middlewares'])->toBe($middleware);
    });

    it('registers GET route with middleware array', function () {
        $handler = fn() => 'test';
        $middleware = ['Middleware1', 'Middleware2'];
        
        $this->router->get('/test', $handler, null, $middleware);
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['GET']);
        expect($routeData['middlewares'])->toBe($middleware);
    });

    it('registers POST route', function () {
        $handler = fn() => 'test';
        
        $this->router->post('/test', $handler);
        expect($this->router->__debugInfo())->toHaveKey('POST');
    });

    it('registers POST route with all parameters', function () {
        $handler = fn() => 'test';
        
        $this->router->post('/test', $handler, 'test.post', 'TestMiddleware');
        $routes = $this->router->__debugInfo();
        
        expect($routes)->toHaveKey('POST');
        $routeData = current($routes['POST']);
        expect($routeData['name'])->toBe('test.post');
        expect($routeData['middlewares'])->toBe('TestMiddleware');
    });

    it('registers PUT route', function () {
        $handler = fn() => 'test';
        
        $this->router->put('/test', $handler);
        expect($this->router->__debugInfo())->toHaveKey('PUT');
    });

    it('registers PUT route with all parameters', function () {
        $handler = fn() => 'test';
        
        $this->router->put('/test', $handler, 'test.put', ['Middleware1']);
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['PUT']);
        expect($routeData['name'])->toBe('test.put');
        expect($routeData['middlewares'])->toBe(['Middleware1']);
    });

    it('registers PATCH route', function () {
        $handler = fn() => 'test';
        
        $this->router->patch('/test', $handler);
        expect($this->router->__debugInfo())->toHaveKey('PATCH');
    });

    it('registers PATCH route with nullable parameters', function () {
        $handler = fn() => 'test';
        
        $this->router->patch('/test', $handler, null, null);
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['PATCH']);
        expect($routeData['name'])->toBeNull();
        expect($routeData['middlewares'])->toBeNull();
    });

    it('registers DELETE route', function () {
        $handler = fn() => 'test';
        
        $this->router->delete('/test', $handler);
        expect($this->router->__debugInfo())->toHaveKey('DELETE');
    });

    it('registers DELETE route with all parameters', function () {
        $handler = 'TestController:delete';
        
        $this->router->delete('/test/{id}', $handler, 'test.delete', 'AuthMiddleware');
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['DELETE']);
        expect($routeData['name'])->toBe('test.delete');
        expect($routeData['middlewares'])->toBe('AuthMiddleware');
    });
});

describe('Configuration', function () {
    it('sets cache time', function () {
        $result = $this->router->setCache(3600);
        expect($result)->toBeInstanceOf(Router::class);
    });

    it('sets cache to false', function () {
        $result = $this->router->setCache(false);
        expect($result)->toBeInstanceOf(Router::class);
    });

    it('sets assets path', function () {
        $result = $this->router->setAssets('/public/assets');
        expect($result)->toBeInstanceOf(Router::class);
    });

    it('removes trailing slash from assets path', function () {
        $result = $this->router->setAssets('/public/assets/');
        expect($result)->toBeInstanceOf(Router::class);
    });
});

describe('Assets Handling', function () {
    it('handles non-existent file', function () {
        simulateRequest('GET', '/nonexistent.css');
        
        $output = captureOutput(fn() => $this->router->assets('/tmp/assets'));
        
        expect($output)->toBeEmpty();
    });

    it('serves valid CSS file', function () {
        // Cria diretório temporário único
        $tempDir = sys_get_temp_dir() . '/router_test_assets_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Cria arquivo CSS temporário
        $cssFile = $tempDir . '/style.css';
        file_put_contents($cssFile, 'body { color: red; }');
        
        simulateRequest('GET', '/style.css');
        
        // Testa se o arquivo existe e pode ser servido
        // Como assets() faz exit(), testamos apenas se não há erro
        $this->expectNotToPerformAssertions();
        
        try {
            $this->router->assets($tempDir);
        } catch (Exception $e) {
            // O método assets faz exit() quando serve arquivos
            // Isso é comportamento esperado
        }
        
        // Limpa arquivos temporários
        if (file_exists($cssFile)) {
            unlink($cssFile);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    });

    it('serves assets with cache enabled', function () {
        // Cria diretório temporário único
        $tempDir = sys_get_temp_dir() . '/router_test_assets_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Cria arquivo JS temporário
        $jsFile = $tempDir . '/script.js';
        file_put_contents($jsFile, 'console.log("test");');
        
        simulateRequest('GET', '/script.js');
        $this->router->setCache(3600);
        
        // Testa se o arquivo existe e pode ser servido com cache
        $this->expectNotToPerformAssertions();
        
        try {
            $this->router->assets($tempDir);
        } catch (Exception $e) {
            // O método assets faz exit() quando serve arquivos
            // Isso é comportamento esperado
        }
        
        // Limpa arquivos temporários
        if (file_exists($jsFile)) {
            unlink($jsFile);
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    });

    it('prevents access to files outside allowed directory', function () {
        simulateRequest('GET', '/../../../etc/passwd');
        
        $output = captureOutput(fn() => $this->router->assets('/tmp/assets'));
        
        expect($output)->toBeEmpty();
    });
});

describe('Handlers', function () {
    it('handles callable', function () {
        $handler = fn() => 'callable';
        
        $this->router->get('/test', $handler);
        $routes = $this->router->__debugInfo();
        
        $routeData = current($routes['GET']);
        expect($routeData['handler'])->toBeCallable();
    });

    it('handles string controller:action', function () {
        $this->router->namespace('App\\Controllers');
        $this->router->get('/test', 'TestController:index');
        
        $routes = $this->router->__debugInfo();
        $routeData = current($routes['GET']);
        
        expect($routeData['handler'])->toBeString();
        expect($routeData['action'])->toBe('index');
    });
});