<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Console\Invoker;

class MigrateFreshCommand extends Command
{
    protected $signature = ['seed'];

    public const NAME = 'migrate:fresh';

    public function execute(): void
    {
        Invoker::run('migrate:rollback');
        Invoker::run('migrate');

        if ($this->argument('seed')) {
            Invoker::run('db:seed');
        }
    }
}