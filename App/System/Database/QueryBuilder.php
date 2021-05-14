<?php

namespace App\System\Database;

use App\Models\Model;
use App\Models\Parameter;
use App\Models\Product;
use App\Pivots\ParameterPivot;
use App\System\Collection;
use App\System\Database\Relations\Pivot;
use App\System\Database\Relations\Relation;

class QueryBuilder
{
    protected $distinct = false;

    protected $sql = [
        'select'    => '',
        'update'    => '',
        'insert'    => '',
        'table'     => '',
        'joins'     => [],
        'where'     => [],
        'limit'     => '',
        'offset'    => 0,
        'order_by'  => '',
        'order_how' => 'ASC',
        'delete'    => ''
    ];

    protected $select = false;
    protected $update = false;
    protected $insert = false;
    protected $delete = false;
    protected $exists = false;

    protected $relations = [];

    /**
     * @var Model $model
     */
    protected $model;

    protected static $operators = ['=', '>', '<', '<=', '>=', '<>'];
    /**
     * @var null|Collection|Model $result
     */
    protected $result;

    /**
     * @param string $fields
     * @return QueryBuilder
     */
    public function select($fields = '*'): QueryBuilder
    {
        if(is_string($fields)) {
            $this->sql['select'] = "{$fields}";
        } elseif (is_array($fields) && count($fields) > 0) {
            $fields = implode(', ', $fields);

            $this->sql['select'] = "${fields}";
        } elseif (func_num_args() > 1) {
            $fields = implode(', ', func_get_args());

            $this->sql['select'] = "${fields}";
        }

        $this->resetActions();

        $this->select = true;

        return $this;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function table(string $table): QueryBuilder
    {
        $this->sql['table'] = $table;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTable(): ?string
    {
        return isset($this->sql['table']) ? $this->sql['table'] : null;
    }

    /**
     * Проверка существования таблицы
     * @return bool
     * @throws \Exception
     */
    public function exists(): bool
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        $this->select('*');

        $table = $this->sql['table'];

        $this->table('information_schema.tables');

        $this->where(['table_schema' => config('connections/database/name'), 'table_name' => $table]);

        $this->exists = true;

        return count(DB::getInstance()->setQuery($this)->query(null)) > 0;
    }

    /**
     * @param array $attributes
     * @return bool
     * @throws \Exception
     */
    public function rowExists(array $attributes): bool
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        $this->select('*');

        $this->where($attributes);

        return DB::getInstance()->setQuery($this)->query()->count() > 0;
    }

