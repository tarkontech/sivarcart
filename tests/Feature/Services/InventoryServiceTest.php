<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Enums\InventoryMovementType;
use Tarkontech\SivarCart\Exceptions\InsufficientStock;
use Tarkontech\SivarCart\Exceptions\SivarCartException;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Services\InventoryService;

it('reserves the units of an order', function () {
    $variant = Product::factory()->create()->defaultVariant();

    $movement = app(InventoryService::class)->reserve($variant, 3, 'Orden SIV-1', Order::class, 1);

    $fresh = $variant->fresh();

    expect($fresh->reserved_quantity)->toBe(3)
        ->and($fresh->stock_quantity)->toBe(25)
        ->and($fresh->availableStock())->toBe(22)
        ->and($movement->type)->toBe(InventoryMovementType::RESERVATION)
        ->and($movement->quantity)->toBe(3)
        ->and($movement->stock_after)->toBe(3)
        ->and($movement->reference_type)->toBe(Order::class)
        ->and($movement->reference_id)->toBe(1)
        ->and($movement->meta)->toBe([
            'stock_quantity' => 25,
            'reserved_quantity' => 3,
            'available' => 22,
        ]);
});

it('refuses to reserve more units than the store has', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $variant->update(['stock_quantity' => 2]);

    expect(fn () => app(InventoryService::class)->reserve($variant, 3))
        ->toThrow(InsufficientStock::class);
});

it('does not reserve a unit when there is not enough stock', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $variant->update(['stock_quantity' => 2]);

    try {
        app(InventoryService::class)->reserve($variant, 3);
    } catch (InsufficientStock) {
        // The stock has to stay untouched.
    }

    expect($variant->fresh()->reserved_quantity)->toBe(0)
        ->and($variant->fresh()->stock_quantity)->toBe(2);
});

it('gives the reserved units back', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $inventory = app(InventoryService::class);

    $inventory->reserve($variant, 3);
    $movement = $inventory->release($variant, 3, 'Orden cancelada', Order::class, 1);

    $fresh = $variant->fresh();

    expect($fresh->reserved_quantity)->toBe(0)
        ->and($fresh->stock_quantity)->toBe(25)
        ->and($movement->type)->toBe(InventoryMovementType::RELEASE)
        ->and($movement->quantity)->toBe(3)
        ->and($movement->stock_after)->toBe(0);
});

it('turns a reservation into a sale', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $inventory = app(InventoryService::class);

    $inventory->reserve($variant, 3);
    $movement = $inventory->sell($variant, 3, 'Orden SIV-1 pagada', Order::class, 1);

    $fresh = $variant->fresh();

    expect($fresh->stock_quantity)->toBe(22)
        ->and($fresh->reserved_quantity)->toBe(0)
        ->and($fresh->availableStock())->toBe(22)
        ->and($movement->type)->toBe(InventoryMovementType::SALE)
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->stock_after)->toBe(22);
});

it('refuses to sell units that were never reserved', function () {
    $variant = Product::factory()->create()->defaultVariant();

    expect(fn () => app(InventoryService::class)->sell($variant, 1))
        ->toThrow(InsufficientStock::class);
});

it('puts the units of a refunded order back into the stock', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $inventory = app(InventoryService::class);

    $inventory->reserve($variant, 2);
    $inventory->sell($variant, 2);

    $movement = $inventory->returnToStock($variant, 2, 'Reembolso', Order::class, 1);

    $fresh = $variant->fresh();

    expect($fresh->stock_quantity)->toBe(25)
        ->and($fresh->reserved_quantity)->toBe(0)
        ->and($movement->type)->toBe(InventoryMovementType::RETURN)
        ->and($movement->quantity)->toBe(2)
        ->and($movement->stock_after)->toBe(25);
});

it('records a restock', function () {
    $variant = Product::factory()->create()->defaultVariant();

    $movement = app(InventoryService::class)->restock($variant, 10, 'Compra al proveedor');

    expect($variant->fresh()->stock_quantity)->toBe(35)
        ->and($movement->type)->toBe(InventoryMovementType::RESTOCK)
        ->and($movement->quantity)->toBe(10)
        ->and($movement->stock_after)->toBe(35);
});

it('sets the stock to an exact quantity', function () {
    $variant = Product::factory()->create()->defaultVariant();

    $movement = app(InventoryService::class)->adjust($variant, 7, 'Inventario físico');

    expect($variant->fresh()->stock_quantity)->toBe(7)
        ->and($movement->type)->toBe(InventoryMovementType::ADJUSTMENT)
        ->and($movement->quantity)->toBe(-18)
        ->and($movement->stock_after)->toBe(7);
});

it('refuses a movement of no units', function () {
    $variant = Product::factory()->create()->defaultVariant();

    expect(fn () => app(InventoryService::class)->reserve($variant, 0))
        ->toThrow(SivarCartException::class);
});

it('writes every movement in the ledger of the variant', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $inventory = app(InventoryService::class);

    $inventory->reserve($variant, 2);
    $inventory->sell($variant, 2);

    expect($variant->fresh()->inventoryMovements)->toHaveCount(2)
        ->and($variant->inventoryMovements()->orderBy('id')->pluck('type')->all())
        ->toBe([InventoryMovementType::RESERVATION, InventoryMovementType::SALE]);
});
