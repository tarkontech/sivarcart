<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Console\Commands;

use Illuminate\Console\Command;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\CartItem;

/**
 * Removes the carts that ran out of time and their items.
 *
 * The items are deleted first so the pruning also works on the installations
 * that turned the foreign keys off, where the cascade of the database does
 * not happen.
 */
class PruneCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart:prune-carts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the carts that expired and their items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = Cart::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        CartItem::query()
            ->whereIn('cart_id', (clone $expired)->select('id'))
            ->delete();

        $deleted = $expired->delete();

        $this->components->info("Deleted {$deleted} expired carts.");

        return self::SUCCESS;
    }
}
