<?php

namespace App\Policies;

use App\Models\Upt;
use App\Models\User;

class UptPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') && ! $user->hasRole('kepala_upt')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('kepala_upt');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Upt $upt): bool
    {
        return $user->upts()->where('upts.id', $upt->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Only admin (handled in before)
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Upt $upt): bool
    {
        return false; // Only admin (handled in before)
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Upt $upt): bool
    {
        return false; // Only admin (handled in before)
    }

    /**
     * Determine whether the user can manage employees for the UPT.
     */
    public function manageEmployees(User $user, Upt $upt): bool
    {
        return $user->upts()->where('upts.id', $upt->id)->exists();
    }

    public function manageDistricts(User $user, Upt $upt): bool
    {
        return $user->upts()->where('upts.id', $upt->id)->exists();
    }
}
