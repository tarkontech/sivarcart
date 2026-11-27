<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Payments;

use Illuminate\Contracts\Container\Container;
use Rmirandasv\Wompi\Contracts\WompiClientInterface;
use Rmirandasv\Wompi\DTOs\Requests\PaymentLinkRequestDTO;
use Rmirandasv\Wompi\WompiClient;
use Tarkontech\SivarCart\Contracts\PaymentGateway;
use Tarkontech\SivarCart\Data\PaymentLinkResult;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;

/**
 * Creates the Wompi payment link of an order.
 *
 * The merchant reference of the link is the reference of the payment, so the
 * webhook of Wompi finds the order without guessing.
 *
 * The client of Wompi is only built when a link is created: a store that does
 * not accept payments online can still use the rest of the package without
 * having the credentials of Wompi configured.
 */
final class WompiPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly Container $container)
    {
        //
    }

    /**
     * Create the payment link the shopper has to pay.
     */
    public function createPaymentLink(Order $order, Payment $payment): PaymentLinkResult
    {
        /** @var array<string, mixed> $formaPago */
        $formaPago = config('sivarcart.wompi.payment_link', []);

        $dto = new PaymentLinkRequestDTO(
            monto: (float) $payment->amount,
            identificadorEnlaceComercio: $payment->reference,
            nombreProducto: 'Orden '.$order->order_number,
            formaPago: $formaPago,
            configuracion: ['urlRedirect' => $this->redirectUrl()],
            datosAdicionales: ['order_number' => $order->order_number],
        );

        $response = $this->clientFor($payment)->createPaymentLink($dto);

        $reference = $response->get('idEnlace');
        $url = $response->get('urlEnlace') ?? $response->get('urlEnlaceLargo');

        return new PaymentLinkResult(
            reference: is_scalar($reference) ? (string) $reference : '',
            url: is_string($url) ? $url : null,
            raw: $response->toArray(),
        );
    }

    /**
     * The client to use, with the credentials of the payment method when it has its own.
     */
    private function clientFor(Payment $payment): WompiClientInterface
    {
        $credentials = $payment->method?->credentials;

        if (! is_array($credentials)) {
            return $this->client();
        }

        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;

        if (! is_string($clientId) || ! is_string($clientSecret) || $clientId === '' || $clientSecret === '') {
            return $this->client();
        }

        $cacheStore = config('wompi.token_cache.store');

        return new WompiClient(
            apiUrl: (string) config('wompi.api_url'),
            authUrl: (string) config('wompi.auth_url'),
            audience: (string) config('wompi.audience', 'wompi_api'),
            clientId: $clientId,
            clientSecret: $clientSecret,
            timeout: (int) config('wompi.timeout', 30),
            connectTimeout: (int) config('wompi.connect_timeout', 10),
            retry: (int) config('wompi.retry', 2),
            logPayloads: (bool) config('wompi.log_payloads', false),
            cacheStore: is_string($cacheStore) ? $cacheStore : null,
            ttlMargin: (int) config('wompi.token_cache.ttl_margin', 60),
        );
    }

    /**
     * The client of the package, built the first time a link is created.
     */
    private function client(): WompiClientInterface
    {
        return $this->container->make(WompiClientInterface::class);
    }

    /**
     * Where Wompi sends the shopper back once the payment is done.
     */
    private function redirectUrl(): string
    {
        $configured = config('sivarcart.wompi.redirect_url');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $base = rtrim((string) config('app.url'), '/');
        $path = ltrim((string) config('sivarcart.wompi.redirect_path', 'payments/wompi/return'), '/');

        return $base.'/'.$path;
    }
}
