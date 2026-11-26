<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Signs the staff of the store into the administration panel.
 *
 * The panel does not own the users: the credentials are checked against the
 * guard of the host application and the session is the one of that guard. The
 * package only renders the screen.
 */
final class LoginController
{
    /**
     * Show the sign in screen.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if (AuthFacade::guard(Auth::guard())->check()) {
            return redirect()->route('sivarcart-core.admin.index');
        }

        $email = $request->old('email', '');

        return Inertia::render('Admin/Login', [
            'email' => is_string($email) ? $email : '',
        ]);
    }

    /**
     * Sign the staff of the store in.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! AuthFacade::guard(Auth::guard())->attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __('sivarcart-core::admin.login.failed'),
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('sivarcart-core.admin.index'));
    }
}
