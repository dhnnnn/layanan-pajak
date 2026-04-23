<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_additional_targets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('no_ayat', 20);
            $table->smallInteger('year');
            $table->decimal('additional_target', 20, 2)->default(0);
            $table->tinyInteger('start_quarter')->default(1);
            $table->decimal('q1_additional', 20, 2)->default(0);
            $table->decimal('q2_additional', 20, 2)->default(0);
            $table->decimal('q3_additional', 20, 2)->default(0);
            $table->decimal('q4_additional', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['district_id', 'no_ayat', 'year'], 'district_additional_targets_unique');
            $table->index(['no_ayat', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_additional_targets');
    }
};
