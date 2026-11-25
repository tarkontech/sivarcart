<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.customers.title') }}</h1>
    </header>

    <div class="siv-filters">
        <input
            type="search"
            class="siv-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('sivarcart-core::admin.customers.search') }}"
        >
    </div>

    <x-sivarcart-core::card>
        @if ($customers->isEmpty())
            <p class="siv-empty">{{ __('sivarcart-core::admin.customers.empty') }}</p>
        @else
            <table class="siv-table">
                <thead>
                    <tr>
                        <th>{{ __('sivarcart-core::admin.customers.name') }}</th>
                        <th>{{ __('sivarcart-core::admin.customers.email') }}</th>
                        <th>{{ __('sivarcart-core::admin.customers.phone') }}</th>
                        <th>{{ __('sivarcart-core::admin.customers.orders') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->uuid }}">
                            <td>{{ trim($customer->first_name.' '.$customer->last_name) ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->orders_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{ $customers->links() }}
    </x-sivarcart-core::card>
</div>
