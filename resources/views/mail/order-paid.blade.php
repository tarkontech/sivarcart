<x-mail::message>
# {{ __('sivarcart::messages.order_notifications.order_paid.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart::messages.order_notifications.order_paid.body', ['number' => $order->order_number, 'amount' => $amount]) }}

<x-mail::panel>
- **{{ __('sivarcart::messages.order_notifications.totals.total') }}: {{ number_format((float) $order->total, 2) }} {{ $order->currency }}**
</x-mail::panel>

<x-mail::button :url="url('/')">
{{ __('sivarcart::messages.order_notifications.order_paid.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.order_notifications.order_paid.footer') }}
</x-mail::message>
