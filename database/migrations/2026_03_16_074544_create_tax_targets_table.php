<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_targets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tax_type_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('tax_type_id')->references('id')->on('tax_types')->cascadeOnDelete();
            $table->unique(['tax_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_targets');
    }
};
