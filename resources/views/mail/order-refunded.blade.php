<x-mail::message>
# {{ __('sivarcart-core::messages.order_notifications.order_refunded.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart-core::messages.order_notifications.order_refunded.body', ['number' => $order->order_number, 'amount' => $amount]) }}

<x-mail::button :url="url('/')">
{{ __('sivarcart-core::messages.order_notifications.order_refunded.cta') }}
</x-mail::button>

{{ __('sivarcart-core::messages.order_notifications.order_refunded.footer') }}
</x-mail::message>
