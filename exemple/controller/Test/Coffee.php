<?php

namespace Test;

use CoffeeCode\Router\Router;

/**
 * Class Coffee MVC :: CONTROLLER
 * @package Test
 */
class Coffee
{
    /**
     * Coffee constructor.
     */
    public function __construct(Router $router)
    {
        $url = BASE;
        $rand = rand(44, 244);

        echo "<h1>Router @CoffeeCode</h1>";
        echo "<p>Normal routes:</p>";
        echo "<nav>
            <a href='{$url}'>Home</a> | 
            <a href='{$url}/edit/{$rand}'>Edit</a> | 
            <a href='{$url}/logado/?user=true'>Logado</a> | 
            <a href='{$router->route("coffe.denied")}'>Negado</a> | 
            <a href='{$url}/error/'>Error</a>
        </nav>";

        echo "<p>Group routes:</p>";
        echo "<nav>
            <a href='{$url}/admin'>Admin</a> | 
            <a href='{$url}/admin/user/{$rand}'>Edit User</a> | 
            <a href='{$url}/admin/user/{$rand}/profile'>Perfil</a> | 
            <a href='{$url}/admin/user/{$rand}/profile/imagem-{$rand}.jpg'>Photo</a> 
        </nav>";

        echo "<p>Named and call routes:</p>";
        echo "<nav>
            <a href='{$url}/name'>Named</a> | 
            <a href='{$url}/call'>Call Current</a> | 
            <a href='{$url}/call/coffecode'>Call Current + App</a>
        </nav>";
    }

    /**
     * @param array $data
     */
    public function home(array $data): void
    {
        echo "<h3>", __METHOD__, "::", $_SERVER["REQUEST_METHOD"], "</h3><hr>";
        echo "<pre>", print_r($data, true), "</pre>";
    }

    /**
     * @param array $data
     */
    public function edit(array $data): void
    {
        echo "<h3>", __METHOD__, "::", $_SERVER["REQUEST_METHOD"], "</h3><hr>";

        echo "<form name='coffeecode' method='post' enctype='multipart/form-data'>
            <input name=\"first_name\" value=\"Robson\">
            <input name=\"last_name\" value=\"V. Leite\">
            <input name=\"email\" value=\"cursos@upinside.com.br\">
            <button>@CoffeeCode</button>
        </form>";

        echo "<pre>", print_r($data, true), "</pre>";
    }

    /**
     * @param array $data
     */
    public function notfound(array $data): void
    {
        echo "<h3>Whoops!</h3>", "<pre>", print_r($data, true), "</pre>";
    }

    /**
     * @param array $data
     */
    public function admin(array $data): void
    {
        echo "<h3>Admin Group:</h3>", "<pre>", print_r($data, true), "</pre>";
    }

    /**
     * @param array $data
     */
    public function logged(array $data)
    {
        echo "<h3>Logged</h3>", "<p>Essa tela simula execução de múltiplos middlewares</p>", "<pre>", print_r(
            $data,
            true
        ), "</pre>";
    }

    /**
     * @param array $data
     */
    public function denied(array $data)
    {
        echo "<h3>Acessou com sucesso: (Access Denied)</h3>", "<pre>", print_r($data, true), "</pre>";
    }
}