<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Models\DocumentSequence;

/**
 * Numbers the documents of the store.
 *
 * The numbers are consecutive per year: SIV-2026-00001, PAY-2026-00001.
 */
final class SequenceService
{
    public function __construct(private readonly StoreService $stores)
    {
        //
    }

    /**
     * The number of the next document of the given type.
     */
    public function next(DocumentSequenceType $type, string $prefix, ?string $period = null): string
    {
        $period ??= $this->period();

        $number = $this->increment($type, $period);

        $padding = (int) config('sivarcart-core.orders.number_padding', 5);

        return sprintf('%s-%s-%s', $prefix, $period, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
    }

    /**
     * Take the next number of the sequence, creating it when it does not exist yet.
     */
    private function increment(DocumentSequenceType $type, string $period): int
    {
        $sequence = DocumentSequence::query()
            ->where('type', $type->value)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            return $this->create($type, $period);
        }

        $sequence->number = $sequence->number + 1;
        $sequence->save();

        return $sequence->number;
    }

    /**
     * Create the sequence when another request did not create it first.
     */
    private function create(DocumentSequenceType $type, string $period): int
    {
        try {
            $sequence = DocumentSequence::query()->create([
                'type' => $type,
                'period' => $period,
                'number' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            $sequence = DocumentSequence::query()
                ->where('type', $type->value)
                ->where('period', $period)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->number = $sequence->number + 1;
            $sequence->save();
        }

        return $sequence->number;
    }

    /**
     * The year the documents of this period belong to.
     */
    private function period(): string
    {
        return now($this->stores->timezone())->format('Y');
    }
}
