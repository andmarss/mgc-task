<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;
use App\System\Filesystem\Workers\{Directory, File};

class CreateCommandCommand extends Command
{
    public const NAME = 'create:command';

    protected $signature = ['name', 'command'];

    protected $required = ['name'];

    public function execute(): void
    {
        /**
         * @var Directory $commandsDirectory
         */
        $commandsDirectory = Directory::open(root('/App/System/Console/Commands'));
        /**
         * @var string $commandFileName
         */
        $commandFileName = $this->argument('name');
        /**
         * @var File|null $exist
         */
        $exist = $commandsDirectory->file($commandFileName, true);
        // если файл с таким именем существует - сообщаем об ошибке, и завершаем работу
        if ($exist) {
            $this->error(sprintf('Файл с таким именем уже существует. Команда "%s" не выполнена', static::NAME));
            die;
        }
        /**
         * Черновик, с которого копируется контент команд
         * @var File $stub
         */
        $stub = $commandsDirectory->directory('stubs')->file('CommandStub.php');
        /**
         * Создаем файл новой консольной команды
         * @var File $commandFile
         */
        $commandFile = File::create($commandsDirectory->path() . $commandFileName . '.php');
        /**
         * Контент чернового файла
         * @var string $conten
         */
        $content = $stub->read();

        $content = str_replace('CommandStub', $commandFileName, $content);

        if ($command = $this->argument('command')) {
            $content = str_replace('stub:command', $command, $content);
        } else {
            $content = str_replace('stub:command', '', $content);
        }
        // записываем измененный контент черновика в новый файл
        $commandFile->write($content);

        system('composer dump-autoload');

        $this->info(sprintf('Файл консольной команды "%s" успешно создан', $commandFile->name()));
    }
}