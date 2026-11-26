<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth as AuthFacade;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Explains what the application has to do for the panel to know its staff.
 *
 * The roles of the store live in a pivot that points at the users of the host
 * application, so the model of the user has to carry the trait of the roles.
 * The package cannot add the trait by itself, so this screen says what to do.
 */
final class SetupController
{
    /**
     * Show the setup screen.
     */
    public function show(): Response
    {
        $user = AuthFacade::guard(Auth::guard())->user();

        return Inertia::render('Admin/Setup', [
            'model' => Auth::model(),
            'signedIn' => $user !== null,
        ]);
    }
}
