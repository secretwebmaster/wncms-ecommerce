<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class OrderItem extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'order_item';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-boxes-stacked'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function order()
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }

    public function order_itemable()
    {
        return $this->morphTo();
    }

    /**
     * Translated model type label.
     */
    public function getTypeAttribute(): string
    {
        return $this->order_itemable?->priceable?->type
            ?? $this->order_itemable?->type_label
            ?? '';
    }

    /**
     * Clean display name for showing in frontend/backend.
     *
     * - If this item is a Price, show its parent model’s name (Plan/Product/etc.)
     * - Otherwise, show its own name
     * - Fallbacks to class name if no name field
     */
    public function getNameAttribute(): string
    {
        return $this->order_itemable?->priceable?->name
            ?? $this->order_itemable?->name
            ?? '';
    }
}
