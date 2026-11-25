<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use SivarcartCore\SivarcartCore\Admin\AdminPanel;
use SivarcartCore\SivarcartCore\Services\Admin\StatsService;

/**
 * The home of the administration panel.
 *
 * It shows the counters the staff of the store looks at first and the
 * widgets the panel registry knows, so a merchant can add its own.
 */
class Dashboard extends Component
{
    /**
     * Render the dashboard.
     */
    public function render(StatsService $stats, AdminPanel $panel): View
    {
        $panel->registerLivewireComponents();

        return view('sivarcart-core::admin.pages.dashboard', [
            'ordersToday' => $stats->ordersToday(),
            'ordersAwaiting' => $stats->ordersAwaiting(),
            'ordersUnpaid' => $stats->ordersUnpaid(),
            'products' => $stats->products(),
            'lowStock' => $stats->lowStock(),
            'widgets' => $panel->visibleWidgets(),
        ]);
    }
}
