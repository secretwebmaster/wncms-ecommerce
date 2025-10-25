<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Transaction extends BaseModel
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

    public const STATUSES = [
        'completed',
        'refunded',
        'failed',
    ];

    public function order()
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }
}
