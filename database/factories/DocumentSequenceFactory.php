<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Models\DocumentSequence;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DocumentSequence>
     */
    protected $model = DocumentSequence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => DocumentSequenceType::ORDER,
            'period' => (string) now()->year,
            'number' => 0,
        ];
    }
}
