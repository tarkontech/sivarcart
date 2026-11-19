<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Models\DocumentSequence;
use SivarcartCore\SivarcartCore\Services\SequenceService;

it('numbers the documents consecutively', function () {
    $sequences = app(SequenceService::class);
    $year = now(config('app.timezone'))->format('Y');

    expect($sequences->next(DocumentSequenceType::ORDER, 'SIV'))->toBe('SIV-'.$year.'-00001')
        ->and($sequences->next(DocumentSequenceType::ORDER, 'SIV'))->toBe('SIV-'.$year.'-00002')
        ->and($sequences->next(DocumentSequenceType::ORDER, 'SIV'))->toBe('SIV-'.$year.'-00003');
});

it('keeps a counter of its own for every kind of document', function () {
    $sequences = app(SequenceService::class);
    $year = now(config('app.timezone'))->format('Y');

    expect($sequences->next(DocumentSequenceType::ORDER, 'SIV'))->toBe('SIV-'.$year.'-00001')
        ->and($sequences->next(DocumentSequenceType::PAYMENT, 'PAY'))->toBe('PAY-'.$year.'-00001')
        ->and($sequences->next(DocumentSequenceType::ORDER, 'SIV'))->toBe('SIV-'.$year.'-00002');
});

it('starts a new counter for every period', function () {
    $sequences = app(SequenceService::class);

    expect($sequences->next(DocumentSequenceType::ORDER, 'SIV', '2025'))->toBe('SIV-2025-00001')
        ->and($sequences->next(DocumentSequenceType::ORDER, 'SIV', '2025'))->toBe('SIV-2025-00002')
        ->and($sequences->next(DocumentSequenceType::ORDER, 'SIV', '2026'))->toBe('SIV-2026-00001');
});

it('pads the number with the configured amount of zeros', function () {
    config(['sivarcart-core.orders.number_padding' => 3]);

    expect(app(SequenceService::class)->next(DocumentSequenceType::ORDER, 'SIV', '2026'))
        ->toBe('SIV-2026-001');
});

it('keeps the counter in the table of the sequences', function () {
    $sequences = app(SequenceService::class);

    $sequences->next(DocumentSequenceType::ORDER, 'SIV', '2026');
    $sequences->next(DocumentSequenceType::ORDER, 'SIV', '2026');

    $sequence = DocumentSequence::query()
        ->where('type', DocumentSequenceType::ORDER->value)
        ->where('period', '2026')
        ->firstOrFail();

    expect($sequence->number)->toBe(2);
});
