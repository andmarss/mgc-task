<?php

namespace App\System\Database\Relations;

use App\Models\Model;
use App\Models\Product;
use App\System\Collection;
use App\System\Database\DB;
use App\System\Database\Expression;
use App\System\Database\QueryBuilder;

class BelongsToMany extends Relation
{
    /**
     * @var string $table
     */
    protected $table;
    /**
     * @var string $foreignPivotKey
     */
    protected $foreignPivotKey;
    /**
     * @var string $relatedPivotKey
     */
    protected $relatedPivotKey;
    /**
     * @var string $parentKey
     */
    protected $parentKey;
    /**
     * @var string $relatedKey
     */
    protected $relatedKey;

    /**
     * @var Model $model
     */
    protected $model;
    /**
     * @var Model|null $relatedModel
     */
    protected $relatedModel;
    /**
     * @var array $pivotWheres
     */
    protected $pivotWheres = [];
    /**
     * @var array $pivotColumns
     */
    protected $pivotColumns = [];
    /**
     * @var Pivot $using
     */
    protected $using;

    public function __construct($model, string $class, string $table = null, string $foreignPivotKey = null, string $relatedPivotKey = null, string $parentKey = null, string $relatedKey = null)
    {
        $this->model = $model;
        $this->relatedModel($class);
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;

        if (is_null($this->table)) {
            $this->setTable();
        }

        $this->setKeys();
    }

    /**
     * @return \App\System\Collection
     * @throws \Exception
     */
    public function getResults()
    {
        if (is_null($this->model->getData($this->parentKey))) return collect([]);

        if (!$this->query) $this->query = $this->getQuery();

        /**
         * @var QueryBuilder $builder
         */
        $builder =  $this->query;

        /**
         * @var Collection $collection
         */
        $collection = $builder->get();

        if ($this->pivotColumns) {

            $collection = $collection->map(function (Model $relatedInstance) {
                $pivot = $relatedInstance->newPivot(
                    $this->cleanPivotAttributes($relatedInstance), $this->table
                );

                $relatedInstance->setRelation('pivot', $pivot);

                return $relatedInstance;
            });
        }

        return $collection;
    }

