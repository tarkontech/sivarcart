<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Lets the staff of the store into the administration panel.
 *
 * The panel does not authenticate anybody: it uses the users, the guard and
 * the session of the host application. This middleware sends the staff that
 * are not signed in to the sign in screen of the panel, and sends the ones
 * whose model does not carry the trait of the roles of the store to the setup
 * screen, which explains what the application has to do.
 */
final class EnsureStoreAdmin
{
    /**
     * Handle the request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = AuthFacade::guard(Auth::guard())->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                abort(401, 'No has iniciado sesión.');
            }

            return redirect()->guest($this->loginUrl());
        }

        if (! method_exists($user, 'hasStorePermission')) {
            if ($request->expectsJson()) {
                abort(403, 'El modelo del usuario no tiene los roles de la tienda.');
            }

            return redirect()->route('sivarcart-core.admin.setup');
        }

        return $next($request);
    }

    /**
     * Where the staff that are not signed in are sent.
     */
    private function loginUrl(): string
    {
        $route = config('sivarcart-core.admin.login.route');

        if (is_string($route) && $route !== '') {
            return route($route);
        }

        if (! (bool) config('sivarcart-core.admin.login.enabled', true)) {
            return url('/');
        }

        return route('sivarcart-core.admin.login');
    }
}
