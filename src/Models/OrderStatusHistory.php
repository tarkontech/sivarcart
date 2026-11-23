<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\OrderStatusHistoryFactory;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * A recorded change of an order.
 *
 * The change belongs to one axis: the lifecycle of the order or the state of
 * its payment. A row of the axis of the status fills "status" and
 * "previous_status"; a row of the axis of the payment fills "payment_status"
 * and "previous_payment_status".
 */
class OrderStatusHistory extends Model
{
    /**
     * The table does not keep a "updated_at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'sivarcart_order_status_histories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'axis',
        'status',
        'previous_status',
        'payment_status',
        'previous_payment_status',
        'note',
        'user_id',
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
            'axis' => OrderHistoryAxis::class,
            'status' => OrderStatus::class,
            'previous_status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'previous_payment_status' => PaymentStatus::class,
            'created_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * Limit the query to the changes of one axis.
     *
     * @param  Builder<OrderStatusHistory>  $query
     * @return Builder<OrderStatusHistory>
     */
    public function scopeForAxis(Builder $query, OrderHistoryAxis $axis): Builder
    {
        return $query->where('axis', $axis->value);
    }

    /**
     * The order this change belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The member of the staff who recorded the change.
     *
     * The staff are the users of the host application, so the relation
     * points at the model of the "users" provider of "config/auth.php".
     *
     * @return BelongsTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Auth::model(), 'user_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderStatusHistoryFactory
    {
        return OrderStatusHistoryFactory::new();
    }
}
