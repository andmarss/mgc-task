# giftcard-task
для того, что бы заработало подключение к БД
нужно переименовать config.example.php в config.php

```php
 'connections' => [
         'database' => [
             'name'       => '', // название базы данных
             'username'   => '', // имя пользователя
             'password'   => '', // пароль для подключения к БД
             'connection' => '', // тип соединения: "mysql:host=123.4.5.6", "sqlite:example.db" ...etc
             'charset'    => 'utf8mb4', // кодировка
             'collation'  => 'utf8mb4_unicode_ci', // представление для таблиц
             'engine'     => null, // движок
             'options'    => [   //
                 \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
             ]
         ]
     ]
```

И заполнить поля
> name - имя БД

> username - имя пользователя

> password - пароль

> connection - тип подключения (mysql)

> options - настройки для работы с PDO

> charset - кодировка

> collation - представление для таблиц

> engine - движок БД

После того, как заполненые все поля, нужно в консоли вызвать команду

> php command.php db:seed

И нажать "Да" для всех вопросов (Просто нажать "Enter" на всех этапах)