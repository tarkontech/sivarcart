<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Tests\TestCase;

/**
 * The API of the store, turned off.
 */
final class RoutesDisabledTest extends TestCase
{
    /**
     * Turn the API of the store off.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sivarcart.api', [
            'enabled' => false,
            'prefix' => 'api/sivarcart',
            'middleware' => ['api'],
            'rate_limit' => 60,
        ]);
    }

    public function test_it_does_not_register_the_routes_of_the_api(): void
    {
        $this->assertFalse(Route::has('sivarcart.api.products.index'));
        $this->assertFalse(Route::has('sivarcart.api.cart.show'));
    }

    public function test_it_keeps_the_route_of_the_webhook_of_wompi(): void
    {
        config([
            'wompi.client_id' => 'client-de-prueba',
            'wompi.client_secret' => 'secreto-de-prueba',
        ]);

        $this->assertTrue(Route::has('sivarcart.wompi.webhook'));

        $this->postJson('/webhooks/wompi', [])->assertStatus(400);
    }
}
