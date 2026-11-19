<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Enums\CouponType;
use SivarcartCore\SivarcartCore\Exceptions\CouponNotApplicable;
use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\CouponService;

it('finds a coupon by its code', function () {
    $coupon = Coupon::factory()->create(['code' => 'BIENVENIDA']);

    expect(app(CouponService::class)->find('BIENVENIDA')?->is($coupon))->toBeTrue()
        ->and(app(CouponService::class)->find('NO-EXISTE'))->toBeNull();
});

it('takes a fixed amount off the subtotal', function () {
    $coupon = Coupon::factory()->create(['type' => CouponType::FIXED, 'value' => 7.50]);

    expect(app(CouponService::class)->discountFor($coupon, 100.00))->toBe(7.5);
});

it('takes a percentage off the subtotal', function () {
    $coupon = Coupon::factory()->percentage(15)->create();

    expect(app(CouponService::class)->discountFor($coupon, 100.00))->toBe(15.0)
        ->and(app(CouponService::class)->discountFor($coupon, 59.99))->toBe(9.0);
});

it('never takes more than the subtotal', function () {
    $coupon = Coupon::factory()->create(['type' => CouponType::FIXED, 'value' => 500]);

    expect(app(CouponService::class)->discountFor($coupon, 100.00))->toBe(100.0);
});

it('never takes more than the maximum amount of the coupon', function () {
    $coupon = Coupon::factory()->percentage(50)->create(['max_discount_amount' => 10]);

    expect(app(CouponService::class)->discountFor($coupon, 100.00))->toBe(10.0);
});

it('gives the shipping for free', function () {
    $coupon = Coupon::factory()->create(['type' => CouponType::FREE_SHIPPING, 'value' => 0]);

    expect(app(CouponService::class)->shippingDiscountFor($coupon, 7.50))->toBe(7.5)
        ->and(app(CouponService::class)->discountFor($coupon, 100.00))->toBe(0.0);
});

it('does not give the shipping for free with another kind of coupon', function () {
    $coupon = Coupon::factory()->percentage(10)->create();

    expect(app(CouponService::class)->shippingDiscountFor($coupon, 7.50))->toBe(0.0);
});

it('accepts a coupon that can still be used', function () {
    $coupon = Coupon::factory()->create();

    app(CouponService::class)->assertApplicable($coupon, 100.00);
})->throwsNoExceptions();

it('refuses a coupon that is not active', function () {
    $coupon = Coupon::factory()->create(['is_active' => false]);

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 100.00))
        ->toThrow(CouponNotApplicable::class);
});

it('refuses a coupon that already expired', function () {
    $coupon = Coupon::factory()->expired()->create();

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 100.00))
        ->toThrow(CouponNotApplicable::class);
});

it('refuses a coupon that has not started yet', function () {
    $coupon = Coupon::factory()->create(['starts_at' => now()->addDay()]);

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 100.00))
        ->toThrow(CouponNotApplicable::class);
});

it('refuses a coupon that was already used as many times as allowed', function () {
    $coupon = Coupon::factory()->create(['usage_limit' => 1, 'used_count' => 1]);

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 100.00))
        ->toThrow(CouponNotApplicable::class);
});

it('refuses a coupon below the minimum amount of the order', function () {
    $coupon = Coupon::factory()->create(['min_order_amount' => 50]);

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 49.99))
        ->toThrow(CouponNotApplicable::class, 'requiere una orden de al menos 50.00');
});

it('refuses a coupon the customer already used', function () {
    $coupon = Coupon::factory()->create(['per_customer_limit' => 1]);
    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->getKey(),
        'coupon_code' => $coupon->code,
    ]);

    expect(fn () => app(CouponService::class)->assertApplicable($coupon, 100.00, $customer->getKey()))
        ->toThrow(CouponNotApplicable::class);
});

it('accepts a coupon the customer did not use yet', function () {
    $coupon = Coupon::factory()->create(['per_customer_limit' => 1]);
    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->getKey(),
        'coupon_code' => 'OTRO-CUPON',
    ]);

    app(CouponService::class)->assertApplicable($coupon, 100.00, $customer->getKey());
})->throwsNoExceptions();
