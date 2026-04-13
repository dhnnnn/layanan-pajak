<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('role_id')->nullable()->after('upt_id')->index();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });

        // Backfill role_id dari relasi Spatie yang sudah ada
        User::query()->with('roles')->each(function (User $user): void {
            $role = $user->roles->first();
            if ($role) {
                $user->updateQuietly(['role_id' => $role->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
