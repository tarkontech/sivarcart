<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Enums\CatalogSort;
use SivarcartCore\SivarcartCore\Enums\CouponType;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Enums\InventoryMovementType;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentMethodType;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentTransactionStatus;
use SivarcartCore\SivarcartCore\Enums\ProductOptionType;
use SivarcartCore\SivarcartCore\Enums\ProductStatus;
use SivarcartCore\SivarcartCore\Enums\ProductType;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;

/**
 * The label of every case of every enum, in Spanish and in English.
 *
 * The Spanish text is the one that the enums carried before the labels moved
 * to the language file: it is the text that the store shows by default, and
 * the English one is what an application in English shows instead.
 */
$labels = [
    [CatalogSort::class, 'recent', 'Más recientes', 'Most recent'],
    [CatalogSort::class, 'price_asc', 'Precio de menor a mayor', 'Price from low to high'],
    [CatalogSort::class, 'price_desc', 'Precio de mayor a menor', 'Price from high to low'],
    [CatalogSort::class, 'name', 'Nombre', 'Name'],

    [CouponType::class, 'fixed', 'Monto fijo', 'Fixed amount'],
    [CouponType::class, 'percentage', 'Porcentaje', 'Percentage'],
    [CouponType::class, 'free_shipping', 'Envío gratis', 'Free shipping'],

    [CustomerDocumentType::class, 'nit', 'NIT', 'NIT'],
    [CustomerDocumentType::class, 'ruc', 'RUC', 'RUC'],
    [CustomerDocumentType::class, 'dui', 'DUI', 'DUI'],
    [CustomerDocumentType::class, 'pasaporte', 'Pasaporte', 'Passport'],
    [CustomerDocumentType::class, 'otro', 'Otro', 'Other'],

    [DocumentSequenceType::class, 'order', 'Orden', 'Order'],
    [DocumentSequenceType::class, 'payment', 'Pago', 'Payment'],

    [InventoryMovementType::class, 'sale', 'Venta', 'Sale'],
    [InventoryMovementType::class, 'restock', 'Reposición', 'Restock'],
    [InventoryMovementType::class, 'adjustment', 'Ajuste', 'Adjustment'],
    [InventoryMovementType::class, 'return', 'Devolución', 'Return'],
    [InventoryMovementType::class, 'reservation', 'Reserva', 'Reservation'],
    [InventoryMovementType::class, 'release', 'Liberación', 'Release'],

    [OrderHistoryAxis::class, 'status', 'Estado de la orden', 'Order status'],
    [OrderHistoryAxis::class, 'payment', 'Estado del pago', 'Payment status'],

    [OrderStatus::class, 'pending', 'Pendiente', 'Pending'],
    [OrderStatus::class, 'processing', 'En preparación', 'Processing'],
    [OrderStatus::class, 'shipped', 'Enviada', 'Shipped'],
    [OrderStatus::class, 'delivered', 'Entregada', 'Delivered'],
    [OrderStatus::class, 'returned', 'Devuelta', 'Returned'],
    [OrderStatus::class, 'cancelled', 'Cancelada', 'Cancelled'],
    [OrderStatus::class, 'refunded', 'Reembolsada', 'Refunded'],

    [PaymentMethodType::class, 'online', 'Pago en línea', 'Online payment'],
    [PaymentMethodType::class, 'offline', 'Pago fuera de línea', 'Offline payment'],

    [PaymentRecordStatus::class, 'pending', 'Pendiente', 'Pending'],
    [PaymentRecordStatus::class, 'processing', 'Procesando', 'Processing'],
    [PaymentRecordStatus::class, 'paid', 'Pagado', 'Paid'],
    [PaymentRecordStatus::class, 'failed', 'Fallido', 'Failed'],
    [PaymentRecordStatus::class, 'cancelled', 'Cancelado', 'Cancelled'],
    [PaymentRecordStatus::class, 'refunded', 'Reembolsado', 'Refunded'],
    [PaymentRecordStatus::class, 'partially_refunded', 'Reembolso parcial', 'Partially refunded'],

    [PaymentStatus::class, 'unpaid', 'Sin pagar', 'Unpaid'],
    [PaymentStatus::class, 'pending', 'Pago pendiente', 'Payment pending'],
    [PaymentStatus::class, 'paid', 'Pagada', 'Paid'],
    [PaymentStatus::class, 'failed', 'Pago fallido', 'Payment failed'],
    [PaymentStatus::class, 'cancelled', 'Pago cancelado', 'Payment cancelled'],
    [PaymentStatus::class, 'refunded', 'Reembolsada', 'Refunded'],
    [PaymentStatus::class, 'partially_refunded', 'Reembolso parcial', 'Partially refunded'],

    [PaymentTransactionStatus::class, 'pending', 'Pendiente', 'Pending'],
    [PaymentTransactionStatus::class, 'approved', 'Aprobada', 'Approved'],
    [PaymentTransactionStatus::class, 'rejected', 'Rechazada', 'Rejected'],
    [PaymentTransactionStatus::class, 'failed', 'Fallida', 'Failed'],

    [ProductOptionType::class, 'text', 'Texto', 'Text'],
    [ProductOptionType::class, 'select', 'Selección', 'Select'],
    [ProductOptionType::class, 'color', 'Color', 'Color'],

    [ProductStatus::class, 'draft', 'Borrador', 'Draft'],
    [ProductStatus::class, 'published', 'Publicado', 'Published'],
    [ProductStatus::class, 'archived', 'Archivado', 'Archived'],

    [ProductType::class, 'simple', 'Producto simple', 'Simple product'],
    [ProductType::class, 'variable', 'Producto con variantes', 'Product with variants'],

    [ShippingMethodType::class, 'flat_rate', 'Tarifa fija', 'Flat rate'],
    [ShippingMethodType::class, 'free', 'Gratis', 'Free'],
    [ShippingMethodType::class, 'pickup', 'Recoger en tienda', 'Store pickup'],
];

/**
 * The enums that the labels cover.
 */
$enums = array_values(array_unique(array_column($labels, 0)));

it('labels every case in the language of the application', function (string $enum, string $value, string $spanish, string $english) {
    app()->setLocale('es');

    expect($enum::from($value)->label())->toBe($spanish);

    app()->setLocale('en');

    expect($enum::from($value)->label())->toBe($english);
})->with($labels);

it('has a label for every case in every language file', function (string $enum) {
    $english = Arr::dot(require __DIR__.'/../../lang/en/messages.php');
    $spanish = Arr::dot(require __DIR__.'/../../lang/es/messages.php');

    $group = Str::snake(class_basename($enum));

    foreach ($enum::cases() as $case) {
        $key = "{$group}.{$case->value}";

        expect($english)->toHaveKey($key);
        expect($spanish)->toHaveKey($key);
    }
})->with($enums);

it('never shows the key of the translation', function (string $enum) {
    app()->setLocale('es');

    foreach ($enum::cases() as $case) {
        expect($case->label())->not->toContain('sivarcart-core::');
    }
})->with($enums);

it('speaks the language of the store when the application uses es_SV', function () {
    app()->setLocale('es_SV');

    expect(OrderStatus::PENDING->label())->toBe('Pendiente');
    expect(PaymentStatus::UNPAID->label())->toBe('Sin pagar');
    expect(InventoryMovementType::SALE->label())->toBe('Venta');
});
