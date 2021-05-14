<?php

namespace App\System\Database\Relations;

use App\Models\Model;
use App\System\Collection;
use App\System\Database\DB;
use App\System\Database\QueryBuilder;

class HasMany extends Relation
{
    protected $relatedModelClass;

    protected $relations = [];

    protected $foreignKey;

    protected $ownerKey;

    public function __construct($model, string $relatedModel, string $foreignKey = null, string $ownerKey = null)
    {
        $this->model = $model;
        $this->relatedModel($relatedModel);
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;

        $this->setKeys();

        $this->query = $this->getQuery();
    }

    /**
     * @return Collection|null
     * @throws \Exception
     */
    public function getResults(): ?Collection
    {
        return !is_null($this->model->getData($this->ownerKey)) ? $this->query->get() : collect([]);
    }

    /**
     * @param string $class
     * @return $this
     */
    protected function relatedModel(string $class)
    {
        $class = str_replace('/', '\\', $class);
        $this->relatedModelClass = $class;
        $this->relatedModel = new $class();

        return $this;
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $parent
     * @return QueryBuilder|void
     * @throws \Exception
     */
    public function getRelationCountQuery(QueryBuilder $query, QueryBuilder $parent)
    {
        if ($parent->getTable() === $query->getTable()) {
            return $this->getRelationCountQueryForSelfRelation($query, $parent);
        }

        return parent::getRelationCountQuery($query, $parent);
    }

    /**
     * @param QueryBuilder $query
     * @param QueryBuilder $parent
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getRelationCountQueryForSelfRelation(QueryBuilder $query, QueryBuilder $parent)
    {
        $query->select('count(*)');

        $query->table($query->getModel()->getTable() . ' as ' . ($hash = $this->getRelationCountHash()));

        $query->getModel()->setTable($hash);

        $key = $this->model->getTable() . '.' . $this->ownerKey;

        return $query->where([$hash . '.' . $this->foreignKey => $key]);
    }

    protected function setKeys()
    {
        if (is_null($this->foreignKey)) {
            $this->foreignKey = $this->model->getForeignKey();
        }

        if (is_null($this->ownerKey)) {
            $this->ownerKey = $this->model->getPrimary();
        }
    }

    /**
     * @param bool $condition
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getQuery(bool $condition = false): QueryBuilder
    {
        return DB::getInstance()
            ->setQuery(new QueryBuilder())
            ->getQuery()
            ->setModel($this->relatedModel)
            ->where([$this->relatedModel->getTable() . '.' . $this->foreignKey => $this->model->{$this->ownerKey}]);
    }

    /**
     * @param array $models
     * @return $this
     * @throws \Exception
     */
    public function addRelationConstraints(array $models)
    {
        $this->query->whereIn($this->foreignKey, $this->getKeys($models, $this->ownerKey));

        return $this;
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
         * @var string $foreign
         */
        $foreign = $this->foreignKey;
        /**
         * @var array $dictionary
         */
        $dictionary = [];

        foreach ($results as $result) {
            $key = $result->{$foreign};

            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $result;
        }

        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getData($this->ownerKey)])) {
                $model->setRelation(
                    $relationName, $dictionary[$key]
                );
            }
        }

        return $models;
    }
}