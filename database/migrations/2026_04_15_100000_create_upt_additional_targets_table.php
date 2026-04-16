<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upt_additional_targets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('upt_id')->constrained('upts')->cascadeOnDelete();
            $table->string('no_ayat', 20);
            $table->smallInteger('year');
            $table->decimal('additional_target', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['upt_id', 'no_ayat', 'year'], 'upt_additional_targets_unique');
            $table->index(['no_ayat', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upt_additional_targets');
    }
};
