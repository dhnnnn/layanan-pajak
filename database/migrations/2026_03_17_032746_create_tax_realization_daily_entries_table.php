<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_realization_daily_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tax_type_id');
            $table->uuid('district_id');
            $table->uuid('user_id');
            $table->date('entry_date');
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('tax_type_id')->references('id')->on('tax_types')->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['tax_type_id', 'district_id', 'entry_date'], 'daily_entries_composite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_realization_daily_entries');
    }
};
