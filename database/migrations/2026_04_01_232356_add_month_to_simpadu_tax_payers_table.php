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
        // Recreate simpadu_tax_payers with month inclusion
        Schema::dropIfExists('simpadu_tax_payers');

        Schema::create('simpadu_tax_payers', function (Blueprint $table) {
            $table->string('npwpd');
            $table->string('nop');
            $table->string('ayat', 20);
            $table->integer('year');
            $table->integer('month');
            $table->string('nm_wp');
            $table->string('nm_op');
            $table->text('almt_op')->nullable();
            $table->string('kd_kecamatan')->nullable();
            $table->decimal('total_ketetapan', 20, 2)->default(0);
            $table->decimal('total_bayar', 20, 2)->default(0);
            $table->decimal('total_tunggakan', 20, 2)->default(0);
            $table->string('status', 10)->nullable();
            $table->primary(['npwpd', 'nop', 'year', 'month', 'ayat']);
            $table->timestamps();
            
            $table->index(['kd_kecamatan', 'year', 'month']);
            $table->index(['ayat', 'year', 'month']);
        });

        // Create simpadu_sptpd_reports for detailed matrix view
        Schema::create('simpadu_sptpd_reports', function (Blueprint $table) {
            $table->string('npwpd');
            $table->string('nop');
            $table->integer('year');
            $table->integer('month');
            $table->date('tgl_lapor')->nullable();
            $table->string('masa_pajak')->nullable();
            $table->decimal('jml_lapor', 20, 2)->default(0);
            $table->primary(['npwpd', 'nop', 'year', 'month']);
            $table->timestamps();

            $table->index(['npwpd', 'nop', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpadu_sptpd_reports');
        Schema::dropIfExists('simpadu_tax_payers');
    }
};
