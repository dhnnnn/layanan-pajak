<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password', 'upt_id'];

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isKepalaUpt(): bool
    {
        return $this->hasRole('kepala_upt');
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'employee_districts');
    }

    /**
     * Get the districts the user has access to.
     * Regular officers have assigned districts.
     * Kepala UPT have all districts in their UPT.
     * Admin has access to all districts.
     */
    public function accessibleDistricts()
    {
        if ($this->hasRole('admin')) {
            return District::query();
        }

        if ($this->hasRole('kepala_upt')) {
            if (! $this->upt_id) {
                return District::query()->whereRaw('1 = 0'); // No UPT, no districts
            }

            return District::query()->whereHas('upts', function ($q) {
                $q->where('upts.id', $this->upt_id);
            });
        }

        return $this->districts(); // Relasi BelongsToMany original untuk pegawai
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function upt(): BelongsTo
    {
        return $this->belongsTo(Upt::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OfficerTask::class, 'officer_id');
    }
}
