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
        Schema::create('officer_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tax_payer_id'); // NPWPD or NOP from simpadunew
            $table->string('tax_payer_name')->nullable();
            $table->string('tax_payer_address')->nullable();
            $table->string('tax_type_code')->nullable();
            $table->foreignUuid('officer_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('district_id')->constrained('districts')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, ongoing, completed, cancelled
            $table->decimal('amount_sptpd', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tax_payer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_tasks');
    }
};
