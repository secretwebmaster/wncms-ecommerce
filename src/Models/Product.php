<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Product extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'product';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'variants' => 'array',
        'properties' => 'array',
    ];

    protected static array $tagMetas = [
        [
            'key'   => 'product_category',
            'short' => 'category',
            'route' => 'frontend.products.tag',
        ],
        [
            'key'   => 'product_tag',
            'short' => 'tag',
            'route' => 'frontend.products.tag',
        ],
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];
    
    public const TYPES = [
        'virtual',
        'physical',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function orderItems()
    {
        return $this->morphMany(wncms()->getModelClass('order_item'), 'item');
    }

    public function prices()
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }


    /**
     * ----------------------------------------------------------------------------------------------------
     * Accessors
     * ----------------------------------------------------------------------------------------------------
     */
    public function getTypeLabelAttribute(): string
    {
        return __('wncms-ecommerce::word.' . $this->type); // 商品
    }
}
