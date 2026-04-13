<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    public const SYSTEM_ROLES = ['admin', 'kepala_upt', 'pegawai', 'pemimpin'];

    public function isSystemRole(): bool
    {
        return in_array($this->name, self::SYSTEM_ROLES, true);
    }
}
