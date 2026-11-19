<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\CustomerAddress;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomerAddress>
     */
    protected $model = CustomerAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => CustomerFactory::new(),
            'label' => 'Casa',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('7#######'),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'department_id' => null,
            'municipality' => fake()->city(),
            'city' => fake()->city(),
            'reference' => fake()->sentence(),
            'postal_code' => fake()->numerify('####'),
            'latitude' => null,
            'longitude' => null,
            'is_default' => true,
            'is_billing' => false,
        ];
    }
}
