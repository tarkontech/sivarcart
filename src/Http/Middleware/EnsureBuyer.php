<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tarkontech\SivarCart\Exceptions\CustomerRequired;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Services\CustomerService;

/**
 * Stops a request of a buyer that is not signed in.
 *
 * The middleware does not authenticate anybody: the buyer signs in with the
 * guard of the package ("sivarcart"), and the request that arrives without
 * that session is refused with the same answer the domain uses when a buyer
 * is needed.
 */
final class EnsureBuyer
{
    public function __construct(private readonly CustomerService $customers)
    {
        //
    }

    /**
     * Handle the request.
     *
     * @param  Closure(Request): mixed  $next
     *
     * @throws CustomerRequired
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->customers->user() instanceof Customer) {
            throw CustomerRequired::notSignedIn();
        }

        return $next($request);
    }
}
