<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Database\DB;
use App\System\Filesystem\Workers\Directory;
use App\System\Filesystem\Workers\File;
use App\Traits\MigrationTraits\UnderscoreAndCamelCaseTrait;

class MigrationRollbackCommand extends Command
{
    use UnderscoreAndCamelCaseTrait;

    public const NAME = 'migrate:rollback';

    public function execute(): void
    {
        /**
         * Объект папки migrations
         * @var Directory $migrationsDirectory
         */
        $migrationsDirectory = Directory::open(root('/database/migrations'));

        if (!DB::table('migrations')->exists()) {
            (new CreateMigrationsTable([]))->execute();
        }

        $migrationsDirectory->files()->each(function (File $migrationFile) {
            $fileNameWithoutExtension = $migrationFile->name(true);

            $migration = DB::table('migrations')->where(['migration' => $fileNameWithoutExtension])->first();

            if ($migration && $migration->batch) {
                /**
                 * Имя класса
                 * @var string $className
                 */
                $className = $this->underscoreToCamelCase(preg_replace('/\d{4}\_\d{2}\_\d{2}\_\d{6}\_/', '', $fileNameWithoutExtension));
                $className = "Migrations\\$className";

                $this->info(sprintf('Начало отмены миграции %s', $fileNameWithoutExtension));

                // выполняем отмену миграции
                (new $className())->down();
                // обновляем её, указывая, что миграция еще не загружена
                DB::table('migrations')->update(['batch' => 0])->where(['id' => $migration->id, 'migration' => $fileNameWithoutExtension])->execute();

                $this->info(sprintf('Миграция %s отменена', $fileNameWithoutExtension));
            }
        });
    }
}