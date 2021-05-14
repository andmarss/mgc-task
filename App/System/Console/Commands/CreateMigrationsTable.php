<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Database\DB;
use App\System\Database\QueryBuilder;
use App\System\Database\SchemaBuilder;

class CreateMigrationsTable extends Command
{
    protected $signature = [];

    protected $required = [];

    public const NAME = 'create:migration';

    public function execute(): void
    {
        $builder = new SchemaBuilder('migrations');

        $builder->create();

        $builder->increments('id');
        $builder->string('migration');
        $builder->boolean('batch')->default(0);

        $db = DB::getInstance()->setQuery(new QueryBuilder());

        foreach ($builder->compile() as $statement) {
            $db->query($statement);
        }
    }
}