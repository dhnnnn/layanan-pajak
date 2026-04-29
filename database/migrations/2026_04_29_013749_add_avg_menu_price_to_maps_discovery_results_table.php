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
            $table->decimal('avg_menu_price', 12, 2)->nullable()->after('keyword')->comment('Rata-rata harga menu per pax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maps_discovery_results', function (Blueprint $table) {
            $table->dropColumn('avg_menu_price');
        });
    }
};
