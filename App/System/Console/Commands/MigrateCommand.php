<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Database\DB;
use App\System\Filesystem\Workers\Directory;
use App\System\Filesystem\Workers\File;
use App\Traits\MigrationTraits\UnderscoreAndCamelCaseTrait;

class MigrateCommand extends Command
{
    use UnderscoreAndCamelCaseTrait;

    protected $required = [];

    public const NAME = 'migrate';

    public function execute(): void
    {
        /**
         * @var Directory $migrationsDirectory
         */
        $migrationsDirectory = Directory::open(root('/database/migrations'));

        if (!DB::table('migrations')->exists()) {
            (new CreateMigrationsTable([]))->execute();
        }

        $migrationsDirectory->files()->each(function (File $migrationFile){
            $fileNameWithoutExtension = $migrationFile->name(true);

            $migration = DB::table('migrations')->where(['migration' => $fileNameWithoutExtension])->first();
            // если миграция есть, и она уже загружена - ничего не делаем
            if ($migration && $migration->batch) {
                return;
            } elseif ($migration && !$migration->batch) {
                /**
                 * Имя класса
                 * @var string $className
                 */
                $className = $this->underscoreToCamelCase(preg_replace('/\d{4}\_\d{2}\_\d{2}\_\d{6}\_/', '', $fileNameWithoutExtension));
                $className = "Migrations\\$className";

                $this->info(sprintf('Начало выполнения миграции %s', $fileNameWithoutExtension));

                // выполняем миграцию
                (new $className())->up();
                // обновляем её, указывая, что миграция уже загружена
                DB::table('migrations')->update(['batch' => 1])->where(['id' => $migration->id, 'migration' => $fileNameWithoutExtension])->execute();

                $this->info(sprintf('Миграция %s выполнена', $fileNameWithoutExtension));
            } else { // если по какой-то причине миграция еще не была загружена
                /**
                 * Имя класса
                 * @var string $className
                 */
                $className = $this->underscoreToCamelCase(preg_replace('/\d{4}\_\d{2}\_\d{2}\_\d{6}\_/', '', $fileNameWithoutExtension));
                $className = "Migrations\\$className";

                $this->info(sprintf('Начало выполнения миграции %s', $fileNameWithoutExtension));

                // выполняем миграцию
                (new $className())->up();

                DB::table('migrations')->create(['migration' => $fileNameWithoutExtension, 'batch' => 1])->execute();

                $this->info(sprintf('Миграция %s выполнена', $fileNameWithoutExtension));
            }
        });
    }
}