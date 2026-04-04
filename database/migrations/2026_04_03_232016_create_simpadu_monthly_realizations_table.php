<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simpadu_monthly_realizations', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->string('ayat', 20);
            $table->string('kd_kecamatan', 20)->nullable();
            $table->tinyInteger('month'); // 1-12
            $table->decimal('total_bayar', 20, 2)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'ayat', 'kd_kecamatan', 'month'], 'smpd_monthly_unique');
            $table->index(['year', 'ayat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simpadu_monthly_realizations');
    }
};
