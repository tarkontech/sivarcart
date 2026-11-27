<x-mail::message>
# {{ __('sivarcart::messages.account_notifications.password_reset.greeting', ['name' => $customer->first_name]) }}

{{ __('sivarcart::messages.account_notifications.password_reset.body', ['store' => $store]) }}

<x-mail::button :url="$url">
{{ __('sivarcart::messages.account_notifications.password_reset.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.account_notifications.password_reset.footer') }}
</x-mail::message>
