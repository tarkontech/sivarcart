<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Models\Store;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Store>
     */
    protected $model = Store::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '-').'-'.Str::lower(Str::random(6)),
            'legal_name' => $name.' S.A. de C.V.',
            'tax_id' => fake()->numerify('########-#'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('7#######'),
            'whatsapp' => fake()->numerify('7#######'),
            'address_line1' => fake()->streetAddress(),
            'municipality' => fake()->city(),
            'city' => fake()->city(),
            'postal_code' => fake()->numerify('####'),
            'country_code' => 'SV',
            'phone_country_code' => '503',
            'currency' => 'USD',
            'timezone' => 'America/El_Salvador',
            'locale' => 'es_SV',
            'prices_include_tax' => true,
            'default_tax_rate' => 0.13,
            'low_stock_threshold' => 5,
            'order_number_prefix' => 'SIV',
            'payment_number_prefix' => 'PAY',
            'meta' => [],
            'is_active' => true,
        ];
    }
}
