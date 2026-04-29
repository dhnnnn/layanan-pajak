<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maps_discovery_results', function (Blueprint $table) {
            $table->json('popular_times')->nullable()->after('avg_menu_price')
                ->comment('Statistik kunjungan per jam dari Google Maps, grouped by day');
        });
    }

    public function down(): void
    {
        Schema::table('maps_discovery_results', function (Blueprint $table) {
            $table->dropColumn('popular_times');
        });
    }
};
