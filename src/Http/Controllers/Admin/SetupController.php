<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\View\View;
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
    public function show(): View
    {
        $user = AuthFacade::guard(Auth::guard())->user();

        return view('sivarcart-core::admin.setup', [
            'model' => Auth::model(),
            'signedIn' => $user !== null,
        ]);
    }
}
