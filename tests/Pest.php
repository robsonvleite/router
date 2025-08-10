<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(CoffeeCode\Router\Tests\TestCase::class)->in('Unit', 'Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code you need to type in your tests.
|
*/

function simulateRequest(string $method = 'GET', string $uri = '/', array $data = [], array $headers = []): void
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    
    // Define a rota no $_GET
    $route = parse_url($uri, PHP_URL_PATH);
    $_GET['route'] = $route === '/' ? '' : ltrim($route, '/');
    
    // Define dados baseado no método
    if ($method === 'POST') {
        $_POST = $data;
    } elseif (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
        // Para métodos PUT/PATCH/DELETE, simula dados no php://input
        $_SERVER['CONTENT_LENGTH'] = strlen(http_build_query($data));
    }
    
    // Define headers
    foreach ($headers as $key => $value) {
        $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
    }
}

function captureOutput(callable $callback): string
{
    ob_start();
    $callback();
    return ob_get_clean();
}

function createTempFile(string $content = 'test content', string $extension = 'txt'): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'router_test_') . '.' . $extension;
    file_put_contents($tempFile, $content);
    return $tempFile;
}

function removeTempFile(string $filePath): void
{
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}