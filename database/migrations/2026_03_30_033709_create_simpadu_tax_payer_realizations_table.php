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
        Schema::create('simpadu_tax_payer_realizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tax_type_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->string('npwpd')->index();
            $table->string('nm_wp');
            $table->decimal('total_realization', 20, 2)->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['tax_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpadu_tax_payer_realizations');
    }
};
