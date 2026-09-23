<?php

namespace App\Http\Resources;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Referral */
class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'master_id' => $this->referred_master_id,
            'name' => $this->referredMaster->name,
            'attached_at' => $this->created_at->toIso8601String(),
            'rewarded' => $this->status === Referral::STATUS_REWARDED,
            'earned' => (int) ($this->earnings_sum_amount ?? 0),
        ];
    }
}
