<?php

namespace App\Models;

use App\Pivots\ParameterPivot;
use App\System\Collection;
use App\System\Database\Relations\BelongsToMany;

/**
 * Class Parameter
 * @package App\Models
 *
 * @property int $parameter_id
 * @property string $name
 * @property int $mgc_id
 *
 * @property BelongsToMany|Collection $categories
 * @property BelongsToMany|Collection $products
 */
class Parameter extends Model
{
    protected $table = 'parameters';

    protected $primaryKey = 'parameter_id';

    protected $fillable = [
        'name',
        'mgc_id'
    ];

    /**
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_parameter', 'parameter_id', 'category_id')->withPivot([
            'value', 'array_value'
        ]);
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'parameter_product', 'parameter_id', 'product_id')->withPivot([
            'value', 'array_value'
        ]);
    }

    /**
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return ParameterPivot|\App\System\Database\Relations\Pivot
     * @throws \Exception
     */
    public function newPivot($attributes, string $table, bool $exists = false)
    {
        return new ParameterPivot($attributes, $table, $exists);
    }
}