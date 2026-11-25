<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Models\InventoryMovement;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Services\InventoryService;

/**
 * Puts units back into the stock of a variant.
 *
 * The command is how the staff of the store restock a product and correct a
 * count. The movement is written in the ledger of the stock, so the change is
 * explained like every other movement of the store.
 *
 * The movement happens inside a transaction: the stock of the variant and the
 * entry of the ledger are written together or not at all.
 */
class RestockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart:restock
        {variant : The SKU of the variant}
        {quantity? : The units that go back into the stock}
        {--stock= : The exact stock the variant has to have}
        {--reason= : Why the stock moves}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put units back into the stock of a variant and record the movement';

    public function __construct(
        private readonly InventoryService $inventory,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sku = trim((string) $this->argument('variant'));

        $variant = ProductVariant::query()
            ->where('sku', $sku)
            ->first();

        if ($variant === null) {
            $this->components->error(sprintf('The variant "%s" does not exist.', $sku));

            return self::FAILURE;
        }

        $stock = $this->option('stock');
        $quantity = $this->argument('quantity');

        if ($stock === null && $quantity === null) {
            $this->components->error('Tell how many units go back into the stock, or the stock the variant has to have.');

            return self::FAILURE;
        }

        if ($stock !== null && (int) $stock < 0) {
            $this->components->error('The stock of a variant cannot be negative.');

            return self::FAILURE;
        }

        if ($quantity !== null && (int) $quantity < 1) {
            $this->components->error('The units that go back into the stock have to be more than zero.');

            return self::FAILURE;
        }

        $movement = DB::transaction(function () use ($variant, $stock, $quantity): InventoryMovement {
            if ($stock !== null) {
                return $this->inventory->adjust($variant, (int) $stock, $this->reason());
            }

            return $this->inventory->restock($variant, (int) $quantity, $this->reason());
        });

        $this->components->info(sprintf(
            'The stock of "%s" is %d units (%s).',
            $sku,
            $movement->stock_after,
            $movement->type->value,
        ));

        return self::SUCCESS;
    }

    /**
     * Why the stock moves, when the command was told.
     */
    private function reason(): ?string
    {
        $reason = $this->option('reason');

        if (! is_string($reason)) {
            return null;
        }

        $reason = trim($reason);

        return $reason === '' ? null : $reason;
    }
}
