<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.dashboard.title') }}</h1>
        <p class="siv-page-header__description">{{ __('sivarcart-core::admin.dashboard.description') }}</p>
    </header>

    <div class="siv-stats">
        <x-sivarcart-core::stat
            :label="__('sivarcart-core::admin.dashboard.orders_today')"
            :value="$ordersToday"
        />
        <x-sivarcart-core::stat
            :label="__('sivarcart-core::admin.dashboard.orders_awaiting')"
            :value="$ordersAwaiting"
        />
        <x-sivarcart-core::stat
            :label="__('sivarcart-core::admin.dashboard.orders_unpaid')"
            :value="$ordersUnpaid"
        />
        <x-sivarcart-core::stat
            :label="__('sivarcart-core::admin.dashboard.products')"
            :value="$products"
        />
        <x-sivarcart-core::stat
            :label="__('sivarcart-core::admin.dashboard.low_stock')"
            :value="$lowStock"
        />
    </div>

    @foreach ($widgets as $widget)
        <div class="siv-widget">
            @livewire($widget->component)
        </div>
    @endforeach
</div>
