<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wncms\Models\BaseModel;

class Transaction extends BaseModel
{
    use HasFactory;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'transaction';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_fraud' => 'boolean',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-bill-transfer',
    ];

    public const STATUSES = [
        'pending',
        'succeeded',
        'completed',
        'failed',
        'refunded',
        'cancelled',
    ];

    public const TYPES = [
        'charge',
        'renewal',
        'refund',
        'adjustment',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }

    public function payment_gateway(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('payment_gateway'));
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('subscription'));
    }
}
