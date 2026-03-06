<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wncms\Models\BaseModel;

class Order extends BaseModel
{
    use HasFactory;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'order';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'payload' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-file-invoice-dollar',
    ];

    public const NAME_KEY = 'user_order';

    public const STATUSES = [
        'draft',
        'pending_payment',
        'paid',
        'processing',
        'completed',
        'failed',
        'cancelled',
        'refunded',
    ];

    public const TYPES = [
        'one_time',
        'subscription_initial',
        'subscription_renewal',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (!empty($model->slug)) {
                return;
            }

            do {
                $slug = wncms()->getUniqueSlug('orders', 'slug', 12, 'upper', 'ORD-');
            } while (self::where('slug', $slug)->exists());

            $model->slug = $slug;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(wncms()->getModelClass('order_item'));
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(wncms()->getModelClass('transaction'));
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
