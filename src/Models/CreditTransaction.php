<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class CreditTransaction extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-bill-transfer'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const TRANSACTION_TYPES = [
        'earn',
        'spend',
        'recharge',
        'refund',
        'adjustment',
    ];

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }
}
