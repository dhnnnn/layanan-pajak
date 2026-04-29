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
        Schema::create('maps_statistics', function (Blueprint $table) {
            $table->id();
            $table->uuid('maps_discovery_result_id');
            $table->string('hour_range', 10); // e.g., '12-13'
            $table->enum('day_of_week', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->integer('visitor_count')->default(0);
            $table->timestamps();

            $table->foreign('maps_discovery_result_id')
                ->references('id')
                ->on('maps_discovery_results')
                ->onDelete('cascade');

            $table->unique(['maps_discovery_result_id', 'hour_range', 'day_of_week'], 'maps_stats_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maps_statistics');
    }
};
