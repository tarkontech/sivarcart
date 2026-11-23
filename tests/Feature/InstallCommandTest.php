<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Models\Tax;

it('installs the package in a single command', function () {
    $this->artisan('sivarcart-core:install')
        ->assertSuccessful();

    expect(File::exists(database_path('migrations').'/2026_01_01_000001_create_sivarcart_departments_table.php'))->toBeTrue();
});

it('creates the table of the notification settings', function () {
    $this->artisan('sivarcart-core:install')
        ->assertSuccessful();

    expect(Schema::hasTable('sivarcart_notification_settings'))->toBeTrue();
});

it('seeds the base data of a Salvadoran store', function () {
    $this->artisan('sivarcart-core:install')->assertSuccessful();

    expect(Department::query()->count())->toBe(14);
    expect(Department::query()->where('code', 'SS')->first()->name)->toBe('San Salvador');

    expect(Tax::query()->count())->toBe(3);
    expect((float) Tax::query()->where('code', 'iva-13')->first()->rate)->toBe(0.13);

    expect(StoreRole::query()->count())->toBe(3);
    expect(StoreRole::query()->where('slug', 'owner')->first()->permissions)->toBe(['*']);

    expect(Store::query()->count())->toBe(1);
    expect(Store::query()->first()->currency)->toBe('USD');
    expect(Store::query()->first()->department->code)->toBe('SS');

    expect(PaymentMethod::query()->count())->toBe(3);
    expect(PaymentMethod::query()->where('code', 'wompi')->first()->is_active)->toBeFalse();
    expect(PaymentMethod::query()->where('code', 'cash_on_delivery')->first()->is_active)->toBeTrue();

    expect(ShippingMethod::query()->count())->toBe(2);
});

it('can be run more than once without breaking anything', function () {
    $this->artisan('sivarcart-core:install')->assertSuccessful();
    $this->artisan('sivarcart-core:install')->assertSuccessful();

    expect(Department::query()->count())->toBe(14);
    expect(Store::query()->count())->toBe(1);
    expect(Tax::query()->count())->toBe(3);
});

it('can skip the base data', function () {
    $this->artisan('sivarcart-core:install', ['--no-seed' => true])->assertSuccessful();

    expect(Department::query()->count())->toBe(0);
});
