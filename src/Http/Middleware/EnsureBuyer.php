<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Exceptions\CustomerRequired;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\CustomerService;

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
