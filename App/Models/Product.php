<?php

namespace App\Models;

use App\Pivots\ParameterPivot;
use App\System\Collection;
use App\System\Database\Relations\BelongsTo;
use App\System\Database\Relations\BelongsToMany;
use App\System\Database\Relations\HasOne;

/**
 * Class Product
 * @package App\Models
 *
 * @property int $product_id
 * @property string $name
 * @property string $model
 * @property string $vendor
 * @property string $prefix
 * @property string $vendor_code
 * @property int|null $in_stock
 * @property bool $available
 * @property bool $downloadable
 * @property float $price
 * @property float $discount
 * @property int $category_id
 * @property int $mgc_id
 *
 * @property ProductDescription|HasOne $description
 * @property Category|BelongsTo|null $category
 * @property Collection $duplicates
 * @property BelongsToMany $parameters
 */
class Product extends Model
{
    /**
     * @var string $table
     */
    protected $table = 'products';
    /**
     * @var string $primaryKey
     */
    protected $primaryKey = 'product_id';
    /**
     * @var array $fillable
     */
    protected $fillable = [
        'name',
        'vendor',
        'brand',
        'prefix',
        'vendor_code',
        'in_stock',
        'available',
        'downloadable',
        'price',
        'discount',
        'category_id',
        'mgc_id'
    ];
    /**
     * @var array $casts
     */
    protected $casts = [
        'available' => 'bool',
        'downloadable' => 'bool',
        'in_stock' => 'int',
        'price' => 'float',
        'discount' => 'float'
    ];

    protected $with = ['description'];

    const PRODUCT_PREFIX_CERTIFICATE = 'Сертификат';

    /**
     * @return HasOne
     */
    public function description(): HasOne
    {
        return $this->hasOne(ProductDescription::class, 'product_id', 'product_id');
    }

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    /**
     * @return BelongsToMany
     */
    public function parameters(): BelongsToMany
    {
        return $this->belongsToMany(Parameter::class, 'parameter_product', 'product_id', 'parameter_id')->withPivot([
            'value', 'array_value'
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function breadcrumbs(): array
    {
        /**
         * @var array $breadcrumbs
         */
        $breadcrumbs[] = [
            'name' => 'Главная страница',
            'url' => route('index')
        ];

        if (!$this->category_id) {
            $breadcrumbs[] = [
                'name' => $this->name,
                'url' => route('product', ['id' => $this->product_id])
            ];

            return $breadcrumbs;
        }
        /**
         * @var array $breadcrumbs
         */
        $breadcrumbs = $this->category->breadcrumbs($breadcrumbs);

        $breadcrumbs[] = [
            'name' => $this->name,
            'url' => route('product', ['id' => $this->product_id])
        ];

        return $breadcrumbs;
    }

    /**
     * @return Collection
     */
    public function getDuplicatesAttribute(): ?Collection
    {
        return static::where([
            'prefix' => $this->prefix,
            'vendor' => $this->vendor,
            'name' => $this->name,
            'category_id' => $this->category_id
        ])->where('product_id', '!=', $this->product_id)->get();
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