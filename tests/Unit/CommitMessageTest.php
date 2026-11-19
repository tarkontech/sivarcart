<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Tools\CommitMessage;

it('accepts a conventional commit', function () {
    expect(CommitMessage::validate('feat(orders): agrega el checkout de invitado'))->toBeNull();
});

it('accepts every allowed type', function (string $type) {
    expect(CommitMessage::validate($type.': describe el cambio'))->toBeNull();
})->with(CommitMessage::TYPES);

it('accepts a breaking change marker', function () {
    expect(CommitMessage::validate('feat(api)!: renombra el endpoint de ordenes'))->toBeNull();
});

it('accepts a message without a scope', function () {
    expect(CommitMessage::validate('fix: corrige el total de la orden'))->toBeNull();
});

it('rejects a message without a type', function () {
    expect(CommitMessage::validate('agrega el checkout de invitado'))->not->toBeNull();
});

it('rejects a type that is not allowed', function () {
    expect(CommitMessage::validate('wip: todavia no funciona'))->toContain('wip');
});

it('rejects a message without a description', function () {
    expect(CommitMessage::validate('feat: '))->not->toBeNull();
});

it('rejects a message longer than the maximum length', function () {
    $message = 'feat: '.str_repeat('a', CommitMessage::MAX_LENGTH);

    expect(CommitMessage::validate($message))->toContain('300');
});

it('accepts a message of exactly the maximum length', function () {
    $message = 'feat: '.str_repeat('a', CommitMessage::MAX_LENGTH - 6);

    expect(mb_strlen($message))->toBe(CommitMessage::MAX_LENGTH);
    expect(CommitMessage::validate($message))->toBeNull();
});

it('rejects a message with a body', function () {
    expect(CommitMessage::validate("feat: agrega el checkout\n\nCuerpo del mensaje"))->toContain('una sola línea');
});

it('rejects a message with a footer', function () {
    expect(CommitMessage::validate("feat: agrega el checkout\n\nRefs: #12"))->toContain('una sola línea');
});

it('rejects an empty message', function () {
    expect(CommitMessage::validate(''))->not->toBeNull();
    expect(CommitMessage::validate("  \n  \n"))->not->toBeNull();
});

it('accepts the commits git writes on its own', function (string $prefix) {
    expect(CommitMessage::validate($prefix.'feat: agrega el checkout'))->toBeNull();
})->with(CommitMessage::GENERATED_PREFIXES);

it('accepts the commits git writes on its own even when they are long', function () {
    $message = 'fixup! '.str_repeat('a', CommitMessage::MAX_LENGTH);

    expect(CommitMessage::validate($message))->toBeNull();
});
