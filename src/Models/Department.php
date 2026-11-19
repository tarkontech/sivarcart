<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use SivarcartCore\SivarcartCore\Database\Factories\DepartmentFactory;

/**
 * A department of El Salvador.
 */
class Department extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_departments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'country_code',
        'position',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The customer addresses located in this department.
     *
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * The stores located in this department.
     *
     * @return HasMany<Store, $this>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
