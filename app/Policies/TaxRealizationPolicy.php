<?php

namespace App\Policies;

use App\Models\TaxRealization;
use App\Models\User;

class TaxRealizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('kepala_upt') || $user->hasRole('pegawai');
    }

    public function view(User $user, TaxRealization $taxRealization): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('kepala_upt')) {
            return $taxRealization->district->upts()
                ->where('upts.id', $user->upt_id)
                ->exists();
        }

        return $taxRealization->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('pegawai');
    }

    public function update(User $user, TaxRealization $taxRealization): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $taxRealization->user_id === $user->id;
    }

    public function delete(User $user, TaxRealization $taxRealization): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $taxRealization->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaxRealization $taxRealization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaxRealization $taxRealization): bool
    {
        return false;
    }
}
