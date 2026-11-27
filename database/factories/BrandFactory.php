<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Models\Brand;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Brand>
     */
    protected $model = Brand::class;

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
            'description' => fake()->sentence(),
            'logo_path' => null,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
