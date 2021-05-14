<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Database\DB;
use App\System\Filesystem\Workers\Directory;
use App\System\Filesystem\Workers\File;
use App\Traits\MigrationTraits\UnderscoreAndCamelCaseTrait;

class MigrationCreateCommand extends Command
{
    use UnderscoreAndCamelCaseTrait;
    /**
     * Возможный список команд
     * @var array $signature
     */
    protected $signature = [
        'name', 'table', 'create', 'alter'
    ];
    /**
     * Поля, обязательные для заполнения через знак =
     * @var array $required
     */
    protected $required = [
        'name', 'table'
    ];

    public const NAME = 'create:migration';

    public function execute(): void
    {
        /**
         * Наименование файла
         * @var string $migrationName
         */
        $migrationName = $this->argument('name');
        /**
         * Для какой таблицы создается миграция
         * @var string $table
         */
        $table = $this->argument('table');
        /**
         * Необязательный параметр, указывающий, что таблицу нужно создать
         * @var string $create
         */
        $create = $this->argument('create');
        /**
         * Необязательный параметр, указывающий, что таблицу нужно изменить
         * @var string $alter
         */
        $alter = $this->argument('alter');
        $date = new \DateTime();
        $format = 'Y_m_d_His';
        /**
         * Объект папки database
         * @var Directory $databaseDirectory
         */
        $databaseDirectory = Directory::open(root('/database'));

        if (!$create && !$alter) {
            $create = true;
        }
        /**
         * Проверяем, что файла с именем $migrationName еще не существует
         * Что бы не происходила дубликация классов в одном namespace'e
         */
        $condition = $databaseDirectory->directory('migrations')->files()->filter(function (File $file) use ($migrationName) {
            return preg_replace('/\d{4}\_\d{2}\_\d{2}\_\d{6}\_/', '', $file->name(true)) === $migrationName;
        })->count() > 0;

        if ($condition) {
            $this->error(sprintf('Файл миграции "%s" уже существует. Команда "%s" не выполнена', $migrationName, static::NAME));
            exit;
        }

        if ($create) {
            /**
             * @var File $stub
             */
            $stub = $databaseDirectory->directory('stubs')->file('CreateStub', true);

            $content = $stub->read();

            $newFileName = sprintf('%s_%s.php', $date->format($format), $migrationName);
            /**
             * @var File $newFile
             */
            $newFile = File::create(root(sprintf('/database/migrations/%s', $newFileName)));

            $content = str_replace('StubClass', $this->underscoreToCamelCase($migrationName), $content);
            $content = str_replace('stubTable', $table, $content);

            $newFile->write($content);

            if (!DB::table('migrations')->exists()) {
                (new CreateMigrationsTable([]))->execute();
            }

            DB::table('migrations')->create(['migration' => $newFile->name(true)])->execute();

            $this->info('Миграция ' . $newFile->name(true) . ' успешно создана');

            system('composer dump-autoload');

        } elseif ($alter) {
            /**
             * @var File $stub
             */
            $stub = $databaseDirectory->directory('stubs')->file('AlterStub', true);

            $content = $stub->read();

            $newFileName = sprintf('%s_%s.php', $date->format($format), $migrationName);
            /**
             * @var File $newFile
             */
            $newFile = File::create(root(sprintf('/database/migrations/%s', $newFileName)));

            $content = str_replace('StubClass', $this->underscoreToCamelCase($migrationName), $content);
            $content = str_replace('stubTable', $table, $content);

            $newFile->write($content);

            if (!DB::table('migrations')->exists()) {
                (new CreateMigrationsTable([]))->execute();
            }

            DB::table('migrations')->create(['migration' => $newFile->name(true)])->execute();

            $this->info('Миграция ' . $newFile->name(true) . ' успешно создана');

            system('composer dump-autoload');
        }
    }
}