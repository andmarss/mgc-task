<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Filesystem\Workers\Directory;
use App\System\Filesystem\Workers\File;

class CreateSeederCommand extends Command
{
    public const NAME = 'create:seeder';

    protected $signature = ['name'];

    protected $required = ['name'];

    public function execute(): void
    {
        if ($class = $this->argument('name')) {
            /**
             * @var Directory $databaseDirectory
             */
            $databaseDirectory = Directory::open(root('/database'));
            /**
             * @var File $stub
             */
            $stub = $databaseDirectory->directory('stubs')->file('SeederStub.php');
            /**
             * @var string $content
             */
            $content = $stub->read();

            $content = str_replace('StubSeeder', $class, $content);
            /**
             * @var Directory $seedsDirectory
             */
            $seedsDirectory = Directory::open(root('/database/seeds'));

            if (strpos($class, '.php') === false) {
                $class = sprintf('%s.php', $class);
            }

            $exist = $seedsDirectory->files()->filter(function (File $file) use ($class) {
                return $file->name() === $class;
            })->first();
            // если файл существует - не даем создать новый
            if ($exist) {
                $this->error(sprintf('Класс с таким именем уже существует. Команда "%s" не выполнена', static::NAME));
                die;
            }
            /**
             * @var File $file
             */
            $file = File::create($seedsDirectory->path() . $class);

            $file->write($content);

            system('composer dump-autoload');

            $this->info(sprintf('Сидер "%s" успешно создан', $file->name()));
        }
    }
}