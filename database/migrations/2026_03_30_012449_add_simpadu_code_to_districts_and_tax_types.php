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
        Schema::table('districts', function (Blueprint $table) {
            $table->string('simpadu_code')->nullable()->after('id');
        });

        Schema::table('tax_types', function (Blueprint $table) {
            $table->string('simpadu_code')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('simpadu_code');
        });

        Schema::table('tax_types', function (Blueprint $table) {
            $table->dropColumn('simpadu_code');
        });
    }
};
