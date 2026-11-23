<x-mail::message>
# {{ __('sivarcart-core::messages.order_notifications.payment_failed.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart-core::messages.order_notifications.payment_failed.body', ['number' => $order->order_number, 'reason' => $reason]) }}

<x-mail::button :url="url('/')">
{{ __('sivarcart-core::messages.order_notifications.payment_failed.cta') }}
</x-mail::button>

{{ __('sivarcart-core::messages.order_notifications.payment_failed.footer') }}
</x-mail::message>
