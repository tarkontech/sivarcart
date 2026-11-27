<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Tests\TestCase;

/**
 * The store of the shopper, turned off.
 *
 * The store and the store API are two ways of mounting the same store, y
 * conviven: turning the store off does not turn the API off.
 */
final class StorefrontDisabledTest extends TestCase
{
    /**
     * Turn the store of the shopper off.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sivarcart.storefront.enabled', false);
    }

    public function test_it_does_not_register_the_routes_of_the_store(): void
    {
        $this->assertFalse(Route::has('sivarcart.storefront.home'));
        $this->assertFalse(Route::has('sivarcart.storefront.cart.show'));
    }

    public function test_it_does_not_answer_the_pages_of_the_store(): void
    {
        $this->get('/tienda')->assertNotFound();
    }

    public function test_it_keeps_the_api_of_the_store(): void
    {
        $this->assertTrue(Route::has('sivarcart.api.products.index'));

        $this->getJson('/api/sivarcart/products')->assertOk();
    }
}
