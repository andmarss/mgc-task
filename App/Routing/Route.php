<?php

namespace App\Routing;

use App\Traits\SingletonTrait;

class Route
{
    use SingletonTrait;

    public static function load(string $file): Route
    {
        /**
         * @var Route $route
         */
        $route = static::getInstance();

        if (file_exists($file)) {
            require_once $file;
        }

        return $route;
    }
    /**
     * @param string $uri
     * @param $controller
     * @return Router
     * @throws \Exception
     */
    public static function get(string $uri, $controller): Router
    {
        return Router::register('GET', $uri, $controller);
    }

    /**
     * @param string $uri
     * @param $controller
     * @return Router
     * @throws \Exception
     */
    public static function post(string $uri, $controller): Router
    {
        return Router::register('POST', $uri, $controller);
    }

    /**
     * @param string $route
     * @param $controller
     * @return Router
     * @throws \Exception
     */
    public static function any(string $route, $controller)
    {
        return Router::register('*', $route, $controller);
    }

    /**
     * @param array $attributes
     * @param \Closure $callback
     */
    public static function group(array $attributes, \Closure $callback): void
    {
        Router::group($attributes, $callback);
    }

    /**
     * @param string $uri
     * @param string $method
     * @throws \Exception
     */
    public function direct(string $uri, string $method)
    {
        Router::direct($uri, $method);
    }
}