<?php

namespace App\Routing;

use App\Http\Controller;
use App\System\Request;
use App\Traits\SingletonTrait;

class Router
{
    use SingletonTrait;
    /**
     * @var array $routes
     */
    protected static $routes = [
        'GET'    => [],
        'POST'   => [],
        '*'      => []
    ];
    /**
     * @var array $patterns
     */
    protected static $patterns = [];
    /**
     * @var null|array $group
     */
    protected static $group = null;
    /**
     * @var
     */
    protected $uri;
    /**
     * @var array $where
     */
    protected static $where = [];
    /**
     * @var array $names
     */
    protected static $names = [];
    /**
     * @var int $recursion
     */
    protected static $recursion = 0;
    /**
     * @var bool $first_recursion
     */
    protected static $first_recursion = false;

    /**
     * @param string $method
     * @param string $uri
     * @param $controller
     * @return Router
     * @throws \Exception
     */
    protected function register(string $method, string $uri, $controller): Router
    {
        if (!isset(static::$routes[$method])) throw new \Exception(sprintf('Тип %s не поддерживается', $method));

        if ($uri === '') {
            $uri = '/';
        }

        if (!is_null(static::$group) && is_array(static::$group) && count(static::$group) > 0) {
            /**
             * Перебираем массив с параметрами групп, получаем массив строк формата /foo, /bar
             * @var array $groups
             */
            $groups = array_map(function (array $group) {
                return sprintf('/%s', trim($group['prefix'], '/'));
            }, static::$group);
            /**
             * Обрезаем массив по уровню рекурсии
             */
            $groups = array_slice($groups, 0, static::$recursion);
            /**
             * Превращаем массив в строки, формата /foo/bar
             * @var string $prefix
             */
            $prefix = implode('', $groups);
            /**
             * Преобразуем строку префикса к формату /foo/bar/
             */
            $prefix = sprintf('%s/', $prefix);

            $prefix = trim($prefix, '/');

            $uri = $uri === '/' ? sprintf('/%s/', $prefix) : sprintf('/%s/%s/', $prefix, trim($uri, '/'));

            static::$routes[$method][$uri] = $controller;
        } else {
            $uri = $uri === '/' ? $uri : sprintf('/%s/', trim($uri, '/'));

            static::$routes[$method][$uri] = $controller;
        }

        if ($this->checkUriIsPattern($uri)) {
            static::$patterns[$method][] = $uri;
        }

        $this->uri = $uri;

        return $this;
    }

