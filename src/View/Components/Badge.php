<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A label of the administration panel.
 *
 * The tone says how the label looks: it is one of "neutral", "success",
 * "warning" or "danger".
 */
final class Badge extends Component
{
    /**
     * @param  string  $label  The text of the label.
     * @param  string  $tone  The tone of the label.
     */
    public function __construct(
        public string $label,
        public string $tone = 'neutral',
    ) {
        //
    }

    /**
     * The tone a state of the store is shown with.
     *
     * The panel shows the state of an order, of a payment or of a product, and
     * the tone tells at a glance how the staff has to read it.
     */
    public static function toneOf(string $value): string
    {
        return match ($value) {
            'paid', 'delivered', 'published', 'active', 'completed' => 'success',
            'pending', 'processing', 'unpaid', 'reserved' => 'warning',
            'failed', 'cancelled', 'refunded', 'returned', 'draft', 'inactive' => 'danger',
            default => 'neutral',
        };
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('sivarcart-core::admin.components.badge');
    }
}
