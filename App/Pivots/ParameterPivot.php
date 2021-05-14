<?php

namespace App\Pivots;

use App\System\Database\Relations\Pivot;

/**
 * Class ParameterPivot
 * @package App\Pivots
 *
 * @property int $id
 * @property int $product_id
 * @property int $category_id
 * @property int $parameter_id
 * @property mixed $value
 * @property array $array_value
 */
class ParameterPivot extends Pivot
{
    protected $primaryKey = 'id';
    /**
     * @var array $fillable
     */
    protected $fillable = [
        'product_id',
        'category_id',
        'parameter_id',
        'value',
        'array_value'
    ];
    /**
     * @var array $casts
     */
    protected $casts = [
        'array_value' => 'array'
    ];

    public function __construct(array $attributes = [], ?string $table = '', bool $exists = false)
    {
        parent::__construct($attributes, $table, $exists, false);
    }

    /**
     * @return array
     */
    public function getValuesAttribute(): array
    {
        if (!$this->table) return [];
        if ($this->parameter_id && $this->product_id) {
            return static::select(['value', 'array_value'])->where(['parameter_id' => $this->parameter_id, 'product_id' => $this->product_id])->get()->all();
        } elseif ($this->parameter_id && $this->category_id) {
            return static::select(['value', 'array_value'])->where(['parameter_id' => $this->parameter_id, 'category_id' => $this->category_id])->get()->all();
        } else {
            return [];
        }
    }
}