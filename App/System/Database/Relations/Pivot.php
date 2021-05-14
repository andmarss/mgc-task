<?php

namespace App\System\Database\Relations;

use App\Models\Model;
use App\System\Database\DB;

class Pivot extends Model
{
    protected $foreignKey;

    protected $relatedPivotKey;

    /**
     * Pivot constructor.
     * @param $attributes
     * @param $table
     * @param bool $exists
     * @param bool $force
     * @throws \Exception
     */
    public function __construct(array $attributes = [], ?string $table = '', bool $exists = false, bool $force = true)
    {
        parent::__construct();

        $this->table = $table;
        $this->exists = $exists;

        if ($force) {
            $this->forceFill($attributes);
        } else {
            $this->fill($attributes);
        }
    }

    /**
     * @return \App\System\Database\QueryBuilder
     * @throws \Exception
     */
    public function delete()
    {
        return $this->exists ? $this->getDeleteQuery()->delete()->execute() : null;
    }

    /**
     * @return \App\System\Database\QueryBuilder
     * @throws \Exception
     */
    protected function getDeleteQuery()
    {
        $foreignId = $this->getData($this->foreignKey);

        return DB::table($this->table)
            ->where([$this->foreignKey => $foreignId])
            ->where([$this->relatedPivotKey => $this->getData($this->relatedPivotKey)]);
    }

    /**
     * @param $foreignKey
     * @return $this
     */
    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * @param $relatedKey
     * @return $this
     */
    public function setRelatedPivotKey($relatedKey)
    {
        $this->relatedPivotKey = $relatedKey;

        return $this;
    }
}