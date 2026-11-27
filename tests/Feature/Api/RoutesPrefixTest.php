<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Tests\TestCase;

/**
 * The API of the store, mounted under another prefix.
 */
final class RoutesPrefixTest extends TestCase
{
    /**
     * Mount the API under another prefix.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sivarcart.api', [
            'enabled' => true,
            'prefix' => 'api/tienda',
            'middleware' => ['api'],
            'rate_limit' => 60,
        ]);
    }

    public function test_it_mounts_the_api_under_the_prefix_of_the_configuration(): void
    {
        $route = Route::getRoutes()->getByName('sivarcart.api.products.index');

        $this->assertNotNull($route);
        $this->assertSame('api/tienda/products', $route->uri());
    }

    public function test_it_answers_under_the_prefix_of_the_configuration(): void
    {
        $this->getJson('/api/tienda/products')->assertOk();

        $this->getJson('/api/sivarcart/products')->assertNotFound();
    }
}
