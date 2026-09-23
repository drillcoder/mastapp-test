<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachReferralRequest;
use App\Http\Resources\ReferralResource;
use App\Models\Master;
use App\Services\Referral\ReferralReportService;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

readonly class ReferralController
{
    public function __construct(
        private ReferralService $referrals,
        private ReferralReportService $reports,
    ) {
    }

    public function attach(AttachReferralRequest $request): JsonResponse
    {
        $referral = $this->referrals->registerReferral(
            $this->currentMaster($request),
            $request->validated('code'),
        );

        if ($referral === null) {
            return response()->json(['error' => 'Invalid referral code'], 422);
        }

        return response()->json(
            $referral->only(['id', 'referrer_master_id', 'referred_master_id', 'status']),
            $referral->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function my(Request $request): JsonResponse
    {
        $referrals = $this->reports->referralsFor($this->currentMaster($request));

        return response()->json([
            'referrals' => ReferralResource::collection($referrals)->resolve($request),
        ]);
    }

    public function earnings(Request $request): JsonResponse
    {
        return response()->json($this->reports->earningsFor($this->currentMaster($request)));
    }

    private function currentMaster(Request $request): Master
    {
        return $request->attributes->get('current_master');
    }
}
