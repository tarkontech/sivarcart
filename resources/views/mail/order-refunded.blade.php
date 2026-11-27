<x-mail::message>
# {{ __('sivarcart::messages.order_notifications.order_refunded.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart::messages.order_notifications.order_refunded.body', ['number' => $order->order_number, 'amount' => $amount]) }}

<x-mail::button :url="url('/')">
{{ __('sivarcart::messages.order_notifications.order_refunded.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.order_notifications.order_refunded.footer') }}
</x-mail::message>
