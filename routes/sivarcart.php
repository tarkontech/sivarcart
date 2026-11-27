<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Http\Controllers\WompiWebhookController;

/*
|--------------------------------------------------------------------------
| Wompi Webhook
|--------------------------------------------------------------------------
|
| Wompi sends this route when a payment is made. The "wompi.webhook"
| middleware of the laravel-wompi package validates the signature of the
| request before the controller runs.
|
| The host application does not have to publish this route: the package
| registers it. It should not publish the route of the laravel-wompi package
| either, because both would answer the same path.
|
*/

Route::post(
    (string) config('sivarcart.wompi.webhook_path', 'webhooks/wompi'),
    WompiWebhookController::class,
)
    ->name('sivarcart.wompi.webhook')
    ->middleware(['wompi.webhook']);
