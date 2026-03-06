<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wncms\Models\BaseModel;

class Price extends BaseModel
{
    use HasFactory;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'price';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'attributes' => 'array',
        'is_lifetime' => 'boolean',
    ];

    public const DURATION_UNITS = [
        'day',
        'week',
        'month',
        'year',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeLifetime($query)
    {
        return $query->where('is_lifetime', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_lifetime', false);
    }
}
