<x-mail::message>
# {{ __('sivarcart::messages.order_notifications.order_cancelled.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart::messages.order_notifications.order_cancelled.body', ['number' => $order->order_number]) }}

<x-mail::table>
| {{ __('sivarcart::messages.order_notifications.table.product') }} | {{ __('sivarcart::messages.order_notifications.table.quantity') }} | {{ __('sivarcart::messages.order_notifications.table.price') }} |
|---|---|---|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ number_format((float) $item->line_total, 2) }} {{ $order->currency }} |
@endforeach
</x-mail::table>

<x-mail::button :url="url('/')">
{{ __('sivarcart::messages.order_notifications.order_cancelled.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.order_notifications.order_cancelled.footer') }}
</x-mail::message>
