<?php

namespace App\System\Database;

abstract class Migration
{
    protected $table;

    protected $charset;

    protected $collate;

    protected $engine;

    abstract public function up();

    abstract public function down();

    /**
     * Создать таблицу
     * @param \Closure $callback
     */
    protected function create(\Closure $callback): void
    {
        $builder = new SchemaBuilder($this->table);

        $builder->create();

        call_user_func($callback, $builder);

        $db = DB::getInstance()->setQuery(new QueryBuilder());

        foreach ($builder->compile() as $statement) {
            $db->query($statement);
        }
    }

    /**
     * Изменить таблицу (добавить поле, удалить поле, добавить или удалить индекс и т.д.)
     * @param \Closure $callback
     */
    protected function alter(\Closure $callback): void
    {
        $builder = new SchemaBuilder($this->table);

        call_user_func($callback, $builder);

        $db = DB::getInstance()->setQuery(new QueryBuilder());

        foreach ($builder->compile() as $statement) {
            $db->query($statement);
        }
    }

    /**
     * Удалить таблицу. Необязательный параметр $callback может быть передан, что бы, например, сперва удалить индексы
     * @param \Closure|null $callback
     */
    protected function drop(\Closure $callback = null): void
    {
        $builder = new SchemaBuilder($this->table);

        if ($callback instanceof \Closure) {
            call_user_func($callback, $builder);
        }

        $builder->drop();

        $db = DB::getInstance()->setQuery(new QueryBuilder());

        foreach ($builder->compile() as $statement) {
            $db->query($statement);
        }
    }

    /**
     * Удалить таблицу, если она есть. Необязательный параметр $callback может быть передан, что бы, например, сперва удалить индексы
     * @param \Closure|null $callback
     */
    protected function dropIfExists(\Closure $callback = null): void
    {
        $builder = new SchemaBuilder($this->table);

        if ($callback instanceof \Closure) {
            call_user_func($callback, $builder);
        }

        $builder->dropIfExists();

        $db = DB::getInstance()->setQuery(new QueryBuilder());

        foreach ($builder->compile() as $statement) {
            $db->query($statement);
        }
    }

    /**
     * @param string $charset
     * @return $this
     */
    protected function charset(string $charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @param string $collate
     * @return $this
     */
    protected function collate(string $collate)
    {
        $this->collate = $collate;

        return $this;
    }

    /**
     * @param string $engine
     * @return $this
     */
    protected function engine(string $engine)
    {
        $this->engine = $engine;

        return $this;
    }
}