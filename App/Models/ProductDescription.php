<?php

namespace App\Models;

use App\System\Database\Relations\BelongsTo;

/**
 * Class ProductDescription
 * @package App\Models
 *
 * @property int product_description_id
 * @property int product_id
 * @property string|null $picture
 * @property string|null $annotation
 * @property string|null $terms_conditions
 * @property string|null $activation_rules
 * @property string|null $terms_of_use
 */
class ProductDescription extends Model
{
    /**
     * @var string $table
     */
    protected $table = 'product_descriptions';
    /**
     * @var string $primaryKey
     */
    protected $primaryKey = 'product_description_id';
    /**
     * @var array $fillable
     */
    protected $fillable = [
        'product_id',
        'picture',
        'annotation',
        'terms_conditions',
        'activation_rules',
        'terms_of_use'
    ];

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * @param string|null $value
     * @return string|null
     */
    public function getTermsConditionAttribute(?string $value)
    {
        return $value ? html_entity_decode($value) : $value;
    }
}