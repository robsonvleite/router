<?php

use CoffeeCode\Router\Router;

/**
 * Controller de exemplo para testes
 */
class TestController
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function index(array $data): void
    {
        echo "Index page with data: " . json_encode($data);
    }

    public function show(array $data): void
    {
        echo "Show item {$data['id']}";
    }

    public function store(array $data): void
    {
        echo "Store with data: " . json_encode($data);
    }

    public function update(array $data): void
    {
        echo "Update item {$data['id']} with data: " . json_encode($data);
    }

    public function destroy(array $data): void
    {
        echo "Delete item {$data['id']}";
    }
}

/**
 * Middleware de exemplo para testes
 */
class TestMiddleware
{
    public function handle($router): bool
    {
        echo "Middleware executed ";
        return true;
    }
}

/**
 * Middleware que bloqueia acesso
 */
class BlockingMiddleware
{
    public function handle($router): bool
    {
        echo "Access denied";
        return false;
    }
}

beforeEach(function () {
    $this->projectUrl = 'http://localhost';
    $this->router = new Router($this->projectUrl);
});

describe('Basic Routing', function () {
    it('handles basic GET routing', function () {
        simulateRequest('GET', '/');
        
        $this->router->get('/', function() {
            echo "Home page";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Home page");
    });

    it('handles routing with parameters', function () {
        simulateRequest('GET', '/user/123');
        
        $this->router->get('/user/{id}', function($data) {
            echo "User ID: " . $data['id'];
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("User ID: 123");
    });

    it('handles routing with multiple parameters', function () {
        simulateRequest('GET', '/user/123/post/456');
        
        $this->router->get('/user/{userId}/post/{postId}', function($data) {
            echo "User: {$data['userId']}, Post: {$data['postId']}";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("User: 123, Post: 456");
    });
});

describe('POST Routing', function () {
    it('handles POST routing with data', function () {
        $postData = ['name' => 'John', 'email' => 'john@example.com'];
        simulateRequest('POST', '/users', $postData);
        
        $this->router->post('/users', function($data) {
            echo "Created user: " . $data['name'];
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Created user: John");
    });
});

describe('Form Spoofing', function () {
    it('handles PUT method spoofing', function () {
        $postData = ['_method' => 'PUT', 'name' => 'Updated Name'];
        simulateRequest('POST', '/user/123', $postData);
        
        $this->router->put('/user/{id}', function($data) {
            echo "Updated user {$data['id']}: {$data['name']}";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Updated user 123: Updated Name");
    });

    it('handles DELETE method spoofing', function () {
        $postData = ['_method' => 'DELETE'];
        simulateRequest('POST', '/user/123', $postData);
        
        $this->router->delete('/user/{id}', function($data) {
            echo "Deleted user {$data['id']}";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Deleted user 123");
    });
});

describe('Controller Routing', function () {
    it('routes to controller', function () {
        simulateRequest('GET', '/users');
        
        $this->router->namespace('CoffeeCode\\Router\\Tests\\Integration');
        $this->router->get('/users', 'TestController:index');

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toContain("Index page");
    });

    it('routes to controller with parameters', function () {
        simulateRequest('GET', '/user/123');
        
        $this->router->namespace('CoffeeCode\\Router\\Tests\\Integration');
        $this->router->get('/user/{id}', 'TestController:show');

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Show item 123");
    });
});

describe('Route Groups', function () {
    it('handles route groups', function () {
        simulateRequest('GET', '/admin/dashboard');
        
        $this->router->group('admin');
        $this->router->get('/dashboard', function() {
            echo "Admin dashboard";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Admin dashboard");
    });

    it('handles nested route groups', function () {
        simulateRequest('GET', '/api/v1/users');
        
        $this->router->group('api');
        $this->router->group('api/v1');
        $this->router->get('/users', function() {
            echo "API v1 users";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("API v1 users");
    });
});

describe('Middleware', function () {
    it('executes middleware', function () {
        simulateRequest('GET', '/protected');
        
        $this->router->get('/protected', function() {
            echo "Protected content";
        }, null, 'CoffeeCode\\Router\\Tests\\Integration\\TestMiddleware');

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Middleware executed Protected content");
    });

    it('blocks access with middleware', function () {
        simulateRequest('GET', '/blocked');
        
        $this->router->get('/blocked', function() {
            echo "This should not be executed";
        }, null, 'CoffeeCode\\Router\\Tests\\Integration\\BlockingMiddleware');

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Access denied");
    });

    it('executes multiple middlewares', function () {
        simulateRequest('GET', '/multi-middleware');
        
        $middlewares = [
            'CoffeeCode\\Router\\Tests\\Integration\\TestMiddleware',
            'CoffeeCode\\Router\\Tests\\Integration\\TestMiddleware'
        ];
        
        $this->router->get('/multi-middleware', function() {
            echo "Content";
        }, null, $middlewares);

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Middleware executed Middleware executed Content");
    });

    it('executes group middleware', function () {
        simulateRequest('GET', '/admin/users');
        
        $this->router->group('admin', 'CoffeeCode\\Router\\Tests\\Integration\\TestMiddleware');
        $this->router->get('/users', function() {
            echo "Admin users";
        });

        $output = captureOutput(fn() => $this->router->dispatch());

        expect($output)->toBe("Middleware executed Admin users");
    });
});

describe('Named Routes', function () {
    it('generates URLs by route name', function () {
        $this->router->get('/user/{id}', fn() => null, 'user.show');
        
        $url = $this->router->route('user.show', ['id' => 123]);
        
        expect($url)->toBe('http://localhost/user/123');
    });

    it('generates URLs with extra parameters', function () {
        $this->router->get('/user/{id}', fn() => null, 'user.show');
        
        $url = $this->router->route('user.show', ['id' => 123, 'tab' => 'profile']);
        
        expect($url)->toContain('/user/123');
        expect($url)->toContain('tab=profile');
    });
});

describe('Redirects', function () {
    it('redirects by route name', function () {
        $this->router->get('/dashboard', fn() => null, 'dashboard');
        
        expect(fn() => $this->router->redirect('dashboard'))
            ->not->toThrow(Exception::class);
    });

    it('redirects with external URL', function () {
        expect(fn() => $this->router->redirect('https://example.com'))
            ->not->toThrow(Exception::class);
    });
});

describe('Error Handling', function () {
    it('returns 404 for non-existent route', function () {
        simulateRequest('GET', '/nonexistent');
        
        $this->router->get('/existing', function() {
            echo "This exists";
        });

        $result = $this->router->dispatch();
        
        expect($result)->toBeFalse();
        expect($this->router->error())->toBe(404);
    });

    it('returns 405 for unsupported method with Allow header', function () {
        simulateRequest('POST', '/get-only');
        
        $this->router->get('/get-only', function() {
            echo "GET only";
        });

        $result = $this->router->dispatch();
        
        expect($result)->toBeFalse();
        expect($this->router->error())->toBe(405);
    });
});

describe('CRUD Operations', function () {
    it('handles complete CRUD operations', function () {
        $this->router->namespace('CoffeeCode\\Router\\Tests\\Integration');
        
        // CREATE
        simulateRequest('POST', '/users', ['name' => 'John']);
        $this->router->post('/users', 'TestController:store');
        
        $output = captureOutput(fn() => $this->router->dispatch());
        expect($output)->toContain('Store with data');
        
        // READ
        simulateRequest('GET', '/user/123');
        $this->router->get('/user/{id}', 'TestController:show');
        
        $output = captureOutput(fn() => $this->router->dispatch());
        expect($output)->toBe('Show item 123');
        
        // UPDATE
        simulateRequest('POST', '/user/123', ['_method' => 'PUT', 'name' => 'Updated']);
        $this->router->put('/user/{id}', 'TestController:update');
        
        $output = captureOutput(fn() => $this->router->dispatch());
        expect($output)->toContain('Update item 123');
        
        // DELETE
        simulateRequest('POST', '/user/123', ['_method' => 'DELETE']);
        $this->router->delete('/user/{id}', 'TestController:destroy');
        
        $output = captureOutput(fn() => $this->router->dispatch());
        expect($output)->toBe('Delete item 123');
    });
});

describe('Current Route Information', function () {
    it('provides current route information', function () {
        simulateRequest('GET', '/admin/users');
        
        $this->router->namespace('App\\Controllers');
        $this->router->group('admin');
        $this->router->get('/users', fn() => null);
        
        $current = $this->router->current();
        
        expect($current->namespace)->toBe('App\\Controllers');
        expect($current->group)->toBe('admin');
        expect($current->path)->toBe('/admin/users');
    });

    it('provides request data', function () {
        $postData = ['name' => 'Test', 'email' => 'test@example.com'];
        simulateRequest('POST', '/submit', $postData);
        
        $this->router->post('/submit', fn($data) => null);
        
        $this->router->dispatch();
        $data = $this->router->data();
        
        expect($data['name'])->toBe('Test');
        expect($data['email'])->toBe('test@example.com');
    });
});