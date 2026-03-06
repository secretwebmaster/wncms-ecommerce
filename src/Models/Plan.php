<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Wncms\Translatable\Traits\HasTranslations;
use Wncms\Models\BaseModel;

class Plan extends BaseModel
{
    use HasFactory;
    use HasTranslations;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'plan';

    protected $guarded = [];
    protected $translatable = ['name', 'description'];

    protected $casts = [
        'is_recurring' => 'boolean',
        'price_amount' => 'decimal:2',
        'setup_fee_amount' => 'decimal:2',
        'attributes' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-check',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public const INTERVAL_UNITS = [
        'day',
        'week',
        'month',
        'year',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(wncms()->getModelClass('subscription'));
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }

    public function getLifetimePrice()
    {
        return $this->prices()->lifetime()->first();
    }

    public function getPriceForDuration(int $duration)
    {
        return $this->prices()->regular()->where('duration', $duration)->first();
    }

    public function getActiveSubscriptionAttribute()
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }

    public function getTypeAttribute(): string
    {
        return __('wncms-ecommerce::word.plan');
    }
}
