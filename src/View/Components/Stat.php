<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A number of the administration panel.
 *
 * The value is a string on purpose: the panel formats it the way the store
 * wants it, and the merchant can change that in the view.
 */
final class Stat extends Component
{
    /**
     * @param  string  $label  The name of the number.
     * @param  string|int|float  $value  The number itself.
     * @param  string|null  $hint  The text under the number.
     */
    public function __construct(
        public string $label,
        public string|int|float $value,
        public ?string $hint = null,
    ) {
        //
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('sivarcart-core::admin.components.stat');
    }
}
