<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Wncms\Translatable\Traits\HasTranslations;
use Wncms\Models\BaseModel;
class Coupon extends BaseModel
{
    use HasTranslations;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'coupon';

    protected $guarded = [];
    protected $translatable = ['name'];

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
        if (($this->status ?? 'active') !== 'active') {
            return false;
        }

        if (!empty($this->expired_at) && now()->gt($this->expired_at)) {
            return false;
        }

        if (!empty($this->used_limit) && !empty($this->used_count) && $this->used_count >= $this->used_limit) {
            return false;
        }

        return true;
    }
}
