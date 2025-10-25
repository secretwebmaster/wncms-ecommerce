<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Wncms\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends BaseModel
{
    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-ticket'
    ];

    public function isAvailable()
    {
        dd('isAvailable logic');
    }
}
