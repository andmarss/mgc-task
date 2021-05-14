<?php

namespace App\System\Database;

class Join
{
    protected $joins = [];

    protected $type;

    protected $table;

    public const LEFT_JOIN = 'LEFT';
    public const RIGHT_JOIN = 'RIGHT';
    public const INNER_JOIN = 'INNER';
    public const OUTER_JOIN = 'OUTER';

    public function __construct(string $table, string $type = 'INNER')
    {
        $this->type = $type;
        $this->table = $table;
    }

    public function on(string $first, string $operator, string $second): Join
    {
        $this->joins[] = sprintf(
            '%s JOIN %s ON %s %s %s',
            $this->type,
            $this->table,
            $first,
            $operator,
            $second
        );

        return $this;
    }

    public function orOn(string $first, string $operator, string $second): Join
    {
        if (count($this->joins) === 0) {
            throw new \Exception('Сперва должен быть вызван метод "on"');
        }

        $this->joins[] = sprintf(
            'OR %s %s %s',
            $first,
            $operator,
            $second
        );

        return $this;
    }

    public function escape($value): string
    {
        return DB::escape($value);
    }

    public function compile(): string
    {
        return implode(' ', array_values($this->joins));
    }

    public function __toString(): string
    {
        return $this->compile();
    }
}