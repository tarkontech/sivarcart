<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Casts\WompiPaymentMethod;
use SivarcartCore\SivarcartCore\Casts\WompiTransactionResult;
use SivarcartCore\SivarcartCore\Database\Factories\PaymentTransactionFactory;
use SivarcartCore\SivarcartCore\Enums\PaymentTransactionStatus;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A single transaction reported by a payment gateway.
 *
 * The rows are append only. Wompi retries the delivery of a webhook when it
 * does not get a 2xx, so the same payment can arrive more than once: the
 * idempotency key has a unique index, which makes a repeated delivery a no-op
 * instead of a double charge.
 */
class PaymentTransaction extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_payment_transactions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payment_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_payment_attempt_id',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'is_approved',
        'is_production',
        'result_code',
        'payment_method',
        'authorization_code',
        'merchant_reference',
        'occurred_at',
        'raw_payload',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentTransactionStatus::class,
            'amount' => 'decimal:2',
            'is_approved' => 'boolean',
            'is_production' => 'boolean',
            'result_code' => WompiTransactionResult::class,
            'payment_method' => WompiPaymentMethod::class,
            'occurred_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    /**
     * The payment this transaction belongs to.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Whether the transaction was approved and was a real charge.
     *
     * A test transaction is never a successful payment: the customer was never
     * charged, so the order must not be marked as paid.
     */
    public function isSuccessful(): bool
    {
        return $this->is_approved && $this->is_production;
    }

    /**
     * Whether the transaction was a test transaction.
     */
    public function isTestTransaction(): bool
    {
        return ! $this->is_production;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentTransactionFactory
    {
        return PaymentTransactionFactory::new();
    }
}
