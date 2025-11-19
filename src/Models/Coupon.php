<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Wncms\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends BaseModel
{
    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'coupon';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-ticket'
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Methods
     * ----------------------------------------------------------------------------------------------------
     */
    public function isAvailable()
    {
        dd('isAvailable logic');
    }
}
