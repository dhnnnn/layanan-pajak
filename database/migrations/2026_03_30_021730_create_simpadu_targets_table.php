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
        Schema::create('simpadu_targets', function (Blueprint $table) {
            $table->string('no_ayat');
            $table->integer('year');
            $table->string('keterangan')->nullable();
            $table->decimal('total_target', 20, 2)->default(0);
            $table->decimal('q1_pct', 8, 2)->default(0);
            $table->decimal('q2_pct', 8, 2)->default(0);
            $table->decimal('q3_pct', 8, 2)->default(0);
            $table->decimal('q4_pct', 8, 2)->default(0);
            $table->primary(['no_ayat', 'year']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpadu_targets');
    }
};
