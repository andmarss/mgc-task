<?php

namespace App\System;

use Traversable;

class Attributes implements \IteratorAggregate, \Countable
{
    protected $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Получить все параметры
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Получить ключи параметров
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Заменяет все параметры на новые
     *
     * @param array $parameters
     */
    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Добавить новые параметры
     *
     * @param array $parameters
     */
    public function add(array $parameters = [])
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    /**
     * @param string $attribute
     * @param null $default
     * @return mixed|null
     */
    public function get(string $attribute, $default = null)
    {
        return array_key_exists($attribute, $this->parameters) ? $this->parameters[$attribute] : $default;
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        unset($this->parameters[$key]);
    }

    /**
     * @return \ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }
}