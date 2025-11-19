<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Discount extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'discount';

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-comments-dollar'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public const TYPES = [
        'percentage',
        'fixed',
    ];
}
