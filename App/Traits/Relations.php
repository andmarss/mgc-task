<?php

namespace App\Traits;

use App\Models\Model;
use App\System\Database\Relations\BelongsTo;
use App\System\Database\Relations\BelongsToMany;
use App\System\Database\Relations\HasMany;
use App\System\Database\Relations\HasOne;

trait Relations
{
    /**
     * @param string $class
     * @param null $foreignKey
     * @param null $ownerKey
     * @return HasOne
     */
    public function hasOne(string $class, $foreignKey = null, $ownerKey = null): HasOne
    {
        return new HasOne($this, $class, $foreignKey, $ownerKey);
    }

    /**
     * @param string $class
     * @param null $foreignKey
     * @param null $ownerKey
     * @return HasMany
     */
    public function hasMany(string $class, $foreignKey = null, $ownerKey = null): HasMany
    {
        return new HasMany($this, $class, $foreignKey, $ownerKey);
    }

    /**
     * @param string $class
     * @param null $foreignKey
     * @param null $ownerKey
     * @return BelongsTo
     */
    public function belongsTo(string $class, $foreignKey = null, $ownerKey = null): BelongsTo
    {
        return new BelongsTo($this, $class, $foreignKey, $ownerKey);
    }

    /**
     * @param string $class
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @return BelongsToMany
     */
    public function belongsToMany(string $class, string $table = null, string $foreignPivotKey = null, string $relatedPivotKey = null, string $parentKey = null, string $relatedKey = null): BelongsToMany
    {
        /**
         * @var Model $related
         */
        $related = new $class();

        return new BelongsToMany($this, $class, $table, $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(), $relatedKey ?: $related->getKeyName());
    }
}