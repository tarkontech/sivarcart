<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Stops a request the staff of the store is not allowed to make.
 *
 * The permission comes from the parameter of the middleware and, when there is
 * none, from the "permission" parameter of the route:
 *
 *     Route::get('/orders', ...)->middleware('store.permission:orders.view');
 *     Route::get('/reports/{permission}', ...)->middleware('store.permission');
 *
 * The request goes through when the user of the application holds the
 * permission in one of the roles of the store. A request without a permission,
 * without a user, or with a user whose model does not carry the trait of the
 * roles of the store is refused with a 403.
 *
 * The middleware does not authenticate anybody: the application puts its own
 * "auth" middleware in front of this one.
 */
final class EnsureStorePermission
{
    /**
     * Handle the request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        $permission = $permissions[0] ?? $request->route('permission');

        if (! is_string($permission) || $permission === '') {
            abort(403, 'La ruta no dice qué permiso necesita.');
        }

        if (! Auth::hasStorePermission(Auth::user(), $permission)) {
            abort(403, 'No tienes permiso para hacer esto.');
        }

        return $next($request);
    }
}
