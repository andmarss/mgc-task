<?php

return [
    'connections' => [
        'database' => [
            'name'       => 'mgc_test', // название базы данных
            'username'   => 'root', // имя пользователя
            'password'   => '', // пароль для подключения к БД
            'connection' => 'mysql:host=127.0.0.1', // тип соединения: "mysql:host=123.4.5.6", "sqlite:example.db" ...etc,
            'charset'    => 'utf8mb4', // кодировка
            'collation'  => 'utf8mb4_unicode_ci', // представление для таблиц
            'engine'     => 'InnoDB', // движок
            'options'    => [   //
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]
        ]
    ]
];