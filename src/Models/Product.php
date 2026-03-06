<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Translatable\Traits\HasTranslations;
use Wncms\Models\BaseModel;

class Product extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'product';

    protected $guarded = [];
    protected $translatable = ['name'];

    protected $casts = [
        'price' => 'decimal:2',
        'variants' => 'array',
        'properties' => 'array',
        'attributes' => 'array',
    ];

    protected static array $tagMetas = [
        [
            'key' => 'product_category',
            'short' => 'category',
            'route' => 'frontend.products.tag',
        ],
        [
            'key' => 'product_tag',
            'short' => 'tag',
            'route' => 'frontend.products.tag',
        ],
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public const TYPES = [
        'virtual',
        'physical',
    ];

    public const SALE_TYPES = [
        'one_time',
        'recurring',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_thumbnail')->singleFile();
        $this->addMediaCollection('product_content');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(wncms()->getModelClass('order_item'), 'order_itemable');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }

    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('product_thumbnail')->first();
        if ($media) {
            return $media->getUrl();
        }

        return $this->external_thumbnail;
    }

    public function getTypeLabelAttribute(): string
    {
        return __('wncms-ecommerce::word.' . $this->type);
    }

    public function getProperty($key, $fallback = null)
    {
        $properties = collect($this->properties);

        return $properties->where('name', $key)->first()['value'] ?? $fallback;
    }
}
