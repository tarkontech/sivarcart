<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Tarkontech\SivarCart\Database\Factories\DocumentSequenceFactory;
use Tarkontech\SivarCart\Enums\DocumentSequenceType;

/**
 * The counter behind the human readable document numbers.
 *
 * Order and payment numbers are consecutive per period ("SIV-2026-00001"), so
 * they need a counter that survives concurrent writes.
 */
class DocumentSequence extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_document_sequences';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'period',
        'number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentSequenceType::class,
            'number' => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DocumentSequenceFactory
    {
        return DocumentSequenceFactory::new();
    }
}
