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
        Schema::create('monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('maps_discovery_result_id');
            $table->uuid('officer_id');
            $table->date('monitoring_date');
            $table->string('monitoring_hour', 10); // e.g., '12-13'
            $table->enum('day_of_week', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->integer('visitor_count')->default(0);
            $table->integer('parking_bus')->default(0);
            $table->integer('parking_elf')->default(0);
            $table->integer('parking_mobil')->default(0);
            $table->integer('parking_motor')->default(0);
            $table->json('photos')->nullable(); // Array of photo URLs
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->enum('validation_status', ['valid', 'tidak_valid', 'pending'])->default('pending');
            $table->timestamps();

            $table->foreign('maps_discovery_result_id')
                ->references('id')
                ->on('maps_discovery_results')
                ->onDelete('cascade');

            $table->foreign('officer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_reports');
    }
};
