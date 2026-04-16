<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upt_additional_targets', function (Blueprint $table): void {
            $table->tinyInteger('start_quarter')->default(1)->after('additional_target'); // 1-4
            $table->decimal('q1_additional', 20, 2)->default(0)->after('start_quarter');
            $table->decimal('q2_additional', 20, 2)->default(0)->after('q1_additional');
            $table->decimal('q3_additional', 20, 2)->default(0)->after('q2_additional');
            $table->decimal('q4_additional', 20, 2)->default(0)->after('q3_additional');
        });
    }

    public function down(): void
    {
        Schema::table('upt_additional_targets', function (Blueprint $table): void {
            $table->dropColumn(['start_quarter', 'q1_additional', 'q2_additional', 'q3_additional', 'q4_additional']);
        });
    }
};
