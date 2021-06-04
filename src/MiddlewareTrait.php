<?php

namespace CoffeeCode\Router;

trait MiddlewareTrait
{
    /** @var array */
    protected $middlewares = [];

    /** @var array */
    protected $currentRoute;

    /** @var array */
    protected $queue;

    /** @var int = 0 */
    protected $currentQueueNumber = 0;

    /**
     * @var array $middlewares
     * @return self
     */
    public function setMiddlewares(Array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /** 
     * @return array 
     */
    protected function getCurrentMiddlewares()
    {
        if (!isset($this->currentRoute['middlewares'])) return;

        return $this->currentRoute['middlewares'];
    }

    /** 
     * @param array $route
     */
    protected function setCurrentRoute(Array $route): void
    {
        $this->currentRoute = $route;
    }

    /**
     * @return mixed|bool
     */
    protected function executeMiddleware()
    {
        $middlewares = $this->currentRoute['middleware'];

        if (is_string($middlewares)) {
            $middlewares = explode(',', $middlewares);
        }

        $this->resolveNestedMiddleware($middlewares);
        
        return $this->callMiddlewares();
    }

    /**
     * @param array $middlewares
     */
    protected function resolveNestedMiddleware(Array $middlewares): void
    {
        $this->queue = array_map(function($middleware) {
            $middleware = trim(rtrim($middleware));
    
            return $this->instanceMiddleware($middleware);
        }, $middlewares);
    }

    /**
     * @param string $alias
     * @return null|string
     */
    protected function getMiddlewareByAlias(String $alias)
    {
        if (!array_key_exists($alias, $this->middlewares)) {
            return;
        }

        if (class_exists($this->middlewares[$alias])) {
            return $this->middlewares[$alias];
        }

        return;
    }

    /**
     * @param string
     * @return null|object
     */
    protected function instanceMiddleware($middleware)
    {
        if (!preg_match("/\\\/", $middleware)) {
            if(!$middlewareClass = $this->getMiddlewareByAlias($middleware)) return;

            return new $middlewareClass();
        }

        if (class_exists($middleware)) {
            return new $middleware();
        }

        return;
    }

    /**
     * @return mixed|bool
     */
    protected function next()
    {
        $this->currentQueueNumber++;

        return $this->callMiddlewares();
    }

    /**
     * @return void
     */
    protected function reset()
    {
        $this->currentQueueNumber = 0;
    }

    /**
     * @return mixed|bool
     */
    protected function callMiddlewares()
    {
        if (!isset($this->queue[$this->currentQueueNumber])) {
            $this->reset();
            return true;
        }

        $currentMiddleware = $this->queue[$this->currentQueueNumber];

        if (is_null($currentMiddleware) || empty($currentMiddleware)) {
            return $this->next();
        }

        return $currentMiddleware->handle(
                $this->currentRoute['data'],
                fn() => $this->next()
            );
    }
}
