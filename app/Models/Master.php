<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    protected $fillable = [
        'name',
        'referral_code',
    ];

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_master_id');
    }

    public function referralEarnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class, 'referrer_master_id');
    }
}
