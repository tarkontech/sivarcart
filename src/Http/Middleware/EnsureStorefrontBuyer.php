<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\CustomerService;

/**
 * Stops a request of the store that needs a buyer signed in.
 *
 * The middleware does not authenticate anybody: the buyer signs in with the
 * guard of the package ("sivarcart"), and the request that arrives without
 * that session is sent to the screen of entry of the store instead of being
 * refused with a code that a browser does not know what to do with.
 *
 * The screen of entry remembers de dónde vino el comprador, así que vuelve
 * al carrito o al checkout después de entrar.
 */
final class EnsureStorefrontBuyer
{
    public function __construct(private readonly CustomerService $customers)
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
        if (! $this->customers->user() instanceof Customer) {
            return redirect()->guest($this->loginUrl($request));
        }

        return $next($request);
    }

    /**
     * Where the shopper that is not signed in is sent.
     */
    private function loginUrl(Request $request): string
    {
        $url = route('sivarcart-core.storefront.account.login');

        $target = $request->fullUrl();

        if ($target === $url || $target === route('sivarcart-core.storefront.account.login.store')) {
            return $url;
        }

        return $url.'?redirect='.rawurlencode($target);
    }
}
