<x-mail::message>
# {{ __('sivarcart-core::messages.order_notifications.order_placed.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart-core::messages.order_notifications.order_placed.body', ['number' => $order->order_number]) }}

<x-mail::table>
| {{ __('sivarcart-core::messages.order_notifications.table.product') }} | {{ __('sivarcart-core::messages.order_notifications.table.quantity') }} | {{ __('sivarcart-core::messages.order_notifications.table.price') }} |
|---|---|---|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ number_format((float) $item->line_total, 2) }} {{ $order->currency }} |
@endforeach
</x-mail::table>

<x-mail::panel>
- {{ __('sivarcart-core::messages.order_notifications.totals.subtotal') }}: {{ number_format((float) $order->subtotal, 2) }} {{ $order->currency }}
@if ((float) $order->discount_total > 0)
- {{ __('sivarcart-core::messages.order_notifications.totals.discount') }}: {{ number_format((float) $order->discount_total, 2) }} {{ $order->currency }}
@endif
- {{ __('sivarcart-core::messages.order_notifications.totals.shipping') }}: {{ number_format((float) $order->shipping_total, 2) }} {{ $order->currency }}
@unless ($order->tax_inclusive)
- {{ __('sivarcart-core::messages.order_notifications.totals.tax') }}: {{ number_format((float) $order->tax_total, 2) }} {{ $order->currency }}
@endunless
- **{{ __('sivarcart-core::messages.order_notifications.totals.total') }}: {{ number_format((float) $order->total, 2) }} {{ $order->currency }}**
</x-mail::panel>

<x-mail::button :url="url('/')">
{{ __('sivarcart-core::messages.order_notifications.order_placed.cta') }}
</x-mail::button>

{{ __('sivarcart-core::messages.order_notifications.order_placed.footer') }}
</x-mail::message>
