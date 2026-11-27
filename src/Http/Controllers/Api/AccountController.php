<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Tarkontech\SivarCart\Exceptions\SivarCartException;
use Tarkontech\SivarCart\Http\Requests\ChangePasswordRequest;
use Tarkontech\SivarCart\Http\Requests\LoginRequest;
use Tarkontech\SivarCart\Http\Requests\RegisterRequest;
use Tarkontech\SivarCart\Http\Requests\SendResetLinkRequest;
use Tarkontech\SivarCart\Http\Requests\UpdateProfileRequest;
use Tarkontech\SivarCart\Http\Resources\CustomerAddressResource;
use Tarkontech\SivarCart\Http\Resources\CustomerResource;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Services\CustomerService;

/**
 * The account of a buyer.
 *
 * The buyer signs in with the guard of the package, so the session of the
 * buyer is not the session of the staff of the store.
 */
final class AccountController extends ApiController
{
    public function __construct(private readonly CustomerService $customers)
    {
        //
    }

    /**
     * Create the account of a buyer and sign it in.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $attributes = $request->validated();

        $customer = $this->customers->register($attributes);

        $this->customers->attempt(
            (string) $attributes['email'],
            (string) $attributes['password'],
        );

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    /**
     * Sign a buyer in.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $buyer = $this->customers->attempt(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        if (! $buyer instanceof Customer) {
            throw new SivarCartException((string) __('sivarcart::messages.api.invalid_credentials'));
        }

        return CustomerResource::make($buyer)->response();
    }

    /**
     * Sign a buyer out.
     */
    public function logout(): Response
    {
        $this->customers->logout();

        return response()->noContent();
    }

    /**
     * Send the link that resets the password of a buyer.
     *
     * The answer is the same when the account does not exist, so the API
     * does not say who has an account.
     */
    public function sendPasswordResetLink(SendResetLinkRequest $request): JsonResponse
    {
        $this->customers->sendPasswordResetLink((string) $request->validated('email'));

        return response()->json([
            'message' => (string) __('sivarcart::messages.api.password_link_sent'),
        ], 202);
    }

    /**
     * The account of the buyer that is signed in.
     */
    public function show(): JsonResponse
    {
        return CustomerResource::make($this->buyer())->response();
    }

    /**
     * Update the profile of the buyer that is signed in.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $buyer = $this->buyer();

        return CustomerResource::make($this->customers->updateProfile($buyer, $request->validated()))->response();
    }

    /**
     * Change the password of the buyer that is signed in.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $buyer = $this->buyer();

        if (! $this->customers->verifyPassword($buyer, (string) $request->validated('current_password'))) {
            throw new SivarCartException((string) __('sivarcart::messages.api.wrong_password'));
        }

        $this->customers->changePassword($buyer, (string) $request->validated('password'));

        return response()->json([
            'message' => (string) __('sivarcart::messages.api.password_changed'),
        ]);
    }

    /**
     * The addresses the buyer saved.
     */
    public function addresses(): AnonymousResourceCollection
    {
        $buyer = $this->buyer();

        return CustomerAddressResource::collection(
            $buyer->addresses()->with('department')->get(),
        );
    }
}
