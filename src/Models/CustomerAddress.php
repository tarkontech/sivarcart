<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tarkontech\SivarCart\Database\Factories\CustomerAddressFactory;

/**
 * An address a customer can deliver an order to.
 */
class CustomerAddress extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_customer_addresses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'label',
        'first_name',
        'last_name',
        'phone',
        'address_line1',
        'address_line2',
        'department_id',
        'municipality',
        'city',
        'reference',
        'postal_code',
        'latitude',
        'longitude',
        'is_default',
        'is_billing',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
            'is_billing' => 'boolean',
        ];
    }

    /**
     * The customer this address belongs to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The department this address is located in.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerAddressFactory
    {
        return CustomerAddressFactory::new();
    }
}
