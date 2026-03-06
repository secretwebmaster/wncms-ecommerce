<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wncms\Models\BaseModel;

class OrderItem extends BaseModel
{
    use HasFactory;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'order_item';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'attributes' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-boxes-stacked',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }

    public function order_itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeAttribute(): string
    {
        return $this->order_itemable?->priceable?->type
            ?? $this->order_itemable?->type_label
            ?? '';
    }

    public function getNameAttribute(): string
    {
        return $this->attributes['name']
            ?? $this->order_itemable?->priceable?->name
            ?? $this->order_itemable?->name
            ?? '';
    }
}
