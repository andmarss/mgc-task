<?php

namespace App\System\Database;

class SqlCompiler
{
    /**
     * @var SchemaBuilder $builder
     */
    protected $builder;
    /**
     * Возможные модификаторы колонки
     *
     * @var array $modifiers
     */
    protected $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'Nullable', 'Default', 'Increment', 'Comment'
    ];
    /**
     * @var array $serials
     */
    protected $serials = [
        'bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'
    ];

    /**
     * SqlCompiler constructor.
     * @param SchemaBuilder $builder
     */
    public function __construct(SchemaBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return array
     */
    protected function getColumns(): array
    {
        $columns = [];

        foreach ($this->builder->getColumns() as $column) {
            $sql = sprintf("`%s` %s", $column->name, $this->getType($column));

            $columns[] = $this->addModifiers($sql, $column);
        }

        return $columns;
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileForeign(Liquid $command): string
    {
        $sql = sprintf('ALTER TABLE `%s` ADD CONSTRAINT `%s` ', $this->builder->getTable(), $command->index);

        $sql .= sprintf(
            'FOREIGN KEY (%s) references `%s` (%s)',
                implode(', ', array_map(function ($column) {
                    return sprintf('`%s`', $column);
                }, (array) $command->columns)),

                $command->on,

                implode(', ', array_map(function ($column) {
                    return sprintf('`%s`', $column);
                }, (array) $command->references))
        );

        if (!is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * @param bool $create
     * @return string
     */
    public function getStatements(): array
    {
        $statements = [];

        foreach ($this->builder->getCommands() as $command) {
            $method = 'compile' . ucfirst($command->name);

            if (method_exists($this, $method)) {
                if (!is_null($sql = $this->{$method}($command))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    /**
     * @return string
     */
    public function compileCreate()
    {
        $sql = $this->compileCreateTable();

        $sql = $this->compileCreateEncoding($sql);

        return $this->compileCreateEngine($sql);
    }

    /**
     * @return string
     */
    protected function compileCreateTable(): string
    {
        return sprintf('CREATE TABLE `%s` (%s)', $this->builder->getTable(), implode(', ', $this->getColumns()));
    }

    /**
     * @param string $sql
     * @return string
     */
    protected function compileCreateEncoding(string $sql): string
    {
        if ($this->builder->getCharset()) {
            $sql .= ' default CHARSET ' . $this->builder->getCharset();
        } else {
            $sql .= ' default CHARSET ' . config('connections/database/charset');
        }

        if ($this->builder->getCollation()) {
            $sql .= ' collate ' . $this->builder->getCollation();
        } else {
            $sql .= ' collate ' . config('connections/database/collation');
        }

        return $sql;
    }

    /**
     * @param string $sql
     * @return string
     */
    protected function compileCreateEngine(string $sql): string
    {
        if ($this->builder->getEngine()) {
            return $sql . ' engine = ' . $this->builder->getEngine();
        } elseif (is_null($engine = config('connections/database/engine'))) {
            return $sql . ' engine = ' . $engine;
        }

        return $sql;
    }

    /**
     * @return string
     */
    protected function compileAdd()
    {
        $columns = array_map(function ($column) {
            return sprintf('ADD %s', $column);
        }, $this->getColumns());

        return sprintf("ALTER TABLE `%s` %s", $this->builder->getTable(), implode(', ', $columns));
    }

    /**
     * @param Liquid $command
     * @return mixed
     */
    protected function compilePrimary(Liquid $command)
    {
        $command->name(null);

        return $this->compileKey($command, 'primary key');
    }

    /**
     * @param Liquid $command
     * @return mixed
     */
    protected function compileUnique(Liquid $command)
    {
        return $this->compileKey($command, 'unique');
    }

    /**
     * @param Liquid $command
     * @return mixed
     */
    protected function compileIndex(Liquid $command)
    {
        return $this->compileKey($command, 'index');
    }

    /**
     * @param Liquid $command
     * @param string $type
     * @return string
     */
    protected function compileKey(Liquid $command, string $type): string
    {
        return sprintf(
            'ALTER TABLE `%s` ADD %s `%s`(%s)',
                    $this->builder->getTable(),
                    $type,
                    $command->index,
                    implode(', ', array_map(function ($column){
                        return  '`' . $column . '`';
                    }, $command->columns))
            );
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDrop(Liquid $command): string
    {
        return sprintf('DROP TABLE `%s`', $this->builder->getTable());
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDropIfExists(Liquid $command): string
    {
        return sprintf('DROP TABLE IF EXISTS `%s`', $this->builder->getTable());
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDropColumn(Liquid $command)
    {
        $columns = array_map(function ($column){
            return 'DROP `' . $column . '`';
        }, $command->columns);

        return sprintf('ALTER TABLE %s %s', $this->builder->getTable(), implode(', ', $columns));
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDropPrimary(Liquid $command): string
    {
        return 'ALTER TABLE ' . $this->builder->getTable() . ' DROP PRIMARY KEY';
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDropUnique(Liquid $command): string
    {
        $index = $command->index;

        return sprintf('ALTER TABLE %s DROP INDEX `%s`', $this->builder->getTable(), $index);
    }

    /**
     * @param Liquid $command
     * @return string
     */
    protected function compileDropIndex(Liquid $command): string
    {
        $index = $command->index;

        return sprintf('ALTER TABLE %s DROP INDEX `%s`', $this->builder->getTable(), $index);
    }

    protected function compileDropForeign(Liquid $command)
    {
        $index = $command->index;

        return sprintf('ALTER TABLE %s DROP FOREIGN KEY `%s`', $this->builder->getTable(), $index);
    }

    /**
     * @param string $sql
     * @param Liquid $column
     * @return string
     */
    protected function addModifiers(string $sql, Liquid $column): string
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = sprintf('modify%s', (string) $modifier))) {
                $sql .= $this->{$method}($column);
            }
        }

        return $sql;
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function getType(Liquid $column): string
    {
        return $this->{'type' . ucfirst($column->type)}($column);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeChar(Liquid $column): string
    {
        return sprintf('char(%s)', (string) $column->length);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeString(Liquid $column): string
    {
        return sprintf('varchar(%s)', (string) $column->length);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeText(Liquid $column): string
    {
        return 'text';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeMediumText(Liquid $column): string
    {
        return 'mediumtext';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeLongText(Liquid $column): string
    {
        return 'longtext';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeBigInteger(Liquid $column): string
    {
        return 'bigint';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeInteger(Liquid $column): string
    {
        return 'int';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeMediumInteger(Liquid $column): string
    {
        return 'mediumint';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeTinyInteger(Liquid $column): string
    {
        return 'tinyint';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeSmallInteger(Liquid $column): string
    {
        return 'smallint';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeFloat(Liquid $column): string
    {
        return $this->typeDouble($column);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeDouble(Liquid $column): string
    {
        if ($column->total && $column->places) {
            return sprintf("double(%s, %s)", (string) $column->total, (string) $column->places);
        }

        return 'double';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeDecimal(Liquid $column)
    {
        return sprintf("decimal(%s, %s)", (string) $column->total, (string) $column->places);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeBoolean(Liquid $column): string
    {
        return 'tinyint(1)';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeEnum(Liquid $column): string
    {
        return sprintf('enum(%s)', $column->allowed);
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeJson(Liquid $column): string
    {
        return 'json';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeDate(Liquid $column): string
    {
        return 'date';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeDateTime(Liquid $column): string
    {
        $columnType = $column->precision ? sprintf('datetime(%s)', (string) $column->precision) : 'datetime';

        return $column->useCurrent ? sprintf('%s default CURRENT_TIMESTAMP', $columnType) : $columnType;
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeTime(Liquid $column): string
    {
        return $column->precision ? sprintf('time(%s)', (string) $column->precision) : 'time';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function typeTimestamp(Liquid $column): string
    {
        $columnType = $column->precision ? sprintf('timestamp(%s)', (string) $column->precision) : 'timestamp';

        return $column->useCurrent ? sprintf('%s default CURRENT_TIMESTAMP', $columnType) : $columnType;
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyUnsigned(Liquid $column): string
    {
        if ($column->unsigned) {
            return ' unsigned';
        }

        return '';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyCharset(Liquid $column): string
    {
        if (!is_null($column->charset)) {
            return ' charset set ' . $column->charset;
        }

        return '';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyCollate(Liquid $column): string
    {
        if (! is_null($column->collation)) {
            return " collate '{$column->collation}'";
        }

        return '';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyNullable(Liquid $column): string
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyDefault(Liquid $column): string
    {
        if (!is_null($column->default)) {
            return ' default ' . (is_bool($column->default)
                ? "'".(int) $column->default."'"
                : "'".(string) $column->default."'");
        }

        return '';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyIncrement(Liquid $column): string
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' auto_increment primary key';
        }

        return '';
    }

    /**
     * @param Liquid $column
     * @return string
     */
    protected function modifyComment(Liquid $column): string
    {
        if (!is_null($column->comment)) {
            return "comment '" . addslashes($column->comment) . "'";
        }

        return '';
    }

    /**
     * @param array|string $value
     * @return string
     */
    protected function escapeString($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return DB::escape($value);
    }
}