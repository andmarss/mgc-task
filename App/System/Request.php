<?php

namespace App\System;

use App\System\Attributes;
use App\Traits\SingletonTrait;

class Request
{
    use SingletonTrait;

    const GET = 'GET';
    const POST = 'POST';
    /**
     * @var array $data
     */
    protected $data = [];
    /**
     * @var null|Attributes $attributes
     */
    public $attributes = null;

    /**
     * @return string
     */
    public static function uri(): string
    {
        return sprintf('/%s/', trim(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'
        ));
    }

    /**
     * @return string
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param $key
     * @return mixed
     */

    public function __get($key)
    {
        if(isset($this->data[$key])){
            return $this->data[$key];
        }
    }

    /**
     * @param $key
     * @param $value
     */

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param $name
     * @return bool
     */

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @return Session
     */
    public function session(): Session
    {
        return session();
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        if (static::method() === static::GET) {
            if (isset($_GET[$key])) return $_GET[$key];
            if (isset($this->{$key})) return $this->{$key};
        } elseif (static::method() === static::POST) {
            if (isset($_POST[$key])) return $_POST[$key];
            if (isset($this->{$key})) return $this->{$key};
        }
        return null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }

    protected function clean(): void
    {
        $this->data = [];
    }

    /**
     * Получить все переданные поля запроса
     * @return array
     */
    public function all(): array
    {
        $result = [];

        if (static::method() === static::GET) {
            $result = array_merge($_GET, $this->data);
        } elseif (static::method() === static::POST) {
            $result = array_merge($_POST, $this->data);
        }

        return $result;
    }

    /**
     * @return Request
     */
    public static function current(): Request
    {
        /**
         * @var Request $request
         */
        $request = static::getInstance();
        $request->clean();

        if (is_null($request->attributes)) {
            $request->attributes = new Attributes();
        }

        if (static::method() === static::GET) {
            foreach ($_GET as $key => $value) {
                $request->{$key} = $value;
            }
        } elseif (static::method() === static::POST) {
            foreach ($_POST as $key => $value) {
                $request->{$key} = $value;
            }
        }

        return $request;
    }
}