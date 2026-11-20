<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates every table of the data model', function () {
    $tables = [
        'sivarcart_stores',
        'sivarcart_settings',
        'sivarcart_departments',
        'sivarcart_document_sequences',
        'sivarcart_store_roles',
        'sivarcart_store_role_user',
        'sivarcart_customers',
        'sivarcart_customer_addresses',
        'sivarcart_categories',
        'sivarcart_category_product',
        'sivarcart_brands',
        'sivarcart_taxes',
        'sivarcart_products',
        'sivarcart_product_options',
        'sivarcart_product_option_values',
        'sivarcart_product_variants',
        'sivarcart_product_variant_values',
        'sivarcart_product_images',
        'sivarcart_inventory_movements',
        'sivarcart_carts',
        'sivarcart_cart_items',
        'sivarcart_coupons',
        'sivarcart_shipping_methods',
        'sivarcart_payment_methods',
        'sivarcart_orders',
        'sivarcart_order_items',
        'sivarcart_order_status_histories',
        'sivarcart_payments',
        'sivarcart_payment_transactions',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("The table [{$table}] was not created.");
    }

    expect($tables)->toHaveCount(29);
});

it('stores money with two decimals and tax rates with four', function () {
    $columns = collect(Schema::getColumns('sivarcart_product_variants'))->keyBy('name');

    // SQLite compiles a decimal column as "numeric" and does not keep the
    // precision and the scale, so they are only checked when the driver
    // reports them.
    expect(in_array($columns['price']['type'], ['decimal', 'numeric'], true))->toBeTrue();

    if (array_key_exists('precision', $columns['price'])) {
        expect((int) $columns['price']['precision'])->toBe(12);
        expect((int) $columns['price']['scale'])->toBe(2);
    }

    $orderItems = collect(Schema::getColumns('sivarcart_order_items'))->keyBy('name');

    expect(in_array($orderItems['tax_rate']['type'], ['decimal', 'numeric'], true))->toBeTrue();

    if (array_key_exists('precision', $orderItems['tax_rate'])) {
        expect((int) $orderItems['tax_rate']['precision'])->toBe(5);
        expect((int) $orderItems['tax_rate']['scale'])->toBe(4);
    }
});

it('keeps the identifiers that must not repeat unique', function () {
    $expectations = [
        'sivarcart_payment_transactions' => ['idempotency_key'],
        'sivarcart_orders' => ['order_number', 'reference'],
        'sivarcart_payments' => ['reference'],
        'sivarcart_customers' => ['email'],
        'sivarcart_coupons' => ['code'],
        'sivarcart_payment_methods' => ['code'],
        'sivarcart_shipping_methods' => ['code'],
        'sivarcart_document_sequences' => ['type', 'period'],
    ];

    foreach ($expectations as $table => $columns) {
        $indexes = collect(Schema::getIndexes($table))
            ->filter(fn (array $index): bool => ! empty($index['unique']))
            ->flatMap(fn (array $index): array => $index['columns']);

        foreach ($columns as $column) {
            expect($indexes->contains($column))->toBeTrue("The column [{$table}.{$column}] is not unique.");
        }
    }
});

it('soft deletes only the resources of the store', function () {
    $softDeleted = [
        'sivarcart_customers',
        'sivarcart_categories',
        'sivarcart_products',
        'sivarcart_product_variants',
        'sivarcart_coupons',
        'sivarcart_payment_methods',
    ];

    foreach ($softDeleted as $table) {
        expect(Schema::hasColumn($table, 'deleted_at'))->toBeTrue("The table [{$table}] is not soft deleted.");
    }

    $never = [
        'sivarcart_orders',
        'sivarcart_order_items',
        'sivarcart_payments',
        'sivarcart_payment_transactions',
        'sivarcart_inventory_movements',
        'sivarcart_carts',
        'sivarcart_cart_items',
    ];

    foreach ($never as $table) {
        expect(Schema::hasColumn($table, 'deleted_at'))->toBeFalse("The table [{$table}] must not be soft deleted.");
    }
});

it('keeps the stock ledger and the order history append only', function () {
    expect(Schema::hasColumn('sivarcart_inventory_movements', 'updated_at'))->toBeFalse();
    expect(Schema::hasColumn('sivarcart_order_status_histories', 'updated_at'))->toBeFalse();
});
