<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.orders.title') }}</h1>
    </header>

    <div class="siv-filters">
        <input
            type="search"
            class="siv-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('sivarcart-core::admin.orders.search') }}"
        >

        <select class="siv-input" wire:model.live="status">
            <option value="">{{ __('sivarcart-core::admin.orders.all_statuses') }}</option>

            @foreach ($statuses as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </select>
    </div>

    <x-sivarcart-core::card>
        @if ($orders->isEmpty())
            <p class="siv-empty">{{ __('sivarcart-core::admin.orders.empty') }}</p>
        @else
            <table class="siv-table">
                <thead>
                    <tr>
                        <th>{{ __('sivarcart-core::admin.orders.number') }}</th>
                        <th>{{ __('sivarcart-core::admin.orders.customer') }}</th>
                        <th>{{ __('sivarcart-core::admin.orders.date') }}</th>
                        <th>{{ __('sivarcart-core::admin.orders.total') }}</th>
                        <th>{{ __('sivarcart-core::admin.orders.status') }}</th>
                        <th>{{ __('sivarcart-core::admin.orders.payment') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($orders as $order)
                        <tr wire:key="order-{{ $order->uuid }}">
                            <td>
                                <a href="{{ route('sivarcart-core.admin.orders.show', ['order' => $order->uuid]) }}">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->placed_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                            <td>
                                <x-sivarcart-core::badge
                                    :label="$order->status->label()"
                                    :tone="\SivarcartCore\SivarcartCore\View\Components\Badge::toneOf($order->status->value)"
                                />
                            </td>
                            <td>
                                <x-sivarcart-core::badge
                                    :label="$order->payment_status->label()"
                                    :tone="\SivarcartCore\SivarcartCore\View\Components\Badge::toneOf($order->payment_status->value)"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{ $orders->links() }}
    </x-sivarcart-core::card>
</div>
