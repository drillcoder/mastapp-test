<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->unique('referred_master_id');
        });

        Schema::table('referral_earnings', function (Blueprint $table) {
            $table->unique('referral_id');
            $table->unique('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('referral_earnings', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
            $table->dropUnique(['referral_id']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropUnique(['referred_master_id']);
        });
    }
};
