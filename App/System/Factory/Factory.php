<?php

namespace App\System\Factory;

use App\Models\Model;
use App\System\Collection;
use App\System\Console\Invoker;

class Factory
{
    protected static $generator;
    protected $num;
    protected $class;

    /**
     * Factory constructor.
     * @param string $class - класс, экземпляры которого нужно создать
     * @param int $num - количество экземпляров, которое нужно создать
     */
    public function __construct(string $class, int $num = 1)
    {
        if (is_null(static::$generator)) {
            static::$generator = new Generator();
        }

        if (!class_exists($class)) {
            Invoker::error(sprintf('Класс "%s" не найден', $class));
            die;
        }

        $this->class = $class;
        $this->num = $num;
    }

    /**
     * @param \Closure $callback
     * @return Collection
     */
    public function create(\Closure $callback): Collection
    {
        $instances = [];

        if (is_subclass_of($this->class, Model::class)) {
            for ($i = 0; $i < $this->num; $i++) {
                $instances[] = $this->class::create(call_user_func($callback, static::$generator));
            }
        } else {
            for ($i = 0; $i <= $this->num; $i++) {
                $instances[] = (new $this->class((call_user_func($callback, static::$generator))));
            }
        }

        return collect($instances);
    }
}