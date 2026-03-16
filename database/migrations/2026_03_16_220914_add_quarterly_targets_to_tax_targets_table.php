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
            $table->decimal('q1_target', 20, 2)->nullable()->after('target_amount');
            $table->decimal('q2_target', 20, 2)->nullable()->after('q1_target');
            $table->decimal('q3_target', 20, 2)->nullable()->after('q2_target');
            $table->decimal('q4_target', 20, 2)->nullable()->after('q3_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_targets', function (Blueprint $table) {
            $table->dropColumn(['q1_target', 'q2_target', 'q3_target', 'q4_target']);
        });
    }
};
