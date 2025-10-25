<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Credit extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-coins'
    ];

    public const ROUTES = [
        'index',
        'create',
        'recharge',
    ];

    public const TYPES = [
        'credit',
        'balance',
    ];


    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public static function add($user, $amount, $type = 'points')
    {
        $credit = $user->credits()->where('type', $type)->first();

        if (!$credit) {
            $credit = $user->credits()->create([
                'type' => $type,
                'amount' => 0,
            ]);
        }

        $credit->increment('amount', $amount);

        // record the transaction

        return $credit->amount;
    }
    
    public static function get($user, $type = 'points')
    {
        return $user->credits()->where('type', $type)->first()?->amount ?? 0;
    }
}
