<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop upt_id jika masih ada
        if (Schema::hasColumn('upt_additional_targets', 'upt_id')) {
            Schema::table('upt_additional_targets', function (Blueprint $table): void {
                $table->dropForeign(['upt_id']);
                $table->dropUnique('upt_additional_targets_unique');
                $table->dropColumn('upt_id');
            });
        }

        // Buat unique constraint baru jika belum ada
        $indexExists = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'upt_additional_targets'
              AND INDEX_NAME = 'upt_additional_targets_unique'
        ")[0]->cnt > 0;

        if (! $indexExists) {
            Schema::table('upt_additional_targets', function (Blueprint $table): void {
                $table->unique(['no_ayat', 'year'], 'upt_additional_targets_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('upt_additional_targets', function (Blueprint $table): void {
            $table->dropUnique('upt_additional_targets_unique');
            $table->foreignUuid('upt_id')->after('id')->constrained('upts')->cascadeOnDelete();
            $table->unique(['upt_id', 'no_ayat', 'year'], 'upt_additional_targets_unique');
        });
    }
};
