<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Tarkontech\SivarCart\Enums\CustomerDocumentType;
use Tarkontech\SivarCart\Rules\CustomerDocument;

it('accepts a DUI with the right check digit', function () {
    expect(CustomerDocument::check(CustomerDocumentType::DUI, '01234567-8'))->toBeNull()
        ->and(CustomerDocument::check(CustomerDocumentType::DUI, '00016297-5'))->toBeNull();
});

it('accepts a DUI without the dash', function () {
    expect(CustomerDocument::check(CustomerDocumentType::DUI, '012345678'))->toBeNull();
});

it('refuses a DUI with the wrong check digit', function () {
    expect(CustomerDocument::check(CustomerDocumentType::DUI, '01234567-9'))
        ->toBe('invalid_check_digit');
});

it('refuses a DUI with the wrong length', function () {
    expect(CustomerDocument::check(CustomerDocumentType::DUI, '0123456-8'))
        ->toBe('invalid_length');
});

it('refuses a DUI of zeros', function () {
    expect(CustomerDocument::check(CustomerDocumentType::DUI, '00000000-0'))
        ->toBe('invalid_format');
});

it('accepts a NIT of fourteen digits with the right check digit', function () {
    expect(CustomerDocument::check(CustomerDocumentType::NIT, '0614-050707-104-8'))->toBeNull()
        ->and(CustomerDocument::check(CustomerDocumentType::NIT, '06140507071048'))->toBeNull();
});

it('refuses a NIT with the wrong check digit', function () {
    expect(CustomerDocument::check(CustomerDocumentType::NIT, '0614-050707-104-9'))
        ->toBe('invalid_check_digit');
});

it('accepts a DUI as the tax id of a natural person', function () {
    expect(CustomerDocument::check(CustomerDocumentType::NIT, '01234567-8'))->toBeNull();
});

it('checks the RUC with the rules of the NIT', function () {
    expect(CustomerDocument::check(CustomerDocumentType::RUC, '0614-050707-104-8'))->toBeNull()
        ->and(CustomerDocument::check(CustomerDocumentType::RUC, '0614-050707-104-9'))
        ->toBe('invalid_check_digit');
});

it('accepts a passport of letters and digits', function () {
    expect(CustomerDocument::check(CustomerDocumentType::PASAPORTE, 'A1234567'))->toBeNull();
});

it('refuses a passport that is too short', function () {
    expect(CustomerDocument::check(CustomerDocumentType::PASAPORTE, 'A123'))
        ->toBe('invalid_length');
});

it('accepts any text as the other documents', function () {
    expect(CustomerDocument::check(CustomerDocumentType::OTRO, 'Licencia de conducir'))->toBeNull();
});

it('refuses the other documents that do not fit the column', function () {
    expect(CustomerDocument::check(CustomerDocumentType::OTRO, str_repeat('a', 33)))
        ->toBe('invalid_length');
});

it('fails the validation of a document that does not hold the format', function () {
    $validator = Validator::make(
        ['document_number' => '01234567-9'],
        ['document_number' => [new CustomerDocument(CustomerDocumentType::DUI)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('document_number'))
        ->toContain('DUI');
});

it('passes the validation of a document that holds the format', function () {
    $validator = Validator::make(
        ['document_number' => '01234567-8'],
        ['document_number' => [new CustomerDocument(CustomerDocumentType::DUI)]],
    );

    expect($validator->fails())->toBeFalse();
});

it('says nothing when the type of the document is not known', function () {
    $validator = Validator::make(
        ['document_number' => 'lo que sea'],
        ['document_number' => [new CustomerDocument]],
    );

    expect($validator->fails())->toBeFalse();
});
