<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rmirandasv\Wompi\Webhooks\WompiWebhook;
use Rmirandasv\Wompi\Webhooks\WompiWebhookHandler;
use Tarkontech\SivarCart\Actions\HandleWompiPayment;

/**
 * Receives the webhook Wompi sends when a payment is made.
 *
 * The signature of the request is validated by the middleware of the Wompi
 * package, which also leaves the normalized payload on the request. The
 * controller always answers with a 200: a payment that cannot be applied is
 * logged, but answering with an error would make Wompi deliver the same
 * webhook over and over.
 */
class WompiWebhookController extends Controller
{
    public function __construct(private readonly HandleWompiPayment $handlePayment)
    {
        //
    }

    /**
     * Apply the payment of the webhook.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $webhook = $request->attributes->get(WompiWebhookHandler::PAYLOAD_ATTRIBUTE);

        if ($webhook instanceof WompiWebhook) {
            $this->handlePayment->execute($webhook);
        }

        return response()->json(['status' => 'ok']);
    }
}
