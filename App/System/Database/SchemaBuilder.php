<?php

namespace App\System\Database;

class SchemaBuilder
{
    protected $columns = [];

    protected $commands = [];

    protected $indexes = ['primary', 'unique', 'index'];

    protected $alter = false;

    protected $create = true;

    protected $table;

    protected $charset;

    protected $collation;

    protected $engine;

    /**
     * SchemaBuilder constructor.
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function increments(string $name)
    {
        return $this->unsignedInteger($name, true);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function integerIncrements(string $name)
    {
        return $this->unsignedInteger($name, true);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function tinyIncrements(string $name)
    {
        return $this->unsignedTinyInteger($name, true);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function mediumIncrements(string $name)
    {
        return $this->unsignedMediumInteger($name, true);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function bigIncrements(string $name)
    {
        return $this->unsignedBigInteger($name, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnBuilder
     */
    public function unsignedInteger(string $name, bool $autoIncrement = false)
    {
        return $this->integer($name, $autoIncrement, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnBuilder
     */
    public function unsignedTinyInteger(string $name, $autoIncrement = false)
    {
        return $this->tinyInteger($name, $autoIncrement, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnBuilder
     */
    public function unsignedSmallInteger(string $name, $autoIncrement = false)
    {
        return $this->smallInteger($name, $autoIncrement, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnBuilder
     */
    public function unsignedMediumInteger(string $name, $autoIncrement = false)
    {
        return $this->mediumInteger($name, $autoIncrement, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @return ColumnBuilder
     */
    public function unsignedBigInteger(string $name, $autoIncrement = false)
    {
        return $this->bigInteger($name, $autoIncrement, true);
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnBuilder
     */
    public function integer(string $name, bool $autoIncrement = false, bool $unsigned = false)
    {
        return $this->addColumn('integer', $name, compact('autoIncrement', 'unsigned'));
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnBuilder
     */
    public function tinyInteger(string $name, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('tinyInteger', $name, compact('autoIncrement', 'unsigned'));
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnBuilder
     */
    public function smallInteger(string $name, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('smallInteger', $name, compact('autoIncrement', 'unsigned'));
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnBuilder
     */
    public function mediumInteger(string $name, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('mediumInteger', $name, compact('autoIncrement', 'unsigned'));
    }

    /**
     * @param string $name
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnBuilder
     */
    public function bigInteger(string $name, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn('bigInteger', $name, compact('autoIncrement', 'unsigned'));
    }

    /**
     * @param string $name
     * @param int|null $length
     * @return ColumnBuilder
     */
    public function char(string $name, int $length = null)
    {
        $length = $length ?? 255;

        return $this->addColumn('char', $name, compact('length'));
    }

    /**
     * @param string $name
     * @param int|null $length
     * @return ColumnBuilder
     */
    public function string(string $name, int $length = null)
    {
        $length = $length ?: 255;

        return $this->addColumn('string', $name, compact('length'));
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function text(string $name)
    {
        return $this->addColumn('text', $name);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function mediumText(string $name)
    {
        return $this->addColumn('mediumText', $name);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function longText(string $name)
    {
        return $this->addColumn('longText', $name);
    }

    /**
     * @param string $name
     * @param int $total
     * @param int $places
     * @return ColumnBuilder
     */
    public function float(string $name, int $total = 8, int $places = 2)
    {
        return $this->addColumn('float', $name, compact('total', 'places'));
    }

    /**
     * @param string $name
     * @param int|null $total
     * @param int|null $places
     * @return ColumnBuilder
     */
    public function double(string $name, int $total = null, int $places = null)
    {
        return $this->addColumn('double', $name, compact('total', 'places'));
    }

    /**
     * @param string $name
     * @param int $total
     * @param int $places
     * @return ColumnBuilder
     */
    public function decimal(string $name, int $total = 8, int $places = 2)
    {
        return $this->addColumn('decimal', $name, compact('total', 'places'));
    }

    /**
     * @param string $name
     * @param int $total
     * @param int $places
     * @return ColumnBuilder
     */
    public function unsignedDecimal(string $name, int $total = 8, int $places = 2)
    {
        return $this->addColumn('decimal', $name, [
            'total' => $total, 'places' => $places, 'unsigned' => true
        ]);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function boolean(string $name)
    {
        return $this->addColumn('boolean', $name);
    }

    /**
     * @param string $name
     * @param array $allowed
     * @return ColumnBuilder
     */
    public function enum(string $name, array $allowed)
    {
        return $this->addColumn('enum', $name, compact('allowed'));
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function json(string $name)
    {
        return $this->addColumn('json', $name);
    }

    /**
     * @param string $name
     * @return ColumnBuilder
     */
    public function date(string $name)
    {
        return $this->addColumn('date', $name);
    }

    /**
     * @param string $name
     * @param int $precision
     * @return ColumnBuilder
     */
    public function dateTime(string $name, $precision = 0)
    {
        return $this->addColumn('dateTime', $name, compact('precision'));
    }

    /**
     * @param string $name
     * @param int $precision
     * @return ColumnBuilder
     */
    public function time(string $name, $precision = 0)
    {
        return $this->addColumn('time', $name, compact('precision'));
    }

    /**
     * @param string $name
     * @param int $precision
     * @return ColumnBuilder
     */
    public function timestamp(string $name, $precision = 0)
    {
        return $this->addColumn('timestamp', $name, compact('precision'));
    }

    /**
     * @param int $precision
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->nullable();

        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * @param $tables
     * @param string|null $name
     * @return ForeignKeyBuilder
     */
    public function foreign($tables, string $name = null)
    {
        return $this->indexCommand('foreign', $tables, $name);
    }

    /**
     * @param $tables
     * @param string|null $name
     * @return ForeignKeyBuilder
     */
    public function index($tables, string $name = null)
    {
        return $this->indexCommand('index', $tables, $name);
    }

    /**
     * @param $columns
     * @param string|null $name
     * @return ForeignKeyBuilder
     */
    public function unique($columns, string $name = null)
    {
        return $this->indexCommand('unique', $columns, $name);
    }

    /**
     * @param $tables
     * @param string|null $name
     * @return ForeignKeyBuilder
     */
    public function primary($tables, string $name = null)
    {
        return $this->indexCommand('primary', $tables, $name);
    }

    /**
     * @param $columns
     * @return ForeignKeyBuilder
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * @param null $index
     * @return ForeignKeyBuilder
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * @param $index
     * @return ForeignKeyBuilder
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * @param $index
     * @return ForeignKeyBuilder
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * @param $index
     * @return ForeignKeyBuilder
     */
    public function dropForeign($index)
    {
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * @return ForeignKeyBuilder
     */
    public function dropTimestamps()
    {
        return $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * @return ForeignKeyBuilder
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * проверяем, есть ли среди колонок - команды-индексы (primary, unique, index)
     */
    protected function addFluentIndexes()
    {
        foreach ($this->columns as $column) {
            foreach ($this->indexes as $index) {
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);

                    continue 2;
                } elseif (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});

                    continue 2;
                }
            }
        }
    }

    /**
     * @param string $charset
     * @return SchemaBuilder
     */
    protected function setCharset(?string $charset = null): SchemaBuilder
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Если есть поля, но таблица не создается - значит нужно добавить новые поля
     */
    protected function checkCommands()
    {
        if (count($this->columns) > 0 && !$this->creating()) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        $this->addFluentIndexes();
    }

    /**
     * @return string|null
     */
    protected function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * @param string $collation
     * @return SchemaBuilder
     */
    protected function setCollation(?string $collation = null): SchemaBuilder
    {
        $this->collation = $collation;

        return $this;
    }

    /**
     * @return string|null
     */
    protected function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * @param string|null $engine
     * @return SchemaBuilder
     */
    protected function setEngine(?string $engine = null): SchemaBuilder
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @return string|null
     */
    protected function getEngine(): ?string
    {
        return $this->engine;
    }

    /**
     * @return ForeignKeyBuilder
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * @return ForeignKeyBuilder
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnBuilder
     */
    protected function addColumn(string $type, string $name, array $parameters = []): ColumnBuilder
    {
        $this->columns[] = $column = new ColumnBuilder(
            compact('type', 'name') + $parameters
        );

        return $column;
    }

    /**
     * @param string $type
     * @param $columns
     * @param $index
     * @return ForeignKeyBuilder
     */
    protected function indexCommand(string $type, $columns, $index)
    {
        $columns = (array) $columns;

        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand(
            $type, compact('index', 'columns')
        );
    }

    /**
     * @param $command
     * @param $type
     * @param $index
     * @return ForeignKeyBuilder
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];

        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }

        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * @param string $type
     * @param array $columns
     * @return mixed
     */
    protected function createIndexName(string $type, array $columns)
    {
        $index = strtolower($this->table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * @param $name
     * @param array $parameters
     * @return ForeignKeyBuilder
     */
    protected function addCommand($name, array $parameters = []): ForeignKeyBuilder
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return ForeignKeyBuilder
     */
    protected function createCommand(string $name, array $parameters = []): ForeignKeyBuilder
    {
        return new ForeignKeyBuilder(compact('name') + $parameters);
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Происходить создание таблицы, или изменение?
     * @return bool
     */
    protected function creating(): bool
    {
        return collect($this->commands)->contains(function (Liquid $command) {
            return $command->name === 'create';
        });
    }

    /**
     * @return array
     */
    public function compile(): array
    {
        $this->checkCommands();

        return (new SqlCompiler($this))->getStatements();
    }

    public function __call(string $method, array $arguments)
    {
        if (method_exists($this, $method)) {
            return $this->{$method}(...$arguments);
        }
    }
}