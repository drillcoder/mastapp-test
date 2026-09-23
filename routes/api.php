<?php

use App\Http\Controllers\ReferralController;
use App\Http\Middleware\EnsureCurrentMaster;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true]);

Route::prefix('referrals')->middleware(EnsureCurrentMaster::class)->group(function () {
    Route::post('/attach', [ReferralController::class, 'attach']);
    Route::get('/my', [ReferralController::class, 'my']);
    Route::get('/earnings', [ReferralController::class, 'earnings']);
});
