<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clear existing data and rebuild with new unique constraint (no kd_kecamatan)
        Schema::table('simpadu_monthly_realizations', function (Blueprint $table) {
            $table->dropUnique('smpd_monthly_unique');
            $table->unique(['year', 'ayat', 'month'], 'smpd_monthly_unique');
        });
    }

    public function down(): void
    {
        Schema::table('simpadu_monthly_realizations', function (Blueprint $table) {
            $table->dropUnique('smpd_monthly_unique');
            $table->unique(['year', 'ayat', 'kd_kecamatan', 'month'], 'smpd_monthly_unique');
        });
    }
};
