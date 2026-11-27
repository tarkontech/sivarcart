<x-mail::message>
# {{ __('sivarcart::messages.order_notifications.payment_failed.greeting', ['name' => $order->customer_name]) }}

{{ __('sivarcart::messages.order_notifications.payment_failed.body', ['number' => $order->order_number, 'reason' => $reason]) }}

<x-mail::button :url="url('/')">
{{ __('sivarcart::messages.order_notifications.payment_failed.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.order_notifications.payment_failed.footer') }}
</x-mail::message>
