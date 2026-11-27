<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Exceptions\CustomerAlreadyExists;
use Tarkontech\SivarCart\Exceptions\CustomerRequired;
use Tarkontech\SivarCart\Exceptions\InvalidCartState;
use Tarkontech\SivarCart\Exceptions\PaymentGatewayFailed;
use Tarkontech\SivarCart\Exceptions\SivarCartException;

/**
 * Turns the errors of the domain into the answers of the store API.
 *
 * The renderer only answers the routes of the package: the routes of the
 * application keep the answers they had. Every error carries a code that does
 * not change with the message, so a client can tell apart a coupon that does
 * not exist from a coupon that cannot be used.
 */
final class ApiExceptionRenderer
{
    /**
     * Render the error.
     *
     * The renderer is registered in the handler of the application, so the
     * errors of the domain are answered by the same handler that answers the
     * errors of the framework.
     */
    public function __invoke(SivarCartException $exception, Request $request): ?JsonResponse
    {
        if (! $request->routeIs('sivarcart.api.*')) {
            return null;
        }

        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $this->code($exception),
        ], $this->status($exception));
    }

    /**
     * The status the error answers with.
     */
    private function status(SivarCartException $exception): int
    {
        return match (true) {
            $exception instanceof CustomerRequired => 401,
            $exception instanceof CustomerAlreadyExists => 409,
            $exception instanceof InvalidCartState => 409,
            $exception instanceof PaymentGatewayFailed => 502,
            default => 422,
        };
    }

    /**
     * The code that tells the error apart, whatever the message says.
     */
    private function code(SivarCartException $exception): string
    {
        return Str::snake(class_basename($exception));
    }
}
