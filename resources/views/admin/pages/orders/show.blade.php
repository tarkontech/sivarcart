<div class="siv-stack">
    @if ($order === null)
        <div class="siv-alert siv-alert--danger">
            {{ __('sivarcart-core::admin.orders.not_found') }}
        </div>
    @else
        <header class="siv-page-header">
            <a class="siv-back" href="{{ route('sivarcart-core.admin.page', ['page' => 'orders']) }}">
                {{ __('sivarcart-core::admin.orders.back') }}
            </a>

            <h1 class="siv-page-header__title">{{ $order->order_number }}</h1>

            <div class="siv-badges">
                <x-sivarcart-core::badge
                    :label="$order->status->label()"
                    :tone="\SivarcartCore\SivarcartCore\View\Components\Badge::toneOf($order->status->value)"
                />
                <x-sivarcart-core::badge
                    :label="$order->payment_status->label()"
                    :tone="\SivarcartCore\SivarcartCore\View\Components\Badge::toneOf($order->payment_status->value)"
                />
            </div>
        </header>

        @if ($notice !== null)
            <div class="siv-alert siv-alert--success">{{ $notice }}</div>
        @endif

        @if ($error !== null)
            <div class="siv-alert siv-alert--danger">{{ $error }}</div>
        @endif

        <div class="siv-columns">
            <x-sivarcart-core::card :title="__('sivarcart-core::admin.orders.items')">
                <table class="siv-table">
                    <thead>
                        <tr>
                            <th>{{ __('sivarcart-core::admin.orders.item') }}</th>
                            <th>{{ __('sivarcart-core::admin.orders.quantity') }}</th>
                            <th>{{ __('sivarcart-core::admin.orders.price') }}</th>
                            <th>{{ __('sivarcart-core::admin.orders.line_total') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($order->items as $item)
                            <tr wire:key="item-{{ $item->id }}">
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <dl class="siv-totals">
                    <div>
                        <dt>{{ __('sivarcart-core::admin.orders.subtotal') }}</dt>
                        <dd>{{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sivarcart-core::admin.orders.discount') }}</dt>
                        <dd>{{ number_format((float) $order->discount_total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sivarcart-core::admin.orders.tax') }}</dt>
                        <dd>{{ number_format((float) $order->tax_total, 2) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('sivarcart-core::admin.orders.shipping') }}</dt>
                        <dd>{{ number_format((float) $order->shipping_total, 2) }}</dd>
                    </div>
                    <div class="siv-totals__total">
                        <dt>{{ __('sivarcart-core::admin.orders.total') }}</dt>
                        <dd>{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</dd>
                    </div>
                </dl>
            </x-sivarcart-core::card>

            <div class="siv-stack">
                <x-sivarcart-core::card :title="__('sivarcart-core::admin.orders.buyer')">
                    <dl class="siv-details">
                        <div>
                            <dt>{{ __('sivarcart-core::admin.orders.customer') }}</dt>
                            <dd>{{ $order->customer_name }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('sivarcart-core::admin.orders.email') }}</dt>
                            <dd>{{ $order->customer_email }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('sivarcart-core::admin.orders.phone') }}</dt>
                            <dd>{{ $order->customer_phone }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('sivarcart-core::admin.orders.date') }}</dt>
                            <dd>{{ $order->placed_at?->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </x-sivarcart-core::card>

                @if ($canUpdate && $transitions !== [])
                    <x-sivarcart-core::card :title="__('sivarcart-core::admin.orders.actions')">
                        <div class="siv-actions">
                            @foreach ($transitions as $transition)
                                <button
                                    type="button"
                                    class="siv-button{{ $transition->value === 'cancelled' ? ' siv-button--danger' : ' siv-button--primary' }}"
                                    wire:click="move('{{ $transition->value }}')"
                                    wire:loading.attr="disabled"
                                >
                                    {{ $transition->label() }}
                                </button>
                            @endforeach
                        </div>
                    </x-sivarcart-core::card>
                @endif

                @if ($order->statusHistories->isNotEmpty())
                    <x-sivarcart-core::card :title="__('sivarcart-core::admin.orders.timeline')">
                        <ul class="siv-timeline">
                            @foreach ($order->statusHistories->sortByDesc('created_at') as $history)
                                <li wire:key="history-{{ $history->id }}">
                                    <span class="siv-timeline__date">
                                        {{ $history->created_at?->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="siv-timeline__text">
                                        {{ $history->axis->value === 'payment'
                                            ? $history->payment_status?->label()
                                            : $history->status?->label() }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </x-sivarcart-core::card>
                @endif
            </div>
        </div>
    @endif
</div>
