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
        Schema::dropIfExists('simpadu_tax_payers');

        Schema::create('simpadu_tax_payers', function (Blueprint $table) {
            $table->string('npwpd');
            $table->string('nop');
            $table->string('ayat', 20);
            $table->integer('year');
            $table->string('nm_wp');
            $table->string('nm_op');
            $table->text('almt_op')->nullable();
            $table->string('kd_kecamatan')->nullable();
            $table->decimal('total_ketetapan', 20, 2)->default(0);
            $table->decimal('total_bayar', 20, 2)->default(0);
            $table->decimal('total_tunggakan', 20, 2)->default(0);
            $table->primary(['npwpd', 'nop', 'year', 'ayat']);
            $table->timestamps();
            
            $table->index(['kd_kecamatan', 'year']);
            $table->index(['ayat', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpadu_tax_payers');
    }
};
