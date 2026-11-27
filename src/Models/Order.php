<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tarkontech\SivarCart\Database\Factories\OrderFactory;
use Tarkontech\SivarCart\Enums\OrderHistoryAxis;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Enums\PaymentStatus;
use Tarkontech\SivarCart\Exceptions\InvalidOrderStatusTransition;
use Tarkontech\SivarCart\Exceptions\InvalidPaymentStatusTransition;
use Tarkontech\SivarCart\Models\Concerns\HasUuidColumn;

/**
 * An order placed by a shopper.
 *
 * The order keeps a snapshot of what was bought: the items, the prices and
 * the tax rate are frozen here, so editing the catalog never rewrites the
 * history of an order that was already placed.
 */
class Order extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_orders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'reference',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'total',
        'tax_inclusive',
        'coupon_code',
        'shipping_address',
        'billing_address',
        'shipping_method_code',
        'shipping_method_name',
        'customer_note',
        'placed_at',
        'paid_at',
        'cancelled_at',
        'fulfilled_at',
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
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * The customer who placed the order.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The items of the order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The payments of the order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The status changes of the order.
     *
     * The timeline holds the changes of both axes: the lifecycle of the order
     * and the state of its payment. The "forAxis" scope of the history tells
     * them apart.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Move the order to another status and record the change.
     *
     * @throws InvalidOrderStatusTransition
     */
    public function transitionTo(OrderStatus $status, ?Authenticatable $user = null, ?string $note = null): void
    {
        $previous = $this->status;

        if (! $previous->canTransitionTo($status)) {
            throw InvalidOrderStatusTransition::between($previous, $status);
        }

        $attributes = ['status' => $status];

        if ($status === OrderStatus::CANCELLED && $this->cancelled_at === null) {
            $attributes['cancelled_at'] = now();
        }

        if (in_array($status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED], true) && $this->fulfilled_at === null) {
            $attributes['fulfilled_at'] = now();
        }

        $this->fill($attributes)->save();

        $this->statusHistories()->create([
            'axis' => OrderHistoryAxis::STATUS,
            'status' => $status,
            'previous_status' => $previous,
            'note' => $note,
            'user_id' => $user?->getKey(),
        ]);
    }

    /**
     * Move the payment of the order to another state and record the change.
     *
     * The payment is an axis of its own: it does not follow the lifecycle of
     * the order, so an order can be delivered and still be unpaid. The change
     * does nothing when the payment is already in that state, because the
     * same payment can be confirmed twice.
     *
     * @throws InvalidPaymentStatusTransition
     */
    public function transitionPaymentTo(PaymentStatus $status, ?Authenticatable $user = null, ?string $note = null): void
    {
        $previous = $this->payment_status;

        if ($previous === $status) {
            return;
        }

        if (! $previous->canTransitionTo($status)) {
            throw InvalidPaymentStatusTransition::between($previous, $status);
        }

        $attributes = ['payment_status' => $status];

        if ($status === PaymentStatus::PAID && $this->paid_at === null) {
            $attributes['paid_at'] = now();
        }

        $this->fill($attributes)->save();

        $this->statusHistories()->create([
            'axis' => OrderHistoryAxis::PAYMENT,
            'payment_status' => $status,
            'previous_payment_status' => $previous,
            'note' => $note,
            'user_id' => $user?->getKey(),
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
