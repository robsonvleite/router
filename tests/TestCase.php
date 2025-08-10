<?php

namespace CoffeeCode\Router\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe base para todos os testes do CoffeeCode Router
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Limpa variáveis globais antes de cada teste
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpa variáveis superglobais
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_REQUEST = [];
        
        // Define valores padrão para $_SERVER
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    /**
     * Simula uma requisição HTTP
     */
    protected function simulateRequest(
        string $method = 'GET',
        string $uri = '/',
        array $data = [],
        array $headers = []
    ): void {
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

    /**
     * Cria um arquivo temporário para testes de assets
     */
    protected function createTempFile(string $content = 'test content', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'router_test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Remove arquivo temporário
     */
    protected function removeTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Captura output de uma função
     */
    protected function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

    /**
     * Simula headers enviados (para testes que verificam headers)
     */
    protected function getHeadersOutput(callable $callback): array
    {
        $headers = [];
        
        // Mock da função header() para capturar headers
        $originalHeaders = headers_list();
        
        ob_start();
        try {
            $callback();
        } catch (\Exception $e) {
            // Ignora exceções de exit/die
        }
        ob_end_clean();
        
        $newHeaders = headers_list();
        return array_diff($newHeaders, $originalHeaders);
    }
}