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
        Schema::create('potential_calculations', function (Blueprint $table) {
            $table->id();
            $table->uuid('maps_discovery_result_id');
            $table->foreignId('monitoring_report_id')->nullable()->constrained('monitoring_reports')->onDelete('set null');

            // Input data
            $table->integer('checker_result')->comment('Hasil checker pengunjung saat monitoring');
            $table->integer('maps_hour_count')->comment('Jumlah pengunjung dari Maps pada jam checker');
            $table->integer('maps_weekly_total')->comment('Total statistik Maps seminggu');
            $table->decimal('avg_duration_hours', 4, 2)->default(2.5)->comment('Rata-rata durasi kunjungan dalam jam');
            $table->decimal('avg_menu_price', 12, 2)->comment('Rata-rata harga menu per pax');

            // Calculated results
            $table->integer('weekly_visitors')->comment('Jumlah pengunjung 1 minggu (calculated)');
            $table->decimal('weekly_potential_tax', 15, 2)->comment('Potensi pajak 1 minggu');
            $table->decimal('monthly_potential_tax', 15, 2)->comment('Potensi pajak 1 bulan');
            $table->decimal('min_potential_tax', 15, 2)->comment('Potensi pajak minimal (75%)');
            $table->decimal('max_potential_tax', 15, 2)->comment('Potensi pajak maksimal (125%)');

            $table->date('calculation_date');
            $table->timestamps();

            $table->foreign('maps_discovery_result_id')
                ->references('id')
                ->on('maps_discovery_results')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potential_calculations');
    }
};
