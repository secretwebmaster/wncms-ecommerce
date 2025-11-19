<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Subscription extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'subscription';

    protected $guarded = [];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-arrows-spin'
    ];

    public const STATUSES = [
        'active',
        'expired',
        'cancelled',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function plan()
    {
        return $this->belongsTo(wncms()->getModelClass('plan'));
    }

    public function price()
    {
        return $this->belongsTo(wncms()->getModelClass('price'));
    }
}