    /**
     * @return QueryBuilder
     */
    public function getBasicQuery(): QueryBuilder
    {
        $builder = DB::getInstance()
            ->newQuery()
            ->getQuery();

        if ($this->relatedModel instanceof Model) {
            return $builder->setModel($this->relatedModel);
        } else {
            return $builder->table($this->table);
        }
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getResultsQuery(): QueryBuilder
    {
        $relatedTable = $this->relatedModel->getTable();

        $builder =  $this->getBasicQuery()
            ->setModel($this->relatedModel)
            ->select($this->getSelectColumns())
            ->join($this->table, $this->table . '.' . $this->relatedPivotKey, '=', $relatedTable . '.' . $this->relatedModel->getPrimary())
            ->where([$this->table . '.' . $this->foreignPivotKey => $this->model->{$this->model->getPrimary()}]);

        if ($this->pivotWheres) {
            foreach ($this->pivotWheres as $arguments) {

                if (count($arguments) === 3) {
                    [$field, $operator, $value] = $arguments;

                    $builder->where($this->table . '.' . $field, $operator, $value);
                } elseif (is_array($arguments) && count($arguments) > 0) {
                    $table = $this->table;

                    $arguments = array_merge(...array_map(function ($key, $value) use ($table){
                        return [$table . '.' . $key => $value];
                    }, array_keys($arguments), array_values($arguments)));

                    $builder->where($arguments);
                }
            }
        }

        return $builder;
    }

    /**
     * @param string $class
     * @return $this
     */
    protected function relatedModel(string $class)
    {
        $class = str_replace('/', '\\', $class);
        $this->relatedModel = new $class();

        return $this;
    }

    /**
     * @param null $ids
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    public function attach($ids = null, array $attributes = [])
    {
        if ($ids instanceof Model) $ids = (array) $ids->getKey();

        $results = [];

        if (!is_null($ids)) {

            if (!is_array($ids)) {
                $ids = (array) $ids;
            }

            foreach ($this->createAttachRecords($ids, $attributes) as $attachRecord) {
                $results[] = $this->model->newPivot($attachRecord, $this->table)->save();
            }
        }

        return $results;
    }

    /**
     * @param array $ids
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    protected function createAttachRecords(array $ids, array $attributes = [])
    {
        $records = [];

        foreach ($ids as $key => $value) {
            $records[] = $this->createAttachRecord($key, $value, $attributes);
        }

        return $records;
    }

    /**
     * @param $key
     * @param $value
     * @param $attributes
     * @return array
     * @throws \Exception
     */
    protected function createAttachRecord($key, $value, $attributes)
    {
        $record = [];

        [$id, $data] = is_array($value) ? [$key, array_merge($value, $attributes)] : [$value, $attributes];

        $record[$this->foreignPivotKey] = $this->model->getKey();
        $record[$this->relatedPivotKey] = $id;

        return array_merge($record, $data);
    }

    /**
     * @param null $ids
     * @param array $attributes
     * @return $this
     * @throws \Exception
     */
    public function detach($ids = null)
    {
        if (!is_null($ids)) {

            if (!is_array($ids)) {
                $ids = (array) $ids;
            }

            $query = $this->getPivotQuery();

            if ($ids) {
                $query->whereIn($this->relatedPivotKey, $ids);

                $query->delete()->execute();
            }
        } elseif (is_null($ids)) { // значит, нужно удалить все связи текущей модели со связующей таблицей
            $query = $this->getPivotQuery();

            if ($query->count() > 0) $query->delete()->execute();
        }

        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function wherePivot($column, $operator = null, $value = null)
    {
        $this->pivotWheres[] = func_get_args();

        return $this;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function withPivot($columns)
    {
        $this->pivotColumns = array_merge(
            $this->pivotColumns, is_array($columns) ? $columns : func_get_args()
        );

        return $this;
    }

    protected function setTable()
    {
        /**
         * @var array $tables
         */
        $tables = [$this->model->getTable(), $this->relatedModel->getTable()];

        sort($tables);

        if (is_null($this->table)) {
            $this->table = implode('_', $tables);
        }
    }

    /**
     * @param Model $pivot
     * @return array
     */
    protected function cleanPivotAttributes(Model $pivot): array
    {
        $values = [];

        foreach ($pivot->getAllData() as $key => $value) {
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($pivot->$key);
            }
        }

        return $values;
    }

    /**
     * @param array $columns
     * @return array
     */
    protected function getSelectColumns(array $columns = []): array
    {
        if (count($columns) === 0) {
            $columns = array_merge([$this->relatedModel->getTable() . '.*']);
        }

        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey];

        if ($this->pivotColumns) {
            foreach (array_merge($defaults, $this->pivotColumns) as $column) {
                $columns[] = sprintf('%s.%s as pivot_%s', $this->table, $column, $column);
            }
        }

        return array_unique($columns);
    }

    public function getPivotQuery()
    {
        $query = $this->getBasicQuery()->table($this->table);

        foreach ($this->pivotWheres as $arguments) {
            if (count($arguments) === 3) {
                [$field, $operator, $value] = $arguments;

                $query->where($this->table . '.' . $field, $operator, $value);
            } elseif (is_array($arguments) && count($arguments) > 0) {
                $table = $this->table;

                $arguments = array_merge(...array_map(function ($key, $value) use ($table){
                    return [$table . '.' . $key => $value];
                }, array_keys($arguments), array_values($arguments)));

                $query->where($arguments);
            }
        }

        return $query->where([$this->foreignPivotKey => $this->model->{$this->model->getKeyName()}]);
    }

    protected function setKeys()
    {
        if (is_null($this->foreignPivotKey)) {
            $this->foreignPivotKey = $this->model->getForeignKey();
        }

        if (is_null($this->relatedPivotKey)) {
            $this->relatedPivotKey = $this->relatedModel->getForeignKey();
        }
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $parent
     * @return QueryBuilder|void
     * @throws \Exception
     */
    public function getRelationCountQuery(QueryBuilder $query, QueryBuilder $parent)
    {
        if ($query->getTable() === $parent->getTable()) {
            return $this->getRelationCountQueryForSelfJoin($query, $parent);
        }

        $query = $this->setJoin($query);

        return parent::getRelationCountQuery($query, $parent);
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $parent
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getRelationCountQueryForSelfJoin(QueryBuilder $query, QueryBuilder $parent)
    {
        $query->select('count(*)');

        $query->table($this->relatedModel->getTable() . ' as ' . ($hash = $this->getRelationCountHash()));

        $this->relatedModel->setTable($hash);

        $query = $this->setJoin($query);

        return parent::getRelationCountQuery($query, $parent);
    }

    /**
     * @param QueryBuilder|null $query
     * @return QueryBuilder
     */
    protected function setJoin(QueryBuilder $query = null): QueryBuilder
    {
        $query = $query ?: new QueryBuilder();

        $baseTable = $this->relatedModel->getTable();

        $key = $baseTable . '.' . $this->relatedModel->getKeyName();

        $query->join($this->table, $key, '=', $this->table . '.' . $this->relatedPivotKey);

        return $query;
    }

    /**
     * @param bool $condition
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getQuery(bool $condition = false): QueryBuilder
    {
        return $condition ? $this->getBasicQuery() : $this->getResultsQuery();
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function where($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (func_num_args() === 3) {
            /**
             * @var string $field
             * @var string $operator
             * @var string $value
             */
            [$field, $operator, $value] = func_get_args();

            $field = $this->query->getTable() . '.' . $field;

            $this->query->where($field, $operator, $value);
        } elseif (is_array($conditions)) {

            $table = $this->query->getTable();

            $conditions = array_combine(
                array_map(function ($key) use($table) { return $table . '.' . $key; }, array_keys($conditions)),
                array_map(function ($value) { return $value;}, $conditions)
            );

            $this->query->where($conditions);
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
    public function whereIn(string $field, $value, $selectedField = null)
    {
        if (!$this->query) $this->query = $this->getQuery();

        $field = $this->query->getTable() . '.' . $field;

        $this->query->whereIn($field, $value, $selectedField);

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param string|null $selectedField
     * @return BelongsToMany
     * @throws \Exception
     */
    public function whereNotIn(string $field, $value, string $selectedField = null)
    {
        if (!$this->query) $this->query = $this->getQuery();

        $field = $this->query->getTable() . '.' . $field;

        $this->query->whereNotIn($field, $value, $selectedField);

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function orWhere($conditions = []): QueryBuilder
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (func_num_args() === 3) {
            /**
             * @var string $field
             * @var string $operator
             * @var string $value
             */
            [$field, $operator, $value] = func_get_args();
            /**
             * @var string $field
             */
            $field = $this->query->getTable() . '.' . $field;

            $this->query->orWhere($field, $operator, $value);
        } elseif (is_array($conditions) && count($conditions) > 0) {
            /**
             * @var string $table
             */
            $table = $this->query->getTable();

            $conditions = array_combine(
                array_map(function ($key) use($table) { return $table . '.' . $key; }, array_keys($conditions)),
                array_map(function ($value) { return $value;}, $conditions)
            );

            $this->query->orWhere($conditions);
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function whereLike($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (func_num_args() === 2) {
            /**
             * @var string $field
             * @var string $operator
             * @var string $value
             */
            [$field, $value] = func_get_args();
            /**
             * @var string $field
             */
            $field = $this->query->getTable() . '.' . $field;

            $this->query->whereLike($field, $value);
        } elseif (is_array($conditions) && count($conditions) > 0) {
            /**
             * @var string $table
             */
            $table = $this->query->getTable();

            $conditions = array_combine(
                array_map(function ($key) use($table) { return $table . '.' . $key; }, array_keys($conditions)),
                array_map(function ($value) { return $value;}, $conditions)
            );

            $this->query->whereLike($conditions);
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function orWhereLike($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();
        /**
         * @var bool $condition
         */
        $condition = collect(array_keys($conditions))->every(function ($key) {
            return is_string($key);
        });

        if (is_array($conditions) && $condition && count($conditions) > 0) {
            /**
             * @var string $table
             */
            $table = $this->query->getTable();

            $conditions = array_combine(
                array_map(function ($key) use($table) { return $table . '.' . $key; }, array_keys($conditions)),
                array_map(function ($value) { return $value;}, $conditions)
            );

            $this->query->orWhereLike($conditions);

        } elseif (func_num_args() === 2) {
            /**
             * @var string $field
             * @var string $value
             */
            [$field, $value] = func_get_args();
            /**
             * @var string $field
             */
            $field = $this->query->getTable() . '.' . $field;

            $this->query->orWhereLike($field, $value);
        }

        return $this;
    }

    /**
     * @param array $conditions
     * @return BelongsToMany
     * @throws \Exception
     */
    public function whereNull($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $table = $this->query->getTable();

        $conditions = array_map(function ($column) use ($table) {
            return sprintf('%s.%s', $table, $column);
        }, $conditions);

        $this->query->whereNull($conditions);

        return $this;
    }

    /**
     * @param array $conditions
     * @return BelongsToMany
     * @throws \Exception
     */
    public function orWhereNull($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $table = $this->query->getTable();

        $conditions = array_map(function ($column) use ($table) {
            return sprintf('%s.%s', $table, $column);
        }, $conditions);

        $this->query->orWhereNull($conditions);

        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     * @throws \Exception
     */
    public function whereNotNull($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $table = $this->query->getTable();

        $conditions = array_map(function ($column) use ($table) {
            return sprintf('%s.%s', $table, $column);
        }, $conditions);

        $this->query->whereNotNull($conditions);

        return $this;
    }

    /**
     * @param array $conditions
     * @return BelongsToMany
     * @throws \Exception
     */
    public function orWhereNotNull($conditions = [])
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (!is_array($conditions)) {
            $conditions = (array) $conditions;
        }

        $table = $this->query->getTable();

        $conditions = array_map(function ($column) use ($table) {
            return sprintf('%s.%s', $table, $column);
        }, $conditions);

        $this->query->orWhereNotNull($conditions);

        return $this;
    }

    /**
     * @return string
     */
    public function getForeignKeyName(): string
    {
        return $this->table . '.' . $this->foreignPivotKey;
    }

    /**
     * @param array $models
     * @throws \Exception
     */
    public function addRelationConstraints(array $models)
    {
        $this->query->whereIn(
            $this->getForeignKeyName(),
            $this->getKeys($models)
        );
    }

    /**
     * @param array $models
     * @param Collection $results
     * @param $relationName
     * @return array
     */
    public function match(array $models, Collection $results, $relationName)
    {
        /**
         * @var array $dictionary
         */
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->pivot->{$this->foreignPivotKey}][] = $result;
        }

        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->parentKey}])) {
                $model->setRelation(
                    $relationName, $dictionary[$key]
                );
            }
        }

        return $models;
    }

    /**
     * @return array
     */
    public function getPivotColumns(): array
    {
        return $this->pivotColumns;
    }
}