<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use SivarcartCore\SivarcartCore\Exceptions\CustomerRequired;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\CustomerService;

/**
 * The base of the controllers of the store API.
 *
 * The buyer is not the staff of the store: it signs in with the guard of the
 * package, and the controllers resolve it with the account service.
 */
abstract class ApiController extends Controller
{
    /**
     * The buyer that is signed in.
     *
     * @throws CustomerRequired
     */
    protected function buyer(): Customer
    {
        $buyer = $this->customers()->user();

        if (! $buyer instanceof Customer) {
            throw CustomerRequired::notSignedIn();
        }

        return $buyer;
    }

    /**
     * The account of the buyers.
     */
    private function customers(): CustomerService
    {
        return app(CustomerService::class);
    }
}
