<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;

class StubClass extends Command
{
    /**
     * Наименование команды
     * @var string NAME
     */
    public const NAME = 'stub:command';
    /**
     * Возможные аргументы
     * @var array $signature
     */
    protected $signature = [];
    /**
     * Обязательные аргументы
     * @var array $required
     */
    protected $required = [];

    public function execute(): void
    {
        // TODO: Implement execute() method.
    }
}