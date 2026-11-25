<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.products.title') }}</h1>
    </header>

    <div class="siv-filters">
        <input
            type="search"
            class="siv-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('sivarcart-core::admin.products.search') }}"
        >
    </div>

    <x-sivarcart-core::card>
        @if ($products->isEmpty())
            <p class="siv-empty">{{ __('sivarcart-core::admin.products.empty') }}</p>
        @else
            <table class="siv-table">
                <thead>
                    <tr>
                        <th>{{ __('sivarcart-core::admin.products.name') }}</th>
                        <th>{{ __('sivarcart-core::admin.products.sku') }}</th>
                        <th>{{ __('sivarcart-core::admin.products.price') }}</th>
                        <th>{{ __('sivarcart-core::admin.products.stock') }}</th>
                        <th>{{ __('sivarcart-core::admin.products.status') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($products as $product)
                        @php($range = $product->priceRange())
                        <tr wire:key="product-{{ $product->uuid }}">
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku() ?? '—' }}</td>
                            <td>
                                @if ($range === null)
                                    —
                                @elseif ($range['min'] === $range['max'])
                                    {{ number_format($range['min'], 2) }}
                                @else
                                    {{ number_format($range['min'], 2) }} – {{ number_format($range['max'], 2) }}
                                @endif
                            </td>
                            <td>{{ $product->defaultVariant()?->stock_quantity ?? 0 }}</td>
                            <td>
                                <x-sivarcart-core::badge
                                    :label="$product->status->label()"
                                    :tone="\SivarcartCore\SivarcartCore\View\Components\Badge::toneOf($product->status->value)"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{ $products->links() }}
    </x-sivarcart-core::card>
</div>
