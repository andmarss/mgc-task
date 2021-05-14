<?php

namespace App\System\Console;

use App\Contracts\CommandInterface;

abstract class Command extends Console implements CommandInterface
{
    protected $arguments = [];

    protected $signature = [];

    protected $required = [];

    public const NAME = '';

    public function __construct(array $arguments)
    {
        parent::__construct();

        $this->parseArguments($arguments);
    }

    abstract public function execute(): void;

    /**
     * @param array $arguments
     */
    public function parseArguments(array $arguments)
    {
        foreach ($arguments as $argument) {
            if (strpos($argument, '=') !== false) {
                [$argumentKey, $argumentValue] = explode('=', $argument);

                if (in_array($argumentKey, $this->signature)) {
                    $this->arguments[$argumentKey] = $argumentValue;
                }
            } elseif (in_array($argument, $this->signature)) {
                $this->arguments[$argument] = $argument;
            }
        }

        foreach ($this->required as $required) {
            if(!$this->argument($required)) {
                $this->error(sprintf('Поле "%s" обязательно для объявления! Команда "%s" не вызвана', $required, current($arguments)));
                die;
            }
        }
    }

    /**
     * @return array
     */
    protected function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    protected function argument(string $key)
    {
        if (isset($this->arguments[$key])) {
            return $this->arguments[$key];
        }

        return null;
    }
}