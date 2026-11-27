<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Enums\PaymentRecordStatus;
use Tarkontech\SivarCart\Enums\PaymentStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;
use Tarkontech\SivarCart\Models\StoreRole;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
 */
function staffOfOrders(array $permissions = ['*']): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

/**
 * An order that was paid and can be refunded.
 */
function paidOrder(): Order
{
    $order = Order::factory()->paid()->create(['total' => 100]);

    Payment::factory()->paid()->create([
        'order_id' => $order->getKey(),
        'amount' => 100,
    ]);

    return $order;
}

it('tells the staff how much of the payment can be refunded', function () {
    staffOfOrders();

    $order = paidOrder();

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertInertia(fn (Assert $page) => $page
            ->where('refundable', 100)
            ->where('canRefund', true),
        );
});

it('refunds an order of the store', function () {
    staffOfOrders();

    $order = paidOrder();

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/refund', [
        'amount' => '40',
        'reason' => 'Producto devuelto',
    ])->assertRedirect();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and((float) $order->payments->first()->fresh()->refunded_amount)->toBe(40.0);
});

it('refuses a refund bigger than what was paid', function () {
    staffOfOrders();

    $order = paidOrder();

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/refund', [
        'amount' => '500',
    ])->assertRedirect();

    expect((float) $order->payments->first()->fresh()->refunded_amount)->toBe(0.0);
});

it('registers that an order came back to the store', function () {
    staffOfOrders();

    $order = Order::factory()->create(['status' => OrderStatus::SHIPPED]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/return', [
        'reason' => 'El cliente lo devolvio',
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::RETURNED);
});

it('does not refund an order to the staff that cannot move it', function () {
    staffOfOrders(['orders.view']);

    $order = paidOrder();

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/refund', [
        'amount' => '40',
    ])->assertForbidden();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID)
        ->and($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::PAID);
});

it('does not offer the refund to the staff that cannot move the order', function () {
    staffOfOrders(['orders.view']);

    $order = paidOrder();

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertInertia(fn (Assert $page) => $page
            ->where('canRefund', false)
            ->where('canReturn', false),
        );
});
