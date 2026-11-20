<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\OrderStatusHistoryFactory;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * A recorded change of the status of an order.
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
        'status',
        'previous_status',
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
            'status' => OrderStatus::class,
            'previous_status' => OrderStatus::class,
            'created_at' => 'datetime',
            'meta' => 'array',
        ];
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
