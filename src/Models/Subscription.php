<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wncms\Models\BaseModel;

class Subscription extends BaseModel
{
    use HasFactory;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'subscription';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'subscribed_at' => 'datetime',
        'started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'next_billing_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'attributes' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-arrows-spin',
    ];

    public const STATUSES = [
        'pending',
        'trialing',
        'active',
        'past_due',
        'grace',
        'suspended',
        'cancelled',
        'expired',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('plan'));
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('price'));
    }

    public function payment_gateway(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('payment_gateway'));
    }

    public function last_transaction(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('transaction'), 'last_transaction_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(wncms()->getModelClass('transaction'));
    }

    public function scopeDueForRenewal($query)
    {
        return $query
            ->whereIn('status', ['active', 'past_due', 'grace'])
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now());
    }
}
