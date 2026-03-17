<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_realization_daily_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->decimal('amount', 20, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tax_type_id', 'district_id', 'entry_date'], 'daily_entries_composite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_realization_daily_entries');
    }
};
