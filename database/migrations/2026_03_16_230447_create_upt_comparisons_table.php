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
        Schema::create('upt_comparisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tax_type_id');
            $table->uuid('upt_id');
            $table->year('year');
            $table->decimal('target_amount', 20, 2);
            $table->timestamps();

            $table->foreign('tax_type_id')->references('id')->on('tax_types')->cascadeOnDelete();
            $table->foreign('upt_id')->references('id')->on('upts')->cascadeOnDelete();
            $table->unique(['tax_type_id', 'upt_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upt_comparisons');
    }
};
