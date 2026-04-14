<?php

use App\Models\SimpaduTaxPayer;
use App\Models\TaxTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('it returns 401 if token is missing or invalid', function () {
    $response = $this->getJson('/api/tax-summary');
    $response->assertStatus(401);

    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/tax-summary');
    $response->assertStatus(401);
});

test('it returns tax summary data with valid token', function () {
    $token = 'test-secret-token';
    Config::set('app.external_api_token', $token);

    // Seed some data
    $year = (int) date('Y');
    TaxTarget::factory()->create(['year' => $year, 'target_amount' => 1000000]);

    // We can't easily seed SimpaduTaxPayer if it's external or has complex structure,
    // but we can try with fake or just check for structure if DB is empty

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/tax-summary');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'year',
            'total_target',
            'total_realization',
            'percentage',
        ]);
});

test('it returns all time summary when year=all', function () {
    $token = 'test-secret-token';
    Config::set('app.external_api_token', $token);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/tax-summary?year=all');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'summary' => [
                'total_target',
                'total_realization',
                'percentage',
            ],
            'yearly_breakdown',
        ]);
});
