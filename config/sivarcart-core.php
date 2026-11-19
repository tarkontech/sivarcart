<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Store defaults
    |--------------------------------------------------------------------------
    |
    | The values the store is created with the first time the package is
    | installed. They can be changed later from the administration panel.
    |
    */

    'store' => [
        'name' => 'SivarCart',
        'slug' => 'sivarcart',
        'legal_name' => null,
        'tax_id' => null,
        'email' => null,
        'phone' => null,
        'currency' => 'USD',
        'timezone' => 'America/El_Salvador',
        'locale' => 'es_SV',
        'prices_include_tax' => true,
        'default_tax_rate' => 0.13,
        'low_stock_threshold' => 5,
        'order_number_prefix' => 'SIV',
        'payment_number_prefix' => 'PAY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    |
    | The document numbers are consecutive per year: SIV-2026-00001.
    |
    */

    'orders' => [
        'number_padding' => 5,
        'reference_length' => 128,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'low_stock_threshold' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wompi
    |--------------------------------------------------------------------------
    |
    | The credentials themselves are stored per payment method, so a merchant
    | can configure them from the administration panel. They fall back to the
    | WOMPI_CLIENT_ID and WOMPI_CLIENT_SECRET environment variables of the
    | laravel-wompi package when the payment method has none.
    |
    */

    'wompi' => [
        'enabled' => true,
        'webhook_path' => 'webhooks/wompi',
        'redirect_path' => 'payments/wompi/return',
        'payment_link' => [
            'permitir_tarjeta_credito_debito' => true,
            'permitir_pago_con_punto_agricola' => true,
            'permitir_pago_en_cuotas_agricola' => false,
        ],
    ],

];
