<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tarkontech\SivarCart\Enums\OrderHistoryAxis;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Enums\PaymentStatus;
use Tarkontech\SivarCart\Models\Order;

/**
 * The consolidation of the axes of the order.
 *
 * The migration is applied by the test suite with the rest of the migrations,
 * so this test runs it again on a database that still has the old shape: that
 * is the only way to check that the orders and the rows of the history that
 * were written before the consolidation survive it.
 */
it('folds the old axes into the new ones', function () {
    config()->set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    $legacy = DB::connection('legacy');

    $legacy->getSchemaBuilder()->create('sivarcart_orders', function (Blueprint $table): void {
        $table->id();
        $table->string('status', 32)->default('pending');
        $table->string('payment_status', 32)->default('unpaid');
        $table->string('fulfillment_status', 32)->default('unfulfilled');
    });

    $legacy->getSchemaBuilder()->create('sivarcart_order_status_histories', function (Blueprint $table): void {
        $table->id();
        $table->string('status', 32);
        $table->string('previous_status', 32)->nullable();
    });

    $legacy->table('sivarcart_orders')->insert([
        // A paid order that was not prepared yet.
        ['id' => 1, 'status' => 'paid', 'payment_status' => 'paid', 'fulfillment_status' => 'unfulfilled'],
        // A paid order that was already shipped.
        ['id' => 2, 'status' => 'paid', 'payment_status' => 'paid', 'fulfillment_status' => 'shipped'],
        // An order that was shipped without being paid.
        ['id' => 3, 'status' => 'pending', 'payment_status' => 'unpaid', 'fulfillment_status' => 'shipped'],
        // An order whose status already says more than its fulfillment.
        ['id' => 4, 'status' => 'delivered', 'payment_status' => 'paid', 'fulfillment_status' => 'unfulfilled'],
    ]);

    $legacy->table('sivarcart_order_status_histories')->insert([
        // The row that recorded the payment of the order.
        ['id' => 1, 'status' => 'paid', 'previous_status' => 'pending'],
        // The row that recorded a change of the order that started at paid.
        ['id' => 2, 'status' => 'cancelled', 'previous_status' => 'paid'],
        // A row that needs no change.
        ['id' => 3, 'status' => 'shipped', 'previous_status' => 'pending'],
    ]);

    $default = DB::getDefaultConnection();

    DB::setDefaultConnection('legacy');
    Schema::clearResolvedInstance('db.schema');

    try {
        $migration = require __DIR__.'/../../database/migrations/2026_01_01_000031_consolidate_order_status_axes.php';

        $migration->up();
    } finally {
        DB::setDefaultConnection($default);
        Schema::clearResolvedInstance('db.schema');
    }

    $orders = $legacy->table('sivarcart_orders')->orderBy('id')->pluck('status', 'id');

    expect($orders[1])->toBe('pending')
        ->and($orders[2])->toBe('shipped')
        ->and($orders[3])->toBe('shipped')
        ->and($orders[4])->toBe('delivered');

    expect($legacy->getSchemaBuilder()->hasColumn('sivarcart_orders', 'fulfillment_status'))->toBeFalse();

    $history = $legacy->table('sivarcart_order_status_histories')->orderBy('id')->get();

    expect($history[0]->axis)->toBe('payment')
        ->and($history[0]->status)->toBeNull()
        ->and($history[0]->payment_status)->toBe('paid')
        ->and($history[0]->previous_payment_status)->toBe('unpaid');

    expect($history[1]->axis)->toBe('status')
        ->and($history[1]->status)->toBe('cancelled')
        ->and($history[1]->previous_status)->toBe('pending');

    expect($history[2]->axis)->toBe('status')
        ->and($history[2]->status)->toBe('shipped')
        ->and($history[2]->previous_status)->toBe('pending');
});

it('reads the rows of the history that the consolidation left behind', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $order->transitionPaymentTo(PaymentStatus::PAID);
    $order->transitionTo(OrderStatus::SHIPPED);

    $history = $order->fresh()->statusHistories;

    expect($history)->toHaveCount(2);

    foreach ($history as $row) {
        expect($row->axis)->toBeInstanceOf(OrderHistoryAxis::class);
    }
});
