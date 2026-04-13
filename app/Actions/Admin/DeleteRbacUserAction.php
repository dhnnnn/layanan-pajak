<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DeleteRbacUserAction
{
    public function __invoke(User $user): void
    {
        if ($user->hasRole('admin')) {
            $adminCount = User::query()->role('admin')->count();
            if ($adminCount <= 1) {
                throw new \RuntimeException('Tidak dapat menghapus user ini karena merupakan satu-satunya administrator aktif.');
            }
        }

        $userId = $user->id;
        $user->delete();

        Log::info('RBAC: user deleted', [
            'admin' => auth()->id(),
            'deleted_user_id' => $userId,
            'at' => now()->toIso8601String(),
        ]);
    }
}
