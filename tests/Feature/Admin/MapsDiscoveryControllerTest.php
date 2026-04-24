<?php

use App\Actions\MapsDiscovery\CrawlMapsAction;
use App\Actions\MapsDiscovery\MatchTaxPayersAction;
use App\Exceptions\ScraperErrorException;
use App\Exceptions\ScraperUnavailableException;
use App\Models\District;
use App\Models\TaxType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->taxType = TaxType::factory()->create(['simpadu_code' => '41101']);
    $this->district = District::factory()->create();
});

test('it displays the maps discovery page with filters', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.maps-discovery.index'))
        ->assertOk()
        ->assertViewIs('admin.maps-discovery.index')
        ->assertViewHas('taxTypes')
        ->assertViewHas('districts');
});

test('it returns crawl results with stats', function () {
    $crawlResults = collect([
        [
            'title' => 'Hotel Surya',
            'subtitle' => 'Jl. Raya Bangil No. 10',
            'category' => 'Hotel',
            'place_id' => 'ChIJ_abc123',
            'url' => 'https://maps.google.com/place/abc',
            'latitude' => -7.6012,
            'longitude' => 112.7834,
        ],
    ]);

    $matchedResults = collect([
        [
            'title' => 'Hotel Surya',
            'subtitle' => 'Jl. Raya Bangil No. 10',
            'category' => 'Hotel',
            'place_id' => 'ChIJ_abc123',
            'url' => 'https://maps.google.com/place/abc',
            'latitude' => -7.6012,
            'longitude' => 112.7834,
            'status' => 'terdaftar',
            'matched_npwpd' => 'P-001234',
            'matched_name' => 'HOTEL SURYA JAYA',
            'similarity_score' => 0.85,
        ],
    ]);

    $this->mock(CrawlMapsAction::class, function ($mock) use ($crawlResults) {
        $mock->shouldReceive('__invoke')->once()->andReturn($crawlResults);
    });

    $this->mock(MatchTaxPayersAction::class, function ($mock) use ($matchedResults) {
        $mock->shouldReceive('__invoke')->once()->andReturn($matchedResults);
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.maps-discovery.crawl'), [
            'tax_type_code' => '41101',
            'district_id' => $this->district->id,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'results',
            'stats' => ['total', 'terdaftar', 'potensi_baru'],
        ]);
});

test('it returns validation error when no tax type and no keyword', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.maps-discovery.crawl'), [
            'tax_type_code' => null,
            'keyword' => null,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('tax_type_code');
});

test('it redirects unauthenticated user to login for index', function () {
    $this->get(route('admin.maps-discovery.index'))
        ->assertRedirect(route('login'));
});

test('it redirects unauthenticated user to login for crawl', function () {
    $this->postJson(route('admin.maps-discovery.crawl'), [
        'tax_type_code' => '41101',
    ])
        ->assertUnauthorized();
});

test('it returns 503 when scraper is unavailable', function () {
    $this->mock(CrawlMapsAction::class, function ($mock) {
        $mock->shouldReceive('__invoke')->once()->andThrow(new ScraperUnavailableException);
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.maps-discovery.crawl'), [
            'tax_type_code' => '41101',
        ])
        ->assertStatus(503)
        ->assertJson(['error' => 'Layanan scraper tidak tersedia. Hubungi administrator.']);
});

test('it returns 500 when scraper returns error', function () {
    $this->mock(CrawlMapsAction::class, function ($mock) {
        $mock->shouldReceive('__invoke')->once()->andThrow(new ScraperErrorException);
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.maps-discovery.crawl'), [
            'tax_type_code' => '41101',
        ])
        ->assertStatus(500)
        ->assertJson(['error' => 'Gagal mengambil data dari Google Maps. Pastikan layanan scraper aktif.']);
});

test('it returns empty results with info message', function () {
    $this->mock(CrawlMapsAction::class, function ($mock) {
        $mock->shouldReceive('__invoke')->once()->andReturn(collect());
    });

    $this->actingAs($this->admin)
        ->postJson(route('admin.maps-discovery.crawl'), [
            'tax_type_code' => '41101',
        ])
        ->assertOk()
        ->assertJson([
            'results' => [],
            'stats' => ['total' => 0, 'terdaftar' => 0, 'potensi_baru' => 0],
            'message' => 'Tidak ditemukan lokasi bisnis untuk pencarian ini. Coba ubah keyword atau wilayah.',
        ]);
});

test('it has correct route names registered', function () {
    $routes = collect(app('router')->getRoutes()->getRoutesByName());

    expect($routes->has('admin.maps-discovery.index'))->toBeTrue();
    expect($routes->has('admin.maps-discovery.crawl'))->toBeTrue();
});
