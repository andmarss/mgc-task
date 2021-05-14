<?php

namespace App\Models;
use App\Pivots\ParameterPivot;
use App\System\Collection;
use App\System\Database\QueryBuilder;
use App\System\Database\Relations\BelongsTo;
use App\System\Database\Relations\BelongsToMany;
use App\System\Database\Relations\HasMany;
use App\System\Database\Relations\HasOne;

/**
 * Class Category
 * @package App\Models
 *
 * @property int $category_id
 * @property string $name
 * @property int $mgc_id
 * @property int|null $parent_id
 *
 * @property HasMany|Collection $products
 * @property BelongsToMany|Collection $parameters
 * @property HasMany|Collection $children
 * @property HasOne|Category $parent
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'name',
        'parent_id',
        'mgc_id'
    ];

    protected $with = ['children'];

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id')
            ->where(['products.available' => true])
            ->where('products.in_stock', '>', 3);
    }

    /**
     * @return BelongsToMany
     */
    public function parameters(): BelongsToMany
    {
        return $this->belongsToMany(Parameter::class, 'category_parameter', 'category_id', 'parameter_id')->withPivot([
            'value', 'array_value'
        ]);
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'category_id');
    }

    /**
     * @return HasOne
     */
    public function parent(): HasOne
    {
        return $this->hasOne(Category::class, 'category_id', 'parent_id');
    }

    /**
     * @param array $breadcrumbs
     * @return array|null
     * @throws \Exception
     */
    public function breadcrumbs(array &$breadcrumbs = []): array
    {
        if (!$breadcrumbs) $breadcrumbs[] = [
            'name' => 'Главная страница',
            'url' => route('index')
        ];

        if (is_null($this->parent_id)) {
            $breadcrumbs[] = [
                'name' => $this->name,
                'url' => route('category', ['id' => $this->category_id])
            ];

            return $breadcrumbs;
        }

        $breadcrumbs = $this->parent->breadcrumbs($breadcrumbs);

        $breadcrumbs[] = [
            'name' => $this->name,
            'url'  =>  route('category', ['id' => $this->category_id])
        ];

        return $breadcrumbs;
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