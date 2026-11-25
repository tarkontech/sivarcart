<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Enums\InventoryMovementType;
use SivarcartCore\SivarcartCore\Models\InventoryMovement;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

it('puts the units back into the stock of the variant', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, 'quantity' => 5])
        ->assertSuccessful();

    expect($variant->fresh()->stock_quantity)->toBe(15);
});

it('records the movement of the stock in the ledger', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', [
        'variant' => $variant->sku,
        'quantity' => 5,
        '--reason' => 'Llegaron cinco unidades del proveedor',
    ])->assertSuccessful();

    $movement = InventoryMovement::query()->firstOrFail();

    expect($movement->type)->toBe(InventoryMovementType::RESTOCK)
        ->and($movement->quantity)->toBe(5)
        ->and($movement->stock_after)->toBe(15)
        ->and($movement->reason)->toBe('Llegaron cinco unidades del proveedor')
        ->and($movement->product_variant_id)->toBe($variant->getKey());
});

it('sets the exact stock of the variant', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, '--stock' => 3])
        ->assertSuccessful();

    expect($variant->fresh()->stock_quantity)->toBe(3);
});

it('records an adjustment when the stock is set to an exact quantity', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, '--stock' => 3])
        ->assertSuccessful();

    $movement = InventoryMovement::query()->firstOrFail();

    expect($movement->type)->toBe(InventoryMovementType::ADJUSTMENT)
        ->and($movement->quantity)->toBe(-7)
        ->and($movement->stock_after)->toBe(3);
});

it('reports the stock the variant has after the movement', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, 'quantity' => 5])
        ->expectsOutputToContain('15')
        ->assertSuccessful();
});

it('fails when the variant does not exist', function () {
    $this->artisan('sivarcart:restock', ['variant' => 'NO-EXISTE', 'quantity' => 5])
        ->assertFailed();

    expect(InventoryMovement::query()->count())->toBe(0);
});

it('fails when the command is not told how much to move', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku])
        ->assertFailed();

    expect($variant->fresh()->stock_quantity)->toBe(10)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('fails when the units are not more than zero', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, 'quantity' => 0])
        ->assertFailed();

    expect($variant->fresh()->stock_quantity)->toBe(10)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('fails when the stock would be negative', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, '--stock' => -1])
        ->assertFailed();

    expect($variant->fresh()->stock_quantity)->toBe(10)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('does not move the stock of a variant that was deleted', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $variant->delete();

    $this->artisan('sivarcart:restock', ['variant' => $variant->sku, 'quantity' => 5])
        ->assertFailed();

    expect(InventoryMovement::query()->count())->toBe(0);
});
