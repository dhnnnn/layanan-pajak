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
        Schema::table('simpadu_tax_payers', function (Blueprint $table) {
            $table->string('status', 10)->nullable()->after('kd_kecamatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simpadu_tax_payers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
