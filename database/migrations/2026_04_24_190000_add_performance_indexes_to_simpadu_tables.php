<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simpadu_tax_payers', function (Blueprint $table) {
            $table->index(['year', 'month', 'status'], 'idx_stp_year_month_status');
            $table->index(['year', 'month', 'npwpd', 'nop'], 'idx_stp_year_month_npwpd_nop');
        });

        Schema::table('simpadu_sptpd_reports', function (Blueprint $table) {
            $table->index(['year', 'month', 'npwpd', 'nop'], 'idx_ssr_year_month_npwpd_nop');
        });
    }

    public function down(): void
    {
        Schema::table('simpadu_tax_payers', function (Blueprint $table) {
            $table->dropIndex('idx_stp_year_month_status');
            $table->dropIndex('idx_stp_year_month_npwpd_nop');
        });

        Schema::table('simpadu_sptpd_reports', function (Blueprint $table) {
            $table->dropIndex('idx_ssr_year_month_npwpd_nop');
        });
    }
};
