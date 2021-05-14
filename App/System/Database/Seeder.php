<?php

namespace App\System\Database;

use App\System\Console\Console;

abstract class Seeder
{
    protected $console;

    public function __construct()
    {
        $this->console = new Console();
    }

    abstract public function run(): void;

    protected function call(string $class)
    {
        if ($class && class_exists($class) && method_exists($class, 'run')) {
            $this->console->info(sprintf('Начинаем сидинг класса %s', str_replace('Seeds\\', '', $class)));

            $start = microtime(true);

            (new $class())->run();

            $this->console->info(sprintf('Сидинг класса %s успешно выполнен. Время: %f секунд', str_replace('Seeds\\', '', $class), microtime(true) - $start));
        } elseif (!class_exists($class)) {
            $this->console->error('Класс %s не объявлен');
        }
    }
}