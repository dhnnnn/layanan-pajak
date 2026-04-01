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
        Schema::dropIfExists('upt_comparisons');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('upt_comparisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('upt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('target_amount', 20, 2);
            $table->timestamps();
        });
    }
};
