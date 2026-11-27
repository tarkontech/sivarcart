<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Tests\Support;

use Rmirandasv\Wompi\Support\PayloadNormalizer;
use Rmirandasv\Wompi\Webhooks\WompiWebhook;

/**
 * Builds the payloads Wompi sends in its webhook.
 *
 * The merchant reference travels inside the "EnlacePago" object, the same way
 * Wompi sends it.
 */
final class WompiPayload
{
    /**
     * A payment that was approved.
     *
     * @param  bool  $production  Whether the transaction was a real charge.
     */
    public static function approved(
        string $reference,
        float $amount = 25.50,
        bool $production = true,
        string $transactionId = 'txn-1',
        string $attemptId = 'attempt-1',
    ): WompiWebhook {
        return PayloadNormalizer::normalize([
            'IdTransaccion' => $transactionId,
            'IdIntentoPago' => $attemptId,
            'EnlacePago' => [
                'IdentificadorEnlaceComercio' => $reference,
                'IdEnlace' => 'enlace-1',
                'NombreProducto' => 'Orden SIV-2026-00001',
            ],
            'Monto' => $amount,
            'EsAprobada' => true,
            'EsProductiva' => $production,
            'ResultadoTransaccion' => 'ExitosaAprobada',
            'FormaPagoUtilizada' => 'PagoNormal',
            'FechaTransaccion' => '2026-01-15 10:00:00',
            'CodigoAutorizacion' => '123456',
        ]);
    }

    /**
     * A payment the gateway did not approve.
     */
    public static function rejected(string $reference, float $amount = 25.50): WompiWebhook
    {
        return PayloadNormalizer::normalize([
            'IdTransaccion' => 'txn-2',
            'IdIntentoPago' => 'attempt-2',
            'EnlacePago' => [
                'IdentificadorEnlaceComercio' => $reference,
                'IdEnlace' => 'enlace-1',
            ],
            'Monto' => $amount,
            'EsAprobada' => false,
            'EsProductiva' => true,
            'ResultadoTransaccion' => 'Rechazada',
            'FormaPagoUtilizada' => 'PagoNormal',
            'FechaTransaccion' => '2026-01-15 10:00:00',
        ]);
    }

    /**
     * A payment without a merchant reference.
     */
    public static function withoutReference(): WompiWebhook
    {
        return PayloadNormalizer::normalize([
            'IdTransaccion' => 'txn-3',
            'IdIntentoPago' => 'attempt-3',
            'Monto' => 25.50,
            'EsAprobada' => true,
            'EsProductiva' => true,
        ]);
    }
}
