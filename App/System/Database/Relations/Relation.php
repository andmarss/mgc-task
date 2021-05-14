<?php

namespace App\System\Database\Relations;

use App\Models\Model;
use App\System\Collection;
use App\System\Database\DB;
use App\System\Database\Expression;
use App\System\Database\QueryBuilder;
use App\Traits\CallToTrait;

abstract class Relation
{
    use CallToTrait;
    /**
     * @var Model $model
     */
    protected $model;
    /**
     * @var Model|null $relatedModel
     */
    protected $relatedModel;
    /**
     * @var QueryBuilder $query
     */
    public $query;

    abstract public function getResults();

    abstract protected function relatedModel(string $class);

    abstract protected function setKeys();

    abstract public function getQuery(bool $condition = false): QueryBuilder;

    abstract public function addRelationConstraints(array $models);

    abstract public function match(array $models, Collection $results, $relationName);

    public function getRelated(): ?Model
    {
        return $this->relatedModel;
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $parent
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getRelationCountQuery(QueryBuilder $query, QueryBuilder $parent)
    {
        $query->select('count(*)');

        $key = $this->model->getQualifiedKeyName();

        return $query->where([$this->getForeignKeyName() => new Expression($key)]);
    }

    /**
     * @return string|null
     */
    public function getForeignKeyName(): ?string
    {
        return isset($this->foreignKey) ? $this->foreignKey : null;
    }

    /**
     * @return string
     */
    public function getRelationCountHash(): string
    {
        return 'self_' . md5(microtime(true));
    }

    public function __call($method, $arguments)
    {
        if (!$this->query) $this->query = $this->getQuery();

        if (method_exists($this, $method)) {
            return $this->$method(...$arguments);
        } elseif (method_exists($this->query, $method)) {

            $result = $this->callTo($this->query, $method, $arguments);

            if ($result === $this->query) {
                return $this;
            }

            return $result;
        } else {
            throw new \Exception('Метод не объявлен');
        }
    }

    /**
     * @param array $models
     * @param null $key
     * @return array
     */
    protected function getKeys(array $models, $key = null)
    {
        return collect($models)->map(function ($value) use ($key) {
            return $key ? $value->getData($key) : $value->getKey();
        })->values()->unique(null, true)->sort()->all();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->query) $this->query = $this->getQuery();

        return strval($this->query);
    }
}