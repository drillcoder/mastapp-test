<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'referrer_master_id',
        'referred_master_id',
        'referral_id',
        'payment_id',
        'payment_amount',
        'amount',
        'percent',
        'status',
    ];

    protected $casts = [
        'payment_amount' => 'integer',
        'amount' => 'integer',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
