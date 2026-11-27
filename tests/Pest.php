<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * A buyer of the store with a session and a store to buy in.
 *
 * The helper lives here and not in one of the files of the suite, because
 * the suite runs in parallel and every worker only loads the files it runs.
 */
function shopper(): Customer
{
    Store::factory()->create();

    $customer = Customer::factory()->create();

    test()->actingAs($customer, 'sivarcart');

    return $customer;
}
