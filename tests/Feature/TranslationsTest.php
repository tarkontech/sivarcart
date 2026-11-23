<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;

it('has the same keys in every language', function () {
    $english = Arr::dot(require __DIR__.'/../../lang/en/messages.php');
    $spanish = Arr::dot(require __DIR__.'/../../lang/es/messages.php');

    expect(array_keys($english))->toEqual(array_keys($spanish));
});

it('has every key the templates use', function () {
    foreach (OrderNotification::cases() as $notification) {
        foreach (['label', 'subject', 'greeting', 'body', 'cta', 'footer'] as $key) {
            $translation = "sivarcart-core::messages.order_notifications.{$notification->value}.{$key}";

            expect(trans($translation))->not->toBe($translation);
        }
    }
});

it('has the headers of the table of the items', function () {
    foreach (['product', 'quantity', 'price'] as $key) {
        $translation = "sivarcart-core::messages.order_notifications.table.{$key}";

        expect(trans($translation))->not->toBe($translation);
    }
});

it('speaks the language of the application', function () {
    app()->setLocale('es');

    expect(trans('sivarcart-core::messages.order_notifications.order_placed.label'))
        ->toBe('Pedido recibido');
});

it('speaks the language of the store when the application uses es_SV', function () {
    app()->setLocale('es_SV');

    expect(trans('sivarcart-core::messages.order_notifications.order_placed.label'))
        ->toBe('Pedido recibido');
});

it('serves the same texts for es_SV and for es', function () {
    $spanish = require __DIR__.'/../../lang/es/messages.php';
    $salvadoran = require __DIR__.'/../../lang/es_SV/messages.php';

    expect($salvadoran)->toEqual($spanish);
});

it('labels every notification', function () {
    foreach (OrderNotification::cases() as $notification) {
        expect($notification->label())->not->toBeEmpty();
    }
});
