<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Preserve/Update Admin
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@upp.pendapatan'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles(['admin']);
        $admin->updateQuietly(['role_id' => $admin->roles->first()?->id]);

        // 2. Clear other users (Pegawai)
        // We delete users who are NOT the admin we just created/updated
        User::query()->where('id', '!=', $admin->id)->delete();

        // 3. Define Officers data
        $officers = [
            // UPT I
            ['name' => 'MUJIONO', 'upt' => 'UPT-01', 'districts' => ['Purwodadi']],
            ['name' => 'TITOK S.', 'upt' => 'UPT-01', 'districts' => ['Tutur']],
            ['name' => 'AINUR ROFIQ', 'upt' => 'UPT-01', 'districts' => ['Purwosari']],
            ['name' => 'M. MA\'ARIF', 'upt' => 'UPT-01', 'districts' => ['Prigen']],
            ['name' => 'HARTONO', 'upt' => 'UPT-01', 'districts' => ['Prigen']],
            ['name' => 'M. CHOIRUL', 'upt' => 'UPT-01', 'districts' => ['Sukorejo']],
            ['name' => 'SURYANI', 'upt' => 'UPT-01', 'districts' => ['Pandaan']],
            ['name' => 'DEVY', 'upt' => 'UPT-01', 'districts' => ['Pandaan']],
            ['name' => 'A. CHOLIDIN', 'upt' => 'UPT-01', 'districts' => ['Gempol']],
            ['name' => 'M. USOLLI', 'upt' => 'UPT-01', 'districts' => ['Beji']],
            ['name' => 'SEGER S.', 'upt' => 'UPT-01', 'districts' => ['Bangil']],

            // UPT II
            ['name' => 'M. FIRMANSYAH', 'upt' => 'UPT-02', 'districts' => ['Rejoso', 'Lekok']],
            ['name' => 'ROMAWI', 'upt' => 'UPT-02', 'districts' => ['Gondangwetan']],
            ['name' => 'RIZANATUL FUAD', 'upt' => 'UPT-02', 'districts' => ['Pasrepan', 'Puspo', 'Tosari']],
            ['name' => 'MOCH. ANSORI', 'upt' => 'UPT-02', 'districts' => ['Pohjentrek']],
            ['name' => 'HENDRIK NUR CAHYONO', 'upt' => 'UPT-02', 'districts' => ['Grati', 'Nguling']],
            ['name' => 'MATRAIS', 'upt' => 'UPT-02', 'districts' => ['Rembang']],
            ['name' => 'WANTO', 'upt' => 'UPT-02', 'districts' => ['Kraton']],
            ['name' => 'ACH. BILLY', 'upt' => 'UPT-02', 'districts' => ['Kejayan']],
            ['name' => 'HARIS ESKARIANSYAH', 'upt' => 'UPT-02', 'districts' => ['Winongan', 'Lumbang']],
            ['name' => 'TOMY', 'upt' => 'UPT-02', 'districts' => ['Winongan', 'Lumbang']],
            ['name' => 'ABDUL KADIR', 'upt' => 'UPT-02', 'districts' => ['Wonorejo']],
        ];

        foreach ($officers as $data) {
            // Generate email: lowercase name, remove dots/spaces/special chars
            $emailName = strtolower($data['name']);
            $emailName = preg_replace('/[^a-z0-9]/', '.', $emailName);
            $emailName = preg_replace('/\.+/', '.', trim($emailName, '.'));
            $email = $emailName.'@upp.pendapatan';

            $upt = Upt::where('code', $data['upt'])->first();

            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make('password'),
            ]);

            $user->assignRole('pegawai');
            $user->updateQuietly(['role_id' => $user->roles->first()?->id]);

            if ($upt) {
                $user->upts()->sync([$upt->id]);
            }

            // Map districts
            $districtIds = District::whereIn('name', $data['districts'])->pluck('id');
            $user->districts()->sync($districtIds);
        }
    }
}