    /**
     * @param array $conditions
     * @return QueryBuilder
     * @throws \Exception
     */
    public function where($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (func_num_args() === 3) { // where(field,operator,value)

            /**
             * @var string $field
             * @var string $operator
             * @var string $value
             */
            [$field, $operator, $value] = func_get_args();

            if (!($value instanceof Expression)) {
                $value = $this->escape($value);
            }

            if(in_array($operator, static::$operators)) {
                $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf('%s %s %s', $field, $operator, $value);
            }

        } elseif (is_array($conditions) && count($conditions) > 0) {

            $where = implode(' AND ', array_map(function ($key, $value){

                if (is_bool($value)) {
                    $value = intval($value);
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } elseif (!($value instanceof Expression)) {
                    $value = $this->escape($value);
                }

                return sprintf('%s=%s', $key, $value);
            }, array_keys($conditions), array_values($conditions)));

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . $where;

        }

        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @param null $selectedField
     * @return $this
     * @throws \Exception
     */
    public function whereIn(string $field, $value, $selectedField = null): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if(is_callable($value)) { // $value - функция, параметром которой является экземпляр конструктора запроса

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf('%s IN (%s)', $field, call_user_func($value, (new static())));

        } elseif (is_string($value) && is_string($selectedField)) {

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf("%s IN (SELECT %s FROM %s)", $field, $selectedField, $value);

        } elseif (is_array($value)) {

            $values = array_map(function ($value) {
                if (is_bool($value)) {
                    $value = intval($value);
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } elseif (!($value instanceof Expression)) {
                    $value = $this->escape($value);
                }

                return $value;
            }, array_values($value));
            $values = implode(', ', array_values($values));
            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf("%s IN (%s)", $field, $values);

        }

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param string|null $selectedField
     * @return QueryBuilder
     * @throws \Exception
     */
    public function whereNotIn(string $field, $value, string $selectedField = null): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if(is_callable($value)) { // $value - функция, параметром которой является экземпляр конструктора запроса

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf('%s NOT IN (%s)', $field, call_user_func($value, new static()));

        } elseif (is_string($value) && is_string($selectedField)) {

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf("%s NOT IN (SELECT %s FROM %s)", $field, $selectedField, $value);

        } elseif (is_array($value)) {

            $values = array_map(function ($value) {
                if (is_bool($value)) {
                    $value = intval($value);
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } elseif (!($value instanceof Expression)) {
                    $value = $this->escape($value);
                }

                return $value;
            }, array_values($value));
            $values = implode(', ', array_values($values));
            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf("%s NOT IN (%s)", $field, $values);

        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function orWhere($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (count($this->sql['where']) > 0) {

            if (func_num_args() === 3) { // where(field,operator,value)

                /**
                 * @var string $field
                 * @var string $operator
                 * @var string $value
                 */
                [$field, $operator, $value] = func_get_args();

                if (!($value instanceof Expression)) {
                    $value = $this->escape($value);
                }

                if(in_array($operator, static::$operators)) {
                    $this->sql['where'][] = sprintf('OR (%s %s %s)', $field, $operator, $value);
                }

            } elseif (is_array($conditions) && count($conditions) > 0) {

                $where = sprintf(' OR (%s)', implode(' AND ', array_map(function ($key, $value){

                    if (is_bool($value)) {
                        $value = intval($value);
                    } elseif (is_null($value)) {
                        $value = 'NULL';
                    } elseif (!($value instanceof Expression)) {
                        $value = $this->escape($value);
                    }

                    return sprintf('%s=%s', $key, $value);
                }, array_keys($conditions), array_values($conditions))));

                $this->sql['where'][] = $where;

            }

        } else {
            throw new \Exception('Сперва должен быть выбран метод "where"');
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function whereLike($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        /**
         * @var bool $condition
         */
        $condition = collect(array_keys($conditions))->every(function ($key) {
            return is_string($key);
        });

        if (is_array($conditions) && $condition && count($conditions) > 0) {

            $where = implode(' AND ', array_map(function ($key, $value){

                if (is_bool($value)) {
                    $value = intval($value);
                } elseif (is_null($value)) {
                    $value = 'NULL';
                }

                return sprintf('%s LIKE "%%%s%%"', $key, $value);
            }, array_keys($conditions), array_values($conditions)));

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . $where;
        } elseif (func_num_args() === 2) {
            /**
             * @var string $field
             * @var string $value
             */
            [$field, $value] = func_get_args();

            $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . sprintf('%s LIKE "%%%s%%"', $field, $value);
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function orWhereLike($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (count($this->sql['where']) > 0) {
            /**
             * @var bool $condition
             */
            $condition = collect(array_keys($conditions))->every(function ($key) {
                return is_string($key);
            });

            if (is_array($conditions) && $condition && count($conditions) > 0) {

                $where = sprintf(' OR (%s)', implode(' AND ', array_map(function ($key, $value){

                    if (is_bool($value)) {
                        $value = intval($value);
                    } elseif (is_null($value)) {
                        $value = 'NULL';
                    }

                    return sprintf('%s LIKE "%%%s%%"', $key, $value);
                }, array_keys($conditions), array_values($conditions))));

                $this->sql['where'][] = $where;
            } elseif (func_num_args() === 2) {
                /**
                 * @var string $field
                 * @var string $value
                 */
                [$field, $value] = func_get_args();

                $this->sql['where'][] = sprintf('OR %s LIKE "%%%s%%"', $field, $value);
            }

        } else {
            throw new \Exception('Сперва должен быть выбран метод "where"');
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return QueryBuilder
     * @throws \Exception
     */
    public function whereNull($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $where = implode(' AND ', array_map(function ($column){
            return sprintf('%s IS NULL', $column);
        }, $conditions));

        $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . $where;

        return $this;
    }

    /**
     * @param array $conditions
     * @return QueryBuilder
     * @throws \Exception
     */
    public function orWhereNull($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (!is_array($conditions)) $conditions = (array) $conditions;

        if (count($this->sql['where']) > 0) {
            /**
             * @var bool $condition
             */
            $condition = collect(array_keys($conditions))->every(function ($key) {
                return is_numeric($key);
            });

            if (is_array($conditions) && $condition && count($conditions) > 0) {

                $where = sprintf(' OR (%s)', implode(' AND ', array_map(function (?string $column){
                    return sprintf('%s IS NULL', $column);
                }, $conditions)));

                $this->sql['where'][] = $where;
            }

        } else {
            throw new \Exception('Сперва должен быть выбран метод "where" или "whereNull"');
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function whereNotNull($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $where = implode(' AND ', array_map(function ($column){
            return sprintf('%s IS NOT NULL', $column);
        }, array_values($conditions)));

        $this->sql['where'][] = (count((array) $this->sql['where']) ? 'AND ' : '') . $where;

        return $this;
    }

    /**
     * @param array $conditions
     * @return QueryBuilder
     * @throws \Exception
     */
    public function orWhereNotNull($conditions = []): QueryBuilder
    {
        if(!$this->sql['select'] && !$this->sql['delete'] && !$this->sql['update']) {
            $this->select();
        }

        if(!isset($this->sql['table']) && !isset($this->sql['update']) && !isset($this->sql['delete'])) {
            throw new \Exception('Необходимо указать имя таблицы, откуда будет происходить выборка данных');
        }

        if (!is_array($conditions)) $conditions = (array) $conditions;

        if (count($this->sql['where']) > 0) {
            /**
             * @var bool $condition
             */
            $condition = collect(array_keys($conditions))->every(function ($key) {
                return is_numeric($key);
            });

            if (is_array($conditions) && $condition && count($conditions) > 0) {

                $where = sprintf(' OR (%s)', implode(' AND ', array_map(function (?string $column){
                    return sprintf('%s IS NOT NULL', $column);
                }, $conditions)));

                $this->sql['where'][] = $where;
            }

        } else {
            throw new \Exception('Сперва должен быть выбран метод "where" или "whereNull"');
        }

        return $this;
    }

    /**
     * @param $relation
     * @param \Closure $callback
     * @param string $operator
     * @param int $count
     * @return QueryBuilder
     * @throws \Exception
     */
    public function whereHas($relation, \Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);
    }

    /**
     * @param $relation
     * @param string $operator
     * @param int $count
     * @param string $boolean
     * @param \Closure|null $callback
     * @return QueryBuilder
     * @throws \Exception
     */
    public function has($relation, string $operator = '>=', int $count = 1, string $boolean = 'AND', \Closure $callback = null)
    {
        /**
         * @var Relation $relation
         */
        $relation = $this->getModel()->{$relation}();

        $query = $relation->getRelationCountQuery($relation->getRelated()->getQuery(), $this);

        if ($callback) $callback($query);

        switch (strtoupper(trim($boolean))) {
            case 'AND':
                return $this->where(new Expression('(' . $query . ')'), $operator, $count);
                break;
            case 'OR':
                return $this->orWhere(new Expression('(' . $query . ')') , $operator, $count);
                break;
        }

        return $this;
    }

    /**
     * @param string $field
     * @return QueryBuilder
     */
    public function orderBy(string $field = null): QueryBuilder
    {
        if (is_null($field)) {
            $field = 'id';
        }

        $this->sql['order_by'] = $field;

        return $this;
    }

    public function setWheres(array $wheres)
    {
        if ($wheres) {
            $this->sql['where'] = $wheres;
        }

        return $this;
    }

    /**
     * @param array $wheres
     * @return $this
     */
    public function mergeWheres(array $wheres)
    {
        $this->sql['where'] = array_merge($this->sql['where'], $wheres);

        return $this;
    }

    /**
     * @return array
     */
    public function getWheres(): array
    {
        return is_array($this->sql['where']) ? $this->sql['where'] : [];
    }

    /**
     * @return string
     */
    public function getSelects(): string
    {
        return $this->sql['select'] ? $this->sql['select'] : '';
    }

    /**
     * @return QueryBuilder
     */
    public function asc(): QueryBuilder
    {
        $this->sql['order_how'] = 'ASC';

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function desc(): QueryBuilder
    {
        $this->sql['order_how'] = 'DESC';

        return $this;
    }

    /**
     * @param int $from
     * @param int|null $length
     * @return QueryBuilder
     */
    public function limit(int $length, int $from = null): QueryBuilder
    {
        if (!is_null($from)) {
            $this->sql['limit'] = sprintf('LIMIT %s, %s', (string) $from, (string) $length);
        } elseif (is_null($from)) {
            $this->sql['limit'] = sprintf('LIMIT %s', (string) $length);
        }

        return $this;
    }

    /**
     * @param int $page
     * @param int $per_page
     * @return QueryBuilder
     */
    public function forPage(int $page, int $per_page = 15): QueryBuilder
    {
        $page = $page > 0 ? $page : abs($page);

        return $this->limit($per_page, ($page - 1) * $per_page);
    }

    /**
     * @param $relations
     * @return QueryBuilder
     * @throws \Exception
     */
    public function with($relations)
    {
        if (!($this->getModel() instanceof Model)) throw new \Exception('Пустая модель при попытке загрузки связей');

        $relations = $this->parseRelations(is_string($relations) ? func_get_args() : $relations);

        $this->relations = array_merge($this->relations, $relations);

        return $this;
    }

    /**
     * @param array $relations
     * @return array
     */
    protected function parseRelations(array $relations)
    {
        $result = [];

        foreach ($relations as $name => $constraints) {
            if (is_numeric($name)) {
                $name = $constraints;

                [$name, $constraints] = [$name, static function() {

                }];
            }

            $result[$name] = $constraints;
        }

        return $result;
    }

    /**
     * @param array $models
     * @return array|mixed
     */
    public function loadRelations(array $models)
    {
        foreach ($this->relations as $name => $closure) {
            $models = $this->loadRelation($models, $name, $closure);
        }

        return $models;
    }

    /**
     * @param array $models
     * @param $name
     * @param \Closure $closure
     * @return mixed
     */
    protected function loadRelation(array $models, $name, \Closure $closure)
    {
        /**
         * @var Relation $relation
         */
        $relation = $this->getRelation($name);

        $relation->addRelationConstraints($models);

        $closure($relation);

        return $relation->match(
            $models, $relation->getResults(), $name
        );
    }

    protected function getRelation($name)
    {
        return $this->getModel()->$name();
    }

    /**
     * @param string $table
     * @param $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * @return QueryBuilder
     */
    public function join(string $table, $first, $operator = null, $second = null, $type = 'INNER'): QueryBuilder
    {
        /**
         * @var Join $join
         */
        $join = new Join($table, $type);

        if ($first instanceof \Closure) {
            $join = call_user_func($first, $join);

            $this->sql['joins'][] = $join->compile();
        } else {
            if (!in_array((string) $operator, static::$operators)) {
                throw new \InvalidArgumentException(sprintf('Неизвестный оператор "%s"', (string) $operator));
            }

            $this->sql['joins'][] = $join->on($first, $operator, $second)->compile();
        }

        return $this;
    }

    /**
     * @param string $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @return QueryBuilder
     */
    public function leftJoin(string $table, $first, $operator = null, $second = null): QueryBuilder
    {
        return $this->join($table, $first, $operator, $second, Join::LEFT_JOIN);
    }

    /**
     * @param string $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @return QueryBuilder
     */
    public function rightJoin(string $table, $first, $operator = null, $second = null): QueryBuilder
    {
        return $this->join($table, $first, $operator, $second, Join::RIGHT_JOIN);
    }

    /**
     * @param string $table
     * @param $first
     * @param null $operator
     * @param null $second
     * @return QueryBuilder
     */
    public function outerJoin(string $table, $first, $operator = null, $second = null): QueryBuilder
    {
        return $this->join($table, $first, $operator, $second, Join::OUTER_JOIN);
    }

    /**
     * @param array $attributes
     * @return QueryBuilder
     * @throws \Exception
     */
    public function update(array $attributes): QueryBuilder
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        if (count($attributes) === 0) {
            throw new \Exception('При обновлении базы передан пустой массив аттрибутов');
        }

        if (!is_null($this->model)) {
            $attributes = $this->model->fromFillable($attributes);
        }

        $update = sprintf('UPDATE %s SET %s', $this->sql['table'], implode(', ', array_map(function ($key, $value) {

            if (is_bool($value)) {
                $value = intval($value);
            } elseif (is_null($value)) {
                $value = 'NULL';
            } else {
                $value = $this->escape($value);
            }

            return sprintf('%s=%s', $key, $value);
        }, array_keys($attributes), array_values($attributes))));

        $this->sql['update'] = $update;

        $this->resetActions();

        $this->update = true;

        return $this;
    }

    /**
     * @param array $attributes
     * @return QueryBuilder
     * @throws \Exception
     */
    public function create(array $attributes): QueryBuilder
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        if (count($attributes) === 0) {
            throw new \Exception('При обновлении базы передан пустой массив аттрибутов');
        }

        if (!is_null($this->model)) {
            $attributes = $this->model->fromFillable($attributes);
        }

        $keys = implode(', ', array_keys($attributes));

        $values = implode(', ', array_map(function ($value) {
            if (is_bool($value)) {
                return intval($value);
            } elseif (is_null($value)) {
                return 'NULL';
            } else {
                return $this->escape($value);
            }
        }, array_values($attributes)));

        $create = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->sql['table'], $keys, $values);

        $this->sql['insert'] = $create;

        $this->resetActions();

        $this->insert = true;

        return $this;
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    public function delete(): QueryBuilder
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        $delete = sprintf('DELETE FROM %s', $this->sql['table']);

        $this->sql['delete'] = $delete;

        $this->resetActions();

        $this->delete = true;

        return $this;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function count(): int
    {
        if (!$this->sql['table']) {
            throw new \Exception('Сперва нужно объявить таблицу');
        }

        $this->reset(['delete', 'update', 'insert']);

        $this->resetActions();

        $this->select = true;

        $this->select('COUNT(*)');

        $sql = $this->compile();

        return (int) DB::getInstance()->setQuery($this)->query($sql, true);
    }

    /**
     * @param Model $model
     * @return QueryBuilder
     */
    public function setModel(Model $model): QueryBuilder
    {
        $this->model = $model;

        return $this->table($this->model->getTable());
    }

    /**
     * @return mixed
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * @param array|null $keys
     * @return QueryBuilder
     */
    protected function reset(array $keys = null): QueryBuilder
    {
        if (!is_null($keys)) {

            if (!is_array($keys)) $keys = (array) $keys;

            foreach ($keys as $key) {
                if ($key === 'relations') {
                    $this->relations = [];
                } elseif (strpos($key, 'action_') !== false) {
                    $key = str_replace('action_', '', $key);

                    if (isset($this->{$key})) {
                        $this->{$key} = false;
                    }
                } elseif (isset($this->sql[$key])) {
                    unset($this->sql[$key]);
                }
            }
        } else {
            $this->sql = [
                'select' => [],
                'update' => [],
                'insert' => [],
                'table'  => '',
                'joins'  => [],
                'where'  => [],
                'limit'  => '',
                'order'  => '',
                'delete' => ''
            ];

            $this->resetActions();
            $this->relations = [];
        }

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    protected function resetActions(): QueryBuilder
    {
        $this->select = false;
        $this->insert = false;
        $this->delete = false;
        $this->update = false;

        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    protected function escape($value): string
    {
        return DB::escape($value);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function compile(): string
    {
        /**
         * @var string $sql
         */
        $sql = '';

        if ($this->model && !$this->getTable()) {
            $this->table($this->model->getTable());
        }

        if ($this->select) {
            $select = $this->sql['select'];

            $table = $this->sql['table'];

            if (count($this->sql['joins']) > 0) {
                $joins = implode("\n", array_values($this->sql['joins']));
            } else {
                $joins = '';
            }

            if (count($this->sql['where']) > 0) {
                $where = sprintf('WHERE %s', implode(" ", array_values($this->sql['where'])));
            } else {
                $where = '';
            }

            if (isset($this->sql['order_by']) && $this->sql['order_by'] !== '') {
                $order_by = $this->sql['order_by'];
            }

            if (isset($this->sql['order_how'])) {
                $order_how = $this->sql['order_how'];
            } elseif (isset($this->sql['order_by']) && !isset($this->sql['order_how'])) {
                $order_how = 'ASC';
            }

            if (isset($order_by) && isset($order_how)) {
                $order = sprintf('ORDER BY %s %s', $order_by, $order_how);
            } else {
                $order = '';
            }

            if ($this->sql['limit']) {
                $limit = $this->sql['limit'];
            } else {
                $limit = '';
            }

            $sql = sprintf(
                "SELECT %s %s FROM %s %s %s %s %s", $this->distinct ? 'DISTINCT' : '', $select, $table, $joins, $where, $order, $limit
            );
        } elseif ($this->insert) {
            /**
             * @var string $insert
             */
            $insert = $this->sql['insert'];

            $sql = $insert;
        } elseif ($this->update) {
            if (count($this->sql['where']) === 0) {
                throw new \Exception('Для обновления таблицы должно быть объявлено минимум одно условие where');
            }
            /**
             * @var string $update
             */
            $update = $this->sql['update'];

            if (count($this->sql['joins']) > 0) {
                $joins = implode("\n", array_values($this->sql['joins']));
            } else {
                $joins = '';
            }

            if (count($this->sql['where']) > 0) {
                $where = sprintf('WHERE %s', implode(" ", array_values($this->sql['where'])));
            } else {
                $where = '';
            }

            $sql = sprintf('%s %s %s', $update, $joins, $where);
        } elseif ($this->delete) {
            if (count($this->sql['where']) === 0) {
                throw new \Exception('Для удаления из таблицы должно быть объявлено минимум одно условие where');
            }

            $delete = $this->sql['delete'];

            if (count($this->sql['joins']) > 0) {
                $joins = implode("\n", array_values($this->sql['joins']));
            } else {
                $joins = '';
            }

            if (count($this->sql['where']) > 0) {
                $where = sprintf('WHERE %s', implode(" ", array_values($this->sql['where'])));
            } else {
                $where = '';
            }

            $sql = sprintf('%s %s %s', $delete, $joins, $where);
        }

        return preg_replace('/\s+/', ' ', trim($sql));
    }

    /**
     * @param int $count
     * @return $this
     */
    public function random(int $count = 1)
    {
        $this->sql['order_by'] = 'RAND()';
        $this->limit($count);

        return $this;
    }

    /**
     * @param $id
     * @return Model|null
     * @throws \Exception
     */
    public function find($id)
    {
        if (is_array($id)) $id = collect($id)->first();
        if (!$this->model) throw new \Exception('Отсутствует модель для поиска сущности');
        return $this->model::find($id);
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        if ($this->select) {
            /**
             * @var Collection $result
             */
            $result = DB::getInstance()->setQuery($this)->query();

            if ($this->relations) {
                $result = $this->loadRelations($result->all());
            }

            $this->reset();

            return collect($result);
        } elseif ($this->allActionsIsFalse()) {
            $this->select();

            return $this->get();
        }

        return collect([]);
    }

    public function first()
    {
        if ($this->select) {
            /**
             * @var Collection $result
             */
            $result = DB::getInstance()->setQuery($this)->query();

            if ($this->relations) {
                $result = $this->loadRelations([$result->first()]);
            }

            $this->reset();

            return is_array($result) ? current($result) : $result->first();
        } elseif ($this->allActionsIsFalse()) {
            $this->select();

            return $this->first();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if ($this->update) {
            DB::getInstance()->setQuery($this)->query();

            $this->reset();

            return true;
        }

        return false;
    }

    public function execute()
    {
        if ($this->select) {
            return $this->get();
        } elseif ($this->update) {
            return $this->save();
        } elseif ($this->insert) {
            DB::getInstance()->setQuery($this)->query();

            $model = $this->getModel();

            $this->reset();

            return !is_null($model) ? $this->setModel($model)->where([$model->getPrimary() => DB::lastId()])->first() : DB::lastId();
        } elseif ($this->delete) {
            DB::getInstance()->setQuery($this)->query();

            $this->reset();

            return true;
        }
    }

    /**
     * @return bool
     */
    public function isSelect(): bool
    {
        return $this->select;
    }
    /**
     * @return bool
     */
    public function isInsert(): bool
    {
        return $this->insert;
    }
    /**
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->update;
    }
    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->delete;
    }

    /**
     * @return bool
     */
    public function isExists(): bool
    {
        return $this->exists;
    }

    /**
     * @return bool
     */
    public function allActionsIsFalse(): bool
    {
        return !$this->select && !$this->delete && !$this->delete && !$this->update;
    }

    /**
     * @param bool $distinct
     * @return $this
     */
    public function distinct(bool $distinct = true)
    {
        $this->distinct = $distinct;

        return $this;
    }

    public function __toString(): string
    {
        return $this->compile();
    }

}