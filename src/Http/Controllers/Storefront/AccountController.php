<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Storefront;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Exceptions\CustomerAlreadyExists;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Http\Requests\ChangePasswordRequest;
use SivarcartCore\SivarcartCore\Http\Requests\LoginRequest;
use SivarcartCore\SivarcartCore\Http\Requests\RegisterRequest;
use SivarcartCore\SivarcartCore\Http\Requests\SendResetLinkRequest;
use SivarcartCore\SivarcartCore\Http\Requests\UpdateProfileRequest;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Storefront\StorefrontPresenter;

/**
 * The account of the buyer.
 *
 * The buyer signs in with the guard of the package, so the session of the
 * buyer is not the session of the staff of the store.
 */
final class AccountController
{
    public function __construct(private readonly CustomerService $customers)
    {
        //
    }

    /**
     * The screen of entry.
     */
    public function login(Request $request): Response|RedirectResponse
    {
        if ($this->customers->user() instanceof Customer) {
            return redirect()->route('sivarcart-core.storefront.account.show');
        }

        return Inertia::render('Storefront/Account/Login', [
            'email' => $this->old($request, 'email'),
            'redirect' => $this->redirect($request),
        ]);
    }

    /**
     * Sign a buyer in.
     */
    public function loginStore(LoginRequest $request): RedirectResponse
    {
        $buyer = $this->customers->attempt(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
        );

        if (! $buyer instanceof Customer) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => (string) __('sivarcart-core::messages.api.invalid_credentials'),
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirect($request) ?? route('sivarcart-core.storefront.account.show'));
    }

    /**
     * The screen of the signup.
     */
    public function register(): Response|RedirectResponse
    {
        if ($this->customers->user() instanceof Customer) {
            return redirect()->route('sivarcart-core.storefront.account.show');
        }

        return Inertia::render('Storefront/Account/Register', [
            'document_types' => $this->documentTypes(),
        ]);
    }

    /**
     * Create the account of a buyer and sign it in.
     */
    public function registerStore(RegisterRequest $request): RedirectResponse
    {
        try {
            $buyer = $this->customers->register($request->validated());
        } catch (CustomerAlreadyExists $exception) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => $exception->getMessage()]);
        } catch (SivarcartCoreException $exception) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        $this->customers->attempt(
            (string) $buyer->email,
            (string) $request->validated('password'),
        );

        $request->session()->regenerate();

        return redirect()->intended(route('sivarcart-core.storefront.account.show'));
    }

    /**
     * Sign a buyer out.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->customers->logout();

        $request->session()->regenerateToken();

        return redirect()->route('sivarcart-core.storefront.home');
    }

    /**
     * The screen that asks for the link that resets the password.
     */
    public function passwordRequest(): Response
    {
        return Inertia::render('Storefront/Account/ForgotPassword');
    }

    /**
     * Send the buyer the link that resets the password.
     *
     * The answer is the same when the account does not exist, so the store
     * does not say who has an account.
     */
    public function passwordEmail(SendResetLinkRequest $request): RedirectResponse
    {
        $this->customers->sendPasswordResetLink((string) $request->validated('email'));

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * The screen where the password is changed.
     */
    public function passwordReset(Request $request, string $token): Response
    {
        return Inertia::render('Storefront/Account/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Save the new password of the buyer.
     */
    public function passwordUpdate(Request $request, string $token): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->customers->resetPassword(
                (string) $credentials['email'],
                $token,
                (string) $credentials['password'],
            );
        } catch (SivarcartCoreException $exception) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        return redirect()
            ->route('sivarcart-core.storefront.account.login')
            ->with('flash', ['type' => 'success']);
    }

    /**
     * The page of the account of the buyer.
     */
    public function show(): Response
    {
        $buyer = $this->customers->user();

        $addresses = [];

        foreach ($buyer->addresses()->with('department')->get() as $address) {
            $addresses[] = $this->customers->addressToArray($address);
        }

        return Inertia::render('Storefront/Account/Index', [
            'buyer' => StorefrontPresenter::customer($buyer),
            'addresses' => $addresses,
        ]);
    }

    /**
     * Update the profile of the buyer.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $buyer = $this->customers->user();

        try {
            $this->customers->updateProfile($buyer, $request->validated());
        } catch (SivarcartCoreException $exception) {
            return back()->withErrors(['profile' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Change the password of the buyer.
     */
    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $buyer = $this->customers->user();

        if (! $this->customers->verifyPassword($buyer, (string) $request->validated('current_password'))) {
            return back()->withErrors([
                'current_password' => (string) __('sivarcart-core::messages.api.wrong_password'),
            ]);
        }

        $this->customers->changePassword($buyer, (string) $request->validated('password'));

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * A value the form left behind, when it left a string.
     */
    private function old(Request $request, string $key): string
    {
        $value = $request->old($key, '');

        return is_string($value) ? $value : '';
    }

    /**
     * Where the buyer goes after signing in.
     *
     * The link that brought the buyer to the screen of entry se guarda, así
     * que vuelve al carrito o al checkout del que venía.
     *
     * The link travels in la dirección, así que solo se acepta cuando es un
     * camino de la aplicación: una dirección que empieza por "//" o que trae
     * un esquema es la dirección de otro sitio, y mandarle al comprador
     * ahí no es una vuelta al carrito.
     */
    private function redirect(Request $request): ?string
    {
        $redirect = $request->input('redirect');

        if (! is_string($redirect) || $redirect === '') {
            return null;
        }

        if (! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return null;
        }

        return $redirect;
    }

    /**
     * The types of document the buyer can pick from.
     *
     * @return list<array{value: string, label: string}>
     */
    private function documentTypes(): array
    {
        $types = [];

        foreach (CustomerDocumentType::cases() as $type) {
            $types[] = [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        }

        return $types;
    }
}
