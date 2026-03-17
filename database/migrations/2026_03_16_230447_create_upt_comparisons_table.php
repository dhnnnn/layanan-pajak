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
            $table->id();
            $table->foreignId('tax_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upt_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->decimal('target_amount', 20, 2);
            $table->timestamps();

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
