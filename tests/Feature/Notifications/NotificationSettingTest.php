<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Actions\ShipOrder;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\NotificationSetting;
use SivarcartCore\SivarcartCore\Notifications\OrderPlacedNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderShippedNotification;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('sends the notification when the store has no row saved', function () {
    Notification::fake();

    $scenario = CheckoutScenario::guest();

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(OrderPlacedNotification::class);
});

it('does not send the notification the merchant turned off', function () {
    Notification::fake();

    NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_PLACED,
        'is_enabled' => false,
    ]);

    $scenario = CheckoutScenario::guest();

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertNothingSent();
});

it('sends the notification the merchant turned on', function () {
    Notification::fake();

    NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_PLACED,
        'is_enabled' => true,
    ]);

    $scenario = CheckoutScenario::guest();

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(OrderPlacedNotification::class);
});

it('sends nothing when the package is turned off', function () {
    Notification::fake();

    config()->set('sivarcart-core.notifications.enabled', false);

    NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_PLACED,
        'is_enabled' => true,
    ]);

    $scenario = CheckoutScenario::guest();

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertNothingSent();
});

it('only turns off the notification the merchant turned off', function () {
    Notification::fake();

    NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_PLACED,
        'is_enabled' => false,
    ]);

    $scenario = CheckoutScenario::guest();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    app(ShipOrder::class)->execute($order);

    Notification::assertSentOnDemand(OrderShippedNotification::class);
    Notification::assertNotSentTo(new AnonymousNotifiable, OrderPlacedNotification::class);
});

it('casts the notification to the enum', function () {
    $setting = NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_SHIPPED,
    ]);

    expect($setting->notification)->toBe(OrderNotification::ORDER_SHIPPED)
        ->and($setting->is_enabled)->toBeTrue();
});

it('holds one row per notification', function () {
    NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_SHIPPED,
    ]);

    expect(fn () => NotificationSetting::factory()->create([
        'notification' => OrderNotification::ORDER_SHIPPED,
    ]))->toThrow(QueryException::class);
});
