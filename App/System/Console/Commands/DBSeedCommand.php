<?php

namespace App\System\Console\Commands;

use App\System\Console\Command;

class DBSeedCommand extends Command
{
    public const NAME = 'db:seed';

    protected $signature = ['class'];

    public function execute(): void
    {
        $databaseSeeder = "Seeds\\DatabaseSeeder";

        if ($class = $this->argument('class')) {

            $seeder = "Seeds\\" . $class;

            if (class_exists($seeder)) {
                $this->info('Начинается загрузка сидера ' . $class);

                (new $seeder)->run();

                $this->info('Сидер успешно загружен');
            } else {
                $this->error('Сидер не был загружен, т.к. отсутствует класс ' . $class);
                die;
            }

        } elseif (class_exists($databaseSeeder)) {
            $this->info('Начинается загрузка сидеров');

            (new $databaseSeeder())->run();

            $this->info('Сидеры успешно загружены');
        } else {
            $this->error('Сидеры не были загружены, т.к. отсутствует файл DatabaseSeeder.php');
            die;
        }
    }
}