    /**
     * @param string $uri
     * @param string $method
     * @return Router
     * @throws \Exception
     */
    protected function direct(string $uri, string $method): Router
    {
        $uri = trim($uri, '/');

        if ($uri === '') {
            $uri = '/';
        } elseif ($uri === '//') {
            $uri = '/';
        }
        /**
         * @var string $uri
         */
        $uri = $uri === '/' ? $uri : sprintf('/%s/', trim($uri, '/'));
        /**
         * @var array $parameters
         */
        $parameters = [];

        if ($pattern = $this->checkUriHasPattern($uri, $method)) {
            $parameters = $this->getParams($pattern, $uri);
        }

        // если в качестве обработчика была установлена обычная callback функция
        if(is_callable(static::$routes[$method][$uri])) {
            return $this->callClosure(static::$routes[$method][$uri], []);
        } elseif ($pattern && $parameters && is_callable(static::$routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена обычная callback функция
            return $this->callClosure(static::$routes[$method][$pattern], $parameters);
        } elseif ($pattern && $parameters && is_string(static::$routes[$method][$pattern])) {
            // если ссылка была шаблоном, и в качестве обработчика была установлена строка с разделителем @
            [$controller, $action] = explode('@', static::$routes[$method][$pattern]);
            return $this->callAction(
                $controller, $action, $parameters
            );
        } elseif(!$pattern && !$parameters && is_string(static::$routes[$method][$uri])) {
            // если ссылка - не шаблон, и в качестве обработчика была установлена строка с разделителем @
            [$controller, $action] = explode('@', static::$routes[$method][$uri]);

            return $this->callAction(
                $controller, $action, []
            );
        } elseif (count(static::$routes['*']) > 0 && $this->uriHasAnyMatchInAnyRoutes($uri)) { // совпадает ли хотя бы часть маршрута с маршрутами из any
            $callable = current(array_values($this->getAnyRoute($uri)));

            if (is_callable($callable)) {
                return $this->callClosure($callable, $parameters);
            } elseif (is_string($callable) && strpos($callable, '@') !== false) {
                [$controller, $action] = explode('@', $callable);
                return $this->callAction(
                    $controller, $action, $parameters
                );
            }
        }

        throw new \Exception(sprintf('Для URI %s не указан маршрут.', $uri));
    }

    /**
     * @param array $attributes
     * @param \Closure $callback
     */
    public static function group(array $attributes, \Closure $callback): void
    {
        static::$recursion++;

        static::$group[] = $attributes;
        // вызываем функцию, что бы $routes заполнились зарегестрированными маршрутами
        call_user_func($callback);
        // если это группа-родитель (самая первая группа)
        // значит остальные (внутренние) группы уже загружены
        // можно сбрасывать рекурсию в 0
        // и обнулять группы
        if (static::$recursion === 1) {
            static::$recursion = 0;
            static::$group = null;
        } else { // иначе - отнимаем от уровня рекурсии -1, и обрезаем группы так
            // что бы каждая группа смогла получить соответствующие ей префиксы
            static::$recursion -= 1;
            static::$group = array_slice(static::$group, 0, static::$recursion);
        }
    }

    /**
     * @param string $name
     * @param array $data
     * @throws \Exception
     * @return string
     */
    protected function convertUri(string $name, array $data = []): string
    {
        if (!isset(static::$names[$name])) {
            throw new \Exception("Имя маршрута \"$name\" не объявлено");
        }
        /**
         * @var string $uri
         */
        $uri = static::$names[$name];
        /**
         * @var bool $isPattern
         */
        $isPattern = $this->checkUriIsPattern($uri);

        if ($isPattern) {
            preg_match_all('/\{([^\{|\}]+)\}/', $uri, $m);

            if ($m && isset($m[0]) && count($m[0]) > 0 && (count($m[0]) === count($data))) {

                $uri = preg_replace('/\{[^\{\}]+\}/', '%s', $uri);
                /**
                 * @var string $route
                 */
                $route = sprintf($uri, ...array_values($data));

                return $route;
            } else {
                throw new \Exception("Паттерн маршрута не совпадает с переданными параметрами");
            }
        } else {
            return $uri;
        }
    }

    /**
     * Применить к маршруту с динамическими параметрами
     * условие в виде регулярного выражения
     *
     * @param array $condition
     * @return Router
     */
    public function where(array $condition): Router
    {
        if(count($condition) > 0) {
            if(!isset($this->where[$this->uri])) static::$where[$this->uri] = [];

            foreach ($condition as $param => $pattern) {
                static::$where[$this->uri][$param] = $pattern;
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @return Router
     * @throws \Exception
     */
    public function name(string $name): Router
    {
        if(array_key_exists($name, static::$names)) {
            throw new \Exception("Имя маршрута \"$name\" уже объявлено. Выберите другое имя.");
        }

        static::$names[$name] = $this->uri;

        return $this;
    }

    /**
     * @param string $uri
     * @return bool
     *
     * Проверяет, есть ли у uri паттерн, по которому этот uri должен отработать
     */
    protected function checkUriIsPattern(string $uri): bool
    {
        return (bool) preg_match('/\{([^\{|\}]+)\}/', $uri);
    }

    /**
     * @param string $uri
     * @param string $method
     * @return string|null
     */
    protected function checkUriHasPattern(string $uri, string $method): ?string
    {
        if (isset(static::$patterns[$method]) && is_array(static::$patterns[$method])) {
            $patterns = static::$patterns[$method];

            foreach ($patterns as $pattern) {
                if($this->match($pattern, $uri)) return $pattern;

                continue;
            }
        }

        return null;
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return bool
     *
     * Проверяет, совпадает ли переданный паттерн с маршрутом
     */
    protected function match(string $pattern, string $uri)
    {
        $patternChunks = collect(explode('/', $pattern))->filter(function ($chunk){
            return  mb_strlen($chunk) > 0;
        })->values()->all();

        $uriChunks = collect(explode('/', $uri))->filter(function ($chunk){
            return  mb_strlen($chunk) > 0;
        })->values()->all();

        if (count($patternChunks) === count($uriChunks)) {
            $pattern = preg_replace('/\{[^\{\}]+\}/', '(.+)', $pattern); // убираем фигурные скобки, заменяем их круглыми
            $pattern = trim($pattern, '/'); // убираем по бокам слеши
            $pattern = preg_replace('/\/+/', '\/', $pattern); // а так же все лишние слеши

            preg_match_all('/' . $pattern . '/', $uri, $m); // применяем паттерн, получаем параметры, которые были передан в маршрут

            if($m && isset($m[0])) {
                return count(
                        array_filter(array_map(function (array $match){
                            return (bool) isset($match[0]) ? $match[0] : false;
                        }, array_slice($m, 1)), function (bool $match){
                            return $match;
                        })
                    ) > 0;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $controller
     * @param string $action
     * @param null ...$params
     * @return Router
     * @throws \Exception
     */
    protected function callAction(string $controller, string $action, array $params = []): Router
    {
        $controller = "App\\Http\\{$controller}";
        $controller = new $controller;

        if(!method_exists($controller, $action)) {
            throw new \Exception(
                "Экшн $action отсутствует в контроллере $controller"
            );
        }

        return $this->call([$controller, $action], $params);
    }

    /**
     * @param \Closure $callable
     * @param array $params
     * @return Router
     */
    protected function callClosure(\Closure $callable, array $params = []): Router
    {
        $callable->bindTo(new Controller());

        return $this->call($callable, $params);
    }

    /**
     * @param $callable
     * @param array $params
     * @return Router
     */
    protected function call($callable, array $params = []): Router
    {
        /**
         * @var Request $request
         */
        $request = Request::current();

        if($params) {
            foreach ($params as $key => $value) {
                $request->{$key} = $value;
            }
        }

        echo call_user_func($callable, $request, ...array_values($params));

        return $this;
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    protected function getParams(string $pattern, string $uri): array
    {
        /**
         * @var array $keys
         */
        $keys = $this->getParamsKeys($pattern);
        /**
         * @var array $values
         */
        $values = $this->getParamsValues($pattern, $uri);
        /**
         * @var array $params
         */
        $params = [];

        foreach ($keys as $index => $key){
            $params[$key] = $values[$index];
        }

        return $params;
    }

    /**
     * @param string $pattern
     * @return array
     */
    protected function getParamsKeys(string $pattern): array
    {
        /**
         * @var string $pattern
         */
        $pattern = preg_replace('/^\/+|\/+$/', '', $pattern);
        $pattern = $pattern === '' ? '/' : $pattern;

        if(preg_match_all('/\{([^}])+\}/', $pattern, $m) && isset($m[0])) {
            return array_map(function (string $match){
                return preg_replace('/\{|\}/', '', $match);
            }, $m[0]);
        }

        return [];
    }

    /**
     * @param string $pattern
     * @param string $uri
     * @return array
     */
    protected function getParamsValues(string $pattern, string $uri): array
    {
        /**
         * @var array $where
         */
        $where = isset(static::$where[$pattern]) ? static::$where[$pattern] : [];
        // если есть условия
        if($where && count($where) > 0) {
            foreach ($where as $param => $wherePattern) {
                if(preg_match("/\{[$param]+\}/", $pattern)) {
                    $pattern = preg_replace("/\{[$param]+\}/", "($wherePattern)", $pattern);
                } else {
                    continue;
                }
            }

            $pattern = preg_replace("/\/+/", '\\/', $pattern);

            if(preg_match_all("/\{[^\{\}]+\}/", $pattern)) {
                $pattern = preg_replace('/\{[^\{\}]+\}/', "([^/]+)", $pattern);
            }

            if(preg_match_all("/$pattern/", $uri, $m)) {
                return array_slice(array_map(function(array $match){
                    if(isset($match[0])) {
                        return $match[0];
                    } else {
                        return [];
                    }
                }, $m), 1);
            }
        } else {
            if(preg_match_all("/\{[^\{\}]+\}/", $pattern)) {
                $pattern = preg_replace('/\{[^\{\}]+\}/', "([^/]+)", $pattern);
            }

            $pattern = preg_replace("/\/+/", '\\/', $pattern);

            if(preg_match_all("/$pattern/", $uri, $m)) {

                return array_slice(array_map(function(array $match){
                    if(isset($match[0])) {
                        return $match[0];
                    } else {
                        return [];
                    }
                }, $m), 1);
            }
        }

        return [];
    }

    public static function getRoutes(): array
    {
        return static::$routes;
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function uriHasAnyMatchInAnyRoutes(string $uri): bool
    {
        foreach (static::$routes['*'] as $route => $callable) {
            if ((bool) preg_match(sprintf('/%s/', trim($route ,'/')), $uri, $m)) {
                return true;
            }

            continue;
        }

        return false;
    }

    /**
     * @param string $uri
     * @return array
     */
    public function getAnyRoute(string $uri)
    {
        foreach (static::$routes['*'] as $route => $callable) {
            if ((bool) preg_match(sprintf('/%s/', trim($route ,'/')), $uri, $m)) {
                return [$route => $callable];
            }

            continue;
        }

        return [];
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        /**
         * @var Singleton $instance
         */
        $instance = static::getInstance();

        if(method_exists($instance, $method)) {
            return $instance->{$method}(...$arguments);
        } else {
            throw new \Exception(sprintf('Метод %s не объявлен в классе %s', $method, __CLASS__));
        }
    }


}