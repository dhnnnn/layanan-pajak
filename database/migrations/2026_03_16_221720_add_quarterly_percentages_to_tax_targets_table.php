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
        Schema::table('tax_targets', function (Blueprint $table) {
            $table->decimal('q1_percentage', 5, 2)->nullable()->after('q4_target');
            $table->decimal('q2_percentage', 5, 2)->nullable()->after('q1_percentage');
            $table->decimal('q3_percentage', 5, 2)->nullable()->after('q2_percentage');
            $table->decimal('q4_percentage', 5, 2)->nullable()->after('q3_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_targets', function (Blueprint $table) {
            $table->dropColumn(['q1_percentage', 'q2_percentage', 'q3_percentage', 'q4_percentage']);
        });
    }
};
