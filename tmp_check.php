<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('name', 'like', '%MUJIONO%')->first();
if (!$user) {
    echo "User tidak ditemukan\n";
    exit;
}

echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
echo "UPT ID: " . ($user->upt_id ?? 'null') . "\n";

// Cek semua role yang ada
echo "\n=== SEMUA ROLES ===\n";
$roles = \Spatie\Permission\Models\Role::all(['name']);
foreach ($roles as $r) {
    echo "  - {$r->name}\n";
}

// Cek semua user dengan role pegawai
echo "\n=== USER DENGAN ROLE PEGAWAI ===\n";
$pegawai = User::role('pegawai')->get(['name','email']);
foreach ($pegawai as $p) {
    echo "  {$p->name} | {$p->email}\n";
}
?>
