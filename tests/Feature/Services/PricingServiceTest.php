<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\Tax;
use SivarcartCore\SivarcartCore\Services\PricingService;

it('keeps the tax inside the price when the catalog includes it', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 113.00, 'quantity' => 1, 'tax_rate' => 0.13],
    ], true);

    expect($lines[0]['line_subtotal'])->toBe(113.0)
        ->and($lines[0]['tax_total'])->toBe(13.0)
        ->and($lines[0]['line_total'])->toBe(113.0);
});

it('adds the tax on top when the catalog does not include it', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 100.00, 'quantity' => 2, 'tax_rate' => 0.13],
    ], false);

    expect($lines[0]['line_subtotal'])->toBe(200.0)
        ->and($lines[0]['tax_total'])->toBe(26.0)
        ->and($lines[0]['line_total'])->toBe(226.0);
});

it('rounds every amount to two decimals', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 9.99, 'quantity' => 3, 'tax_rate' => 0.13],
    ], true);

    expect($lines[0]['line_subtotal'])->toBe(29.97)
        ->and($lines[0]['tax_total'])->toBe(3.45)
        ->and($lines[0]['line_total'])->toBe(29.97);
});

it('spreads a discount over the lines without losing a cent', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 10.01, 'quantity' => 1, 'tax_rate' => 0.13],
        ['unit_price' => 20.02, 'quantity' => 1, 'tax_rate' => 0.13],
        ['unit_price' => 30.03, 'quantity' => 1, 'tax_rate' => 0.13],
    ], true, 5.00);

    $discount = round(array_sum(array_column($lines, 'discount_total')), 2);

    expect($discount)->toBe(5.0)
        ->and($lines[0]['discount_total'])->toBe(0.83)
        ->and($lines[1]['discount_total'])->toBe(1.67)
        ->and($lines[2]['discount_total'])->toBe(2.5);
});

it('never discounts more than the subtotal', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 10.00, 'quantity' => 1, 'tax_rate' => 0.13],
    ], true, 25.00);

    expect($lines[0]['discount_total'])->toBe(10.0)
        ->and($lines[0]['line_total'])->toBe(0.0);
});

it('builds a total out of the rounded parts when the tax is included', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 25.50, 'quantity' => 2, 'tax_rate' => 0.13],
    ], true, 5.00);

    $totals = $pricing->totals($lines, 5.00, 0.0, true);

    expect($totals->subtotal)->toBe(51.0)
        ->and($totals->discountTotal)->toBe(5.0)
        ->and($totals->taxTotal)->toBe(5.29)
        ->and($totals->shippingTotal)->toBe(5.0)
        ->and($totals->total)->toBe(51.0)
        ->and($totals->taxInclusive)->toBeTrue();
});

it('adds the tax to the total when the catalog does not include it', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 100.00, 'quantity' => 1, 'tax_rate' => 0.13],
    ], false);

    $totals = $pricing->totals($lines, 0.0, 0.0, false);

    expect($totals->subtotal)->toBe(100.0)
        ->and($totals->taxTotal)->toBe(13.0)
        ->and($totals->total)->toBe(113.0)
        ->and($totals->taxInclusive)->toBeFalse();
});

it('takes the discount of the shipping out of the total', function () {
    $pricing = app(PricingService::class);

    $lines = $pricing->price([
        ['unit_price' => 100.00, 'quantity' => 1, 'tax_rate' => 0.13],
    ], true);

    $totals = $pricing->totals($lines, 7.50, 7.50, true);

    expect($totals->discountTotal)->toBe(7.5)
        ->and($totals->shippingTotal)->toBe(7.5)
        ->and($totals->total)->toBe(100.0);
});

it('uses the tax of the product when it is active', function () {
    $pricing = app(PricingService::class);

    $tax = Tax::factory()->create(['rate' => 0.15]);
    $product = Product::factory()->create(['tax_id' => $tax->getKey()]);

    expect($pricing->taxRateFor($product, 0.13))->toBe(0.15);
});

it('falls back to the default rate when the product has no tax', function () {
    $pricing = app(PricingService::class);

    $product = Product::factory()->create();

    expect($pricing->taxRateFor($product, 0.13))->toBe(0.13)
        ->and($pricing->taxRateFor(null, 0.13))->toBe(0.13);
});

it('falls back to the default rate when the tax of the product is not active', function () {
    $pricing = app(PricingService::class);

    $tax = Tax::factory()->create(['rate' => 0.15, 'is_active' => false]);
    $product = Product::factory()->create(['tax_id' => $tax->getKey()]);

    expect($pricing->taxRateFor($product, 0.13))->toBe(0.13);
});
