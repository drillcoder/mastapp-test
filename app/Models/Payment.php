<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const TYPE_CARD = 'card';
    public const TYPE_SBP = 'sbp';
    public const TYPE_PROMO = 'promo';
    public const TYPE_TRIAL = 'trial';

    protected $fillable = [
        'master_id',
        'amount',
        'type',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public static function isMonetary(self $payment): bool
    {
        return in_array($payment->type, [self::TYPE_CARD, self::TYPE_SBP], true)
            && $payment->amount > 0;
    }

    public function scopeMonetary(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_CARD, self::TYPE_SBP]);
    }
}
