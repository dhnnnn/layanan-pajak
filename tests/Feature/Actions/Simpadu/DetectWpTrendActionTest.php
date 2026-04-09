<?php

use App\Actions\Simpadu\DetectWpTrendAction;

beforeEach(function (): void {
    $this->action = new DetectWpTrendAction;
});

it('returns no_data when values array is empty', function (): void {
    $result = ($this->action)([]);

    expect($result['direction'])->toBe('no_data')
        ->and($result['is_inactive'])->toBeTrue();
});

it('returns inactive when all payments are zero', function (): void {
    $result = ($this->action)([0.0, 0.0, 0.0, 0.0]);

    expect($result['direction'])->toBe('inactive')
        ->and($result['is_inactive'])->toBeTrue();
});

it('detects upward trend', function (): void {
    // Data naik konsisten: 1jt → 5jt
    $result = ($this->action)([1_000_000, 2_000_000, 3_000_000, 4_000_000, 5_000_000]);

    expect($result['direction'])->toBe('up')
        ->and($result['is_inactive'])->toBeFalse()
        ->and($result['slope'])->toBeGreaterThan(0)
        ->and($result['change_pct'])->toBeGreaterThan(0);
});

it('detects downward trend', function (): void {
    // Data turun konsisten: 5jt → 1jt
    $result = ($this->action)([5_000_000, 4_000_000, 3_000_000, 2_000_000, 1_000_000]);

    expect($result['direction'])->toBe('down')
        ->and($result['slope'])->toBeLessThan(0)
        ->and($result['change_pct'])->toBeLessThan(0);
});

it('detects stable trend when values are flat', function (): void {
    $result = ($this->action)([3_000_000, 3_000_000, 3_000_000, 3_000_000]);

    expect($result['direction'])->toBe('stable')
        ->and($result['slope'])->toBe(0.0);
});

it('returns correct data_points count', function (): void {
    $values = [1_000_000, 2_000_000, 3_000_000];
    $result = ($this->action)($values);

    expect($result['data_points'])->toBe(3);
});
