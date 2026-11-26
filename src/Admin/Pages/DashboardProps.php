<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPanel;
use SivarcartCore\SivarcartCore\Admin\AdminWidget;
use SivarcartCore\SivarcartCore\Services\Admin\StatsService;

/**
 * The data of the dashboard of the panel.
 *
 * The dashboard shows the counters the staff of the store looks at first, and
 * the widgets the registry of the panel knows.
 */
final readonly class DashboardProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $stats = app(StatsService::class);

        return [
            'ordersToday' => $stats->ordersToday(),
            'ordersAwaiting' => $stats->ordersAwaiting(),
            'ordersUnpaid' => $stats->ordersUnpaid(),
            'products' => $stats->products(),
            'lowStock' => $stats->lowStock(),
            'widgets' => $this->widgets($request),
        ];
    }

    /**
     * The widgets the dashboard renders in its grid.
     *
     * @return list<array{key: string, component: string, props: array<string, mixed>}>
     */
    private function widgets(Request $request): array
    {
        $widgets = [];

        foreach (app(AdminPanel::class)->visibleWidgets() as $widget) {
            $widgets[] = [
                'key' => $widget->key,
                'component' => $widget->component,
                'props' => $this->propsOf($widget, $request),
            ];
        }

        return $widgets;
    }

    /**
     * The data of a widget.
     *
     * @return array<string, mixed>
     */
    private function propsOf(AdminWidget $widget, Request $request): array
    {
        if ($widget->props === null) {
            return [];
        }

        return $widget->props->props($request);
    }
}
