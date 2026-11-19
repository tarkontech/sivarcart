<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use SivarcartCore\SivarcartCore\Enums\InventoryMovementType;
use SivarcartCore\SivarcartCore\Exceptions\InsufficientStock;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\InventoryMovement;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

/**
 * Moves the stock of the store and writes the ledger that explains it.
 *
 * The units of an order are reserved when the order is placed, they become a
 * sale when the order is paid and they are released when the order is
 * cancelled. The available stock never counts a reserved unit twice, so the
 * last unit of a product is never sold twice.
 */
final class InventoryService
{
    /**
     * Reserve units for an order that was just placed.
     */
    public function reserve(
        ProductVariant $variant,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $this->guardQuantity($quantity);

        $locked = $this->lock($variant);

        if ($locked->availableStock() < $quantity) {
            throw InsufficientStock::forVariant($locked, $quantity);
        }

        $locked->reserved_quantity = $locked->reserved_quantity + $quantity;
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::RESERVATION,
            $quantity,
            $locked->reserved_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Release units that were reserved for an order that was cancelled.
     */
    public function release(
        ProductVariant $variant,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $this->guardQuantity($quantity);

        $locked = $this->lock($variant);

        $locked->reserved_quantity = max(0, $locked->reserved_quantity - $quantity);
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::RELEASE,
            $quantity,
            $locked->reserved_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Turn reserved units into a sale.
     */
    public function sell(
        ProductVariant $variant,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $this->guardQuantity($quantity);

        $locked = $this->lock($variant);

        if ($locked->reserved_quantity < $quantity) {
            throw InsufficientStock::forVariant($locked, $quantity);
        }

        $locked->stock_quantity = $locked->stock_quantity - $quantity;
        $locked->reserved_quantity = $locked->reserved_quantity - $quantity;
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::SALE,
            -$quantity,
            $locked->stock_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Take the units of a refunded order back to the stock.
     */
    public function returnToStock(
        ProductVariant $variant,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $this->guardQuantity($quantity);

        $locked = $this->lock($variant);

        $locked->stock_quantity = $locked->stock_quantity + $quantity;
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::RETURN,
            $quantity,
            $locked->stock_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Put units back into the stock after a restock.
     */
    public function restock(
        ProductVariant $variant,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $this->guardQuantity($quantity);

        $locked = $this->lock($variant);

        $locked->stock_quantity = $locked->stock_quantity + $quantity;
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::RESTOCK,
            $quantity,
            $locked->stock_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Set the stock of a variant to an exact quantity.
     */
    public function adjust(
        ProductVariant $variant,
        int $stock,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        $locked = $this->lock($variant);

        $delta = max($stock, 0) - $locked->stock_quantity;

        $locked->stock_quantity = max($stock, 0);
        $locked->save();

        return $this->record(
            $locked,
            InventoryMovementType::ADJUSTMENT,
            $delta,
            $locked->stock_quantity,
            $reason,
            $referenceType,
            $referenceId,
        );
    }

    /**
     * Read the variant with the lock of the row, so two orders never take
     * the same unit.
     */
    private function lock(ProductVariant $variant): ProductVariant
    {
        return ProductVariant::query()
            ->whereKey($variant->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Write the entry of the ledger.
     */
    private function record(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        int $stockAfter,
        ?string $reason,
        ?string $referenceType,
        ?int $referenceId,
    ): InventoryMovement {
        return InventoryMovement::query()->create([
            'product_variant_id' => $variant->getKey(),
            'type' => $type,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'meta' => [
                'stock_quantity' => $variant->stock_quantity,
                'reserved_quantity' => $variant->reserved_quantity,
                'available' => $variant->availableStock(),
            ],
        ]);
    }

    /**
     * A movement always moves at least one unit.
     *
     * @throws SivarcartCoreException
     */
    private function guardQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new SivarcartCoreException('La cantidad de un movimiento de stock tiene que ser mayor que cero.');
        }
    }
}
