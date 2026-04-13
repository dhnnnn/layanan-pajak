<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upt_users', function (Blueprint $table): void {
            $table->uuid('upt_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('upt_id')->references('id')->on('upts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->primary(['upt_id', 'user_id']);
        });

        // Migrasi data dari kolom upt_id di users ke tabel pivot
        DB::table('users')
            ->whereNotNull('upt_id')
            ->get(['id', 'upt_id'])
            ->each(function (object $user): void {
                DB::table('upt_users')->insertOrIgnore([
                    'upt_id' => $user->upt_id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // Drop kolom upt_id dari users
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['upt_id']);
            $table->dropColumn('upt_id');
        });
    }

    public function down(): void
    {
        // Kembalikan kolom upt_id ke users
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('upt_id')->nullable()->after('email')->index();
            $table->foreign('upt_id')->references('id')->on('upts')->nullOnDelete();
        });

        // Restore data dari pivot ke kolom (ambil upt pertama per user)
        DB::table('upt_users')
            ->get(['upt_id', 'user_id'])
            ->each(function (object $row): void {
                DB::table('users')
                    ->where('id', $row->user_id)
                    ->update(['upt_id' => $row->upt_id]);
            });

        Schema::dropIfExists('upt_users');
    }
};
