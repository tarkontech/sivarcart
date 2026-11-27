<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Tests\TestCase;

/**
 * The store of the shopper, mounted under another prefix.
 */
final class StorefrontPrefixTest extends TestCase
{
    /**
     * Mount the store under another prefix.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sivarcart-core.storefront.prefix', 'shop');
    }

    public function test_it_mounts_the_store_under_the_prefix_of_the_configuration(): void
    {
        $route = Route::getRoutes()->getByName('sivarcart-core.storefront.home');

        $this->assertNotNull($route);
        $this->assertSame('shop', $route->uri());
    }

    public function test_it_answers_under_the_prefix_of_the_configuration(): void
    {
        $this->get('/shop')->assertOk();

        $this->get('/tienda')->assertNotFound();
    }
}
