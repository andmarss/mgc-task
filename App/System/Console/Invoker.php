<?php

namespace App\System\Console;

use App\System\Console\Commands\CreateCommandCommand;
use App\System\Console\Commands\CreateSeederCommand;
use App\System\Console\Commands\DBSeedCommand;
use App\System\Console\Commands\MigrateCommand;
use App\System\Console\Commands\MigrateFreshCommand;
use App\System\Console\Commands\MigrationCreateCommand;
use App\System\Console\Commands\MigrationRollbackCommand;

class Invoker extends Console
{
    protected static $commands = [
        MigrationCreateCommand::NAME        => MigrationCreateCommand::class,
        MigrateCommand::NAME                => MigrateCommand::class,
        MigrationRollbackCommand::NAME      => MigrationRollbackCommand::class,
        MigrateFreshCommand::NAME           => MigrateFreshCommand::class,
        CreateSeederCommand::NAME           => CreateSeederCommand::class,
        DBSeedCommand::NAME                 => DBSeedCommand::class,
        CreateCommandCommand::NAME          => CreateCommandCommand::class
    ];

    protected $arguments = [];

    public function __construct(array $arguments)
    {
        parent::__construct();

        $this->arguments = array_slice($arguments, 1); // исключаем имя файла command.php
    }

    /**
     * Выполнить команду, если она зарегестрирована (добавлена)
     * @param Command|string|null $command
     */
    public function execute($command = null)
    {
        if (!is_null($command)) {
            static::run($command);
            die;
        }

        if (count($this->arguments) > 0) {
            if (isset(static::$commands[current($this->arguments)])) {
                try {
                    /**
                     * @var Command $command
                     */
                    $command = new static::$commands[current($this->arguments)]($this->arguments);

                    $command->execute();
                } catch (\Exception $exception) {
                    $this->error(sprintf('Произошла ошибка при выполнении команды %s: %s, %s', current($this->arguments), $exception->getMessage(), $exception->getTraceAsString()));
                    die;
                }
            } else {
                $this->error(sprintf('Команда %s не объявлена', current($this->arguments)));
                die;
            }
        } else {
            die;
        }
    }

    /**
     * Статический метод для вызова команды
     *
     * @param null $command
     * @param array $arguments
     */
    public static function run($command = null, array $arguments = [])
    {
        if (!is_null($command) && $command instanceof Command) {
            $command->execute();
        } elseif (!is_null($command) && is_string($command) && isset(static::$commands[$command])) {
            (new static::$commands[$command]($arguments))->execute();
        }
    }

    /**
     * @param string $command
     * @return bool
     */
    public static function has(string $command): bool
    {
        return !is_null($command) && is_string($command) && isset(static::$commands[$command]);
    }
}