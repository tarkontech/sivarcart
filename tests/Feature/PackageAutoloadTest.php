<?php

declare(strict_types=1);

it('ships its factories so the application that installs it can use them', function () {
    $composer = json_decode(
        (string) file_get_contents(__DIR__.'/../../composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $autoload = $composer['autoload']['psr-4'] ?? [];

    expect($autoload)->toHaveKey('SivarcartCore\SivarcartCore\Database\Factories\\')
        ->and($autoload['SivarcartCore\SivarcartCore\Database\Factories\\'])->toBe('database/factories/');
});

it('keeps the tests and the tools out of the production autoload', function () {
    $composer = json_decode(
        (string) file_get_contents(__DIR__.'/../../composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $autoload = $composer['autoload']['psr-4'] ?? [];

    expect($autoload)->not->toHaveKey('SivarcartCore\SivarcartCore\Tests\\')
        ->not->toHaveKey('Workbench\App\\');
});
