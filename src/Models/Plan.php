<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Plan extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'plan';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-check'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function subscriptions()
    {
        return $this->hasMany(wncms()->getModelClass('subscription'));
    }

    public function prices()
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Methods
     * ----------------------------------------------------------------------------------------------------
     */
    
    /**
     * Get the lifetime price for the plan.
     */
    public function getLifetimePrice()
    {
        return $this->prices()->lifetime()->first();
    }

    /**
     * Get the price for a specific duration.
     */
    public function getPriceForDuration(int $duration)
    {
        return $this->prices()->regular()->where('duration', $duration)->first();
    }

    /**
     * Get the latest active subscription for the plan.
     */
    public function getActiveSubscriptionAttribute()
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }

    public function getTypeAttribute(): string
    {
        return __('wncms-ecommerce::word.plan'); // 方案
    }
}
