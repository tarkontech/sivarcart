<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Rmirandasv\Wompi\Enums\FormaPago;
use Rmirandasv\Wompi\Enums\ResultadoTransaccion;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Enums\PaymentTransactionStatus;
use SivarcartCore\SivarcartCore\Models\DocumentSequence;
use SivarcartCore\SivarcartCore\Models\Payment;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

it('stores the same webhook only once', function () {
    $payment = Payment::factory()->create();

    PaymentTransaction::factory()->create([
        'payment_id' => $payment->getKey(),
        'idempotency_key' => 'txn-123:attempt-1',
    ]);

    expect(fn () => PaymentTransaction::factory()->create([
        'payment_id' => $payment->getKey(),
        'idempotency_key' => 'txn-123:attempt-1',
    ]))->toThrow(QueryException::class);

    expect($payment->transactions()->count())->toBe(1);
});

it('keeps the document counters unique per type and period', function () {
    DocumentSequence::factory()->create([
        'type' => DocumentSequenceType::ORDER,
        'period' => '2026',
    ]);

    expect(fn () => DocumentSequence::factory()->create([
        'type' => DocumentSequenceType::ORDER,
        'period' => '2026',
    ]))->toThrow(QueryException::class);
});

it('does not let two checkouts take the last unit', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 1]);

    $first = ProductVariant::query()
        ->whereKey($variant->getKey())
        ->where('stock_quantity', '>=', 1)
        ->decrement('stock_quantity', 1);

    $second = ProductVariant::query()
        ->whereKey($variant->getKey())
        ->where('stock_quantity', '>=', 1)
        ->decrement('stock_quantity', 1);

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect($variant->fresh()->stock_quantity)->toBe(0);
});

it('never considers a test transaction a successful payment', function () {
    $test = PaymentTransaction::factory()->testTransaction()->create();

    $real = PaymentTransaction::factory()->create();

    expect($test->is_production)->toBeFalse();
    expect($test->isTestTransaction())->toBeTrue();
    expect($test->isSuccessful())->toBeFalse();

    expect($real->isSuccessful())->toBeTrue();
});

it('reads the payment method and the result of a Wompi transaction', function () {
    $transaction = PaymentTransaction::factory()->create([
        'payment_method' => FormaPago::TARJETA_CREDITO_DEBITO,
        'result_code' => ResultadoTransaccion::APROBADA,
    ]);

    expect($transaction->payment_method)->toBe(FormaPago::TARJETA_CREDITO_DEBITO);
    expect($transaction->result_code)->toBe(ResultadoTransaccion::APROBADA);
    expect($transaction->status)->toBe(PaymentTransactionStatus::APPROVED);
});

it('survives a payment method Wompi did not document', function () {
    $transaction = PaymentTransaction::factory()->create([
        'payment_method' => 'UnMetodoNuevo',
    ]);

    expect($transaction->fresh()->payment_method)->toBeNull();
});
