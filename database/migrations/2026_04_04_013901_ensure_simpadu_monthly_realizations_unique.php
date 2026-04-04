<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simpadu_monthly_realizations', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM simpadu_monthly_realizations'))
                ->pluck('Key_name')->unique()->toArray();

            if (in_array('smpd_monthly_unique', $indexes)) {
                $table->dropUnique('smpd_monthly_unique');
            }

            $table->unique(['year', 'ayat', 'month'], 'smpd_monthly_unique');
        });
    }

    public function down(): void
    {
        Schema::table('simpadu_monthly_realizations', function (Blueprint $table) {
            $table->dropUnique('smpd_monthly_unique');
        });
    }
};
