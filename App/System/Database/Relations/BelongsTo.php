<?php

namespace App\System\Database\Relations;

use App\Models\Model;
use App\System\Collection;
use App\System\Database\DB;
use App\System\Database\QueryBuilder;

class BelongsTo extends Relation
{
    /**
     * @var Model $model
     */
    protected $model;
    /**
     * @var Model|null $relatedModel
     */
    protected $relatedModel;
    /**
     * @var string $relatedModelClass
     */
    protected $relatedModelClass;
    /**
     * @var array $relations
     */
    protected $relations = [];
    /**
     * @var string $foreignKey
     */
    protected $foreignKey;
    /**
     * @var string $ownerKey
     */
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
     * @return Model|null
     * @throws \Exception
     */
    public function getResults(): ?Model
    {
        return !is_null($this->model->getData($this->foreignKey)) ? $this->query->first() : null;
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

    protected function setKeys()
    {
        if (is_null($this->foreignKey)) {
            $this->foreignKey = $this->relatedModel->getPrimary();
        }

        if (is_null($this->ownerKey)) {
            $this->ownerKey = $this->relatedModel->getForeignKey();
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
            ->newQuery()
            ->getQuery()
            ->setModel($this->relatedModel)
            ->where([$this->relatedModel->getTable() . '.' . $this->ownerKey => $this->model->{$this->foreignKey}]);
    }

    public function addRelationConstraints(array $models)
    {
        $key = $this->relatedModel->getTable() . '.' . $this->ownerKey;

        $this->query->whereIn($key, $this->getKeys($models));
    }

    /**
     * @param array $models
     * @param null $key
     * @return array
     */
    public function getKeys(array $models, $key = null)
    {
        $keys = [];

        foreach ($models as $model) {
            if (!is_null($key = $model->{$this->foreignKey})) {
                $keys = $key;
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
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
         * @var string $owner
         */
        $owner = $this->ownerKey;

        /**
         * @var array $dictionary
         */
        $dictionary = [];

        foreach ($results as $result) {
            $key = $result->getData($owner);

            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $result;
        }

        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$foreign}])) {
                $model->setRelation(
                    $relationName, $dictionary[$key]
                );
            }
        }

        return $models;
    }
}