<?php


namespace App\Traits;


trait SingletonTrait
{
    protected static $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * предотвращяем вызов конструктора
     */
    private function __construct()
    {
    }

    /**
     * предотвратить возможность клонирования экземпляра
     */
    private function __clone()
    {
    }

    /**
     * закрываем возможность десериализации
     */
    private function __wakeup()
    {
    }
}