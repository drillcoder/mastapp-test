<?php

namespace App\Services\Referral;

use App\Models\Master;
use Illuminate\Database\Eloquent\Collection;

class ReferralReportService
{
    public function referralsFor(Master $master): Collection
    {
        return $master->referrals()
            ->with('referredMaster:id,name')
            ->withSum('earnings', 'amount')
            ->orderBy('id')
            ->get();
    }

    public function earningsFor(Master $master): array
    {
        return [
            'total' => (int) $master->referralEarnings()->sum('amount'),
            'pending' => (int) $master->referralEarnings()->pending()->sum('amount'),
            'paid' => (int) $master->referralEarnings()->paid()->sum('amount'),
            'rewarded_referrals' => $master->referrals()->active()->count(),
        ];
    }
}
