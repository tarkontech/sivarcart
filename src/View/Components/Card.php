<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A box of the administration panel.
 *
 * The view of the component lives in the namespace of the package, so the
 * merchant overrides it in "resources/views/vendor/sivarcart-core".
 */
final class Card extends Component
{
    /**
     * @param  string|null  $title  The title of the box.
     * @param  string|null  $description  The text under the title.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {
        //
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('sivarcart-core::admin.components.card');
    }
}
