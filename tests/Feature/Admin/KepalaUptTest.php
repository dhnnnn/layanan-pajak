<?php

use App\Models\Upt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RoleSeeder::class]);

    $this->upt1 = Upt::factory()->create(['name' => 'UPT Satu']);
    $this->upt2 = Upt::factory()->create(['name' => 'UPT Dua']);

    $this->kepalaUpt1 = User::factory()->create([
        'name' => 'Kepala UPT 1',
        'upt_id' => $this->upt1->id,
    ]);
    $this->kepalaUpt1->assignRole('kepala_upt');
});

test('kepala upt can access admin dashboard', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('kepala upt is redirected to admin dashboard after login', function () {
    $this->post(route('login.store'), [
        'email' => $this->kepalaUpt1->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('kepala upt can view their own upt', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.show', $this->upt1))
        ->assertOk();
});

test('kepala upt cannot view other upts', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.show', $this->upt2))
        ->assertForbidden();
});

test('kepala upt can manage their own districts', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.districts', $this->upt1))
        ->assertOk();
});

test('kepala upt cannot manage other upt districts', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.districts', $this->upt2))
        ->assertForbidden();
});

test('kepala upt can manage their own employees', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.employees.manage', $this->upt1))
        ->assertOk();
});

test('kepala upt cannot manage other upt employees', function () {
    $this->actingAs($this->kepalaUpt1)
        ->get(route('admin.upts.employees.manage', $this->upt2))
        ->assertForbidden();
});
