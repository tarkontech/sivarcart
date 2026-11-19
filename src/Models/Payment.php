<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SivarcartCore\SivarcartCore\Database\Factories\PaymentFactory;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * The payment of an order.
 *
 * A payment collects the money; the transactions recorded against it are the
 * individual attempts made at the gateway.
 */
class Payment extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_payments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'payment_method_id',
        'method_code',
        'gateway',
        'reference',
        'status',
        'amount',
        'currency',
        'gateway_reference',
        'paid_at',
        'failed_at',
        'failure_reason',
        'refunded_amount',
        'attempt_count',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentRecordStatus::class,
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'attempt_count' => 'integer',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * The order this payment settles.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The method this payment was collected with.
     *
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * The gateway transactions of this payment.
     *
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * The amount that has not been refunded yet.
     */
    public function refundableAmount(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
