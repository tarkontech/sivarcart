<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Makes the store API answer with JSON, always.
 *
 * The middleware does two things:
 *
 * - it asks for JSON, so a client that forgets the "Accept" header gets a
 *   JSON answer instead of a page or a redirect to a login that the
 *   application may not even have;
 * - it counts the requests of the buyer with the limiter of the package.
 *
 * The errors of the domain are not answered here: the pipeline of the routes
 * hands them to the handler of the application before they reach a
 * middleware, so the handler is the one that answers them.
 */
final class StorefrontApi
{
    public function __construct(private readonly ThrottleRequests $throttle)
    {
        //
    }

    /**
     * Handle the request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $request->headers->set('Accept', 'application/json');

        return $this->throttle->handle($request, $next, 'sivarcart');
    }
}
