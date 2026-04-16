<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upt_additional_targets', function (Blueprint $table): void {
            $table->dropForeign(['upt_id']);
            $table->dropColumn('upt_id');

            // Ubah unique constraint: sekarang cukup no_ayat + year (global)
            $table->dropUnique('upt_additional_targets_unique');
            $table->unique(['no_ayat', 'year'], 'upt_additional_targets_unique');
        });
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
