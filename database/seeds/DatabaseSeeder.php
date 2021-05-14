<?php

namespace Seeds;

use App\System\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->console->confirm('Нужно ли обновить миграции перед заполнением? Это очистит все старые данные.', true)) {
            $this->console->call('migrate:fresh');
        }

        if ($this->console->confirm('Загрузить категории?', true)) {
            $this->call(CategoryTableSeeder::class);
        }

        if ($this->console->confirm('Загрузить продукты?', true)) {
            $this->call(ProductsTableSeeder::class);
        }
    }
}