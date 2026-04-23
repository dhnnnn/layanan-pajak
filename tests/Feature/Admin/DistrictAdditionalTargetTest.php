<?php

use App\Models\District;
use App\Models\DistrictAdditionalTarget;
use App\Models\SimpaduTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->givePermissionTo('view additional-targets');
});

test('admin can view district additional targets index', function () {
    $district = District::factory()->create(['name' => 'Kecamatan Test']);

    // Create base target for additional context in view
    SimpaduTarget::query()->create([
        'no_ayat' => '4.1.01.01',
        'keterangan' => 'Pajak Hotel',
        'year' => now()->year,
        'total_target' => 1000000000,
        'q1_pct' => 25,
        'q2_pct' => 50,
        'q3_pct' => 75,
        'q4_pct' => 100,
    ]);

    $additional = DistrictAdditionalTarget::create([
        'district_id' => $district->id,
        'no_ayat' => '4.1.01.01',
        'year' => now()->year,
        'additional_target' => 50000000,
        'start_quarter' => 2,
        'q2_additional' => 10000000,
        'q3_additional' => 20000000,
        'q4_additional' => 20000000,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.district-additional-targets.index'));

    $response->assertStatus(200);
    $response->assertSee('Kecamatan Test');
    $response->assertSee('Pajak Hotel');
    $response->assertSee('50.000.000');
});

test('guest cannot view district additional targets index', function () {
    $response = $this->get(route('admin.district-additional-targets.index'));
    $response->assertRedirect(route('login'));
});
