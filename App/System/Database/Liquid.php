<?php

namespace App\System\Database;

class Liquid
{
    protected $attributes = [];

    /**
     * ColumnBuilder constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }

    /**
     * $table->string('name')->nullable() => string => name, nullable => true
     *
     * @param string $method
     * @param array $arguments
     * @return $this
     */
    public function __call(string $method, array $arguments)
    {
        $this->attributes[$method] = count($arguments) > 0 ? current($arguments) : true;

        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * @param $key
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}