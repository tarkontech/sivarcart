<x-mail::message>
# {{ __('sivarcart::messages.account_notifications.welcome.greeting', ['name' => $customer->first_name]) }}

{{ __('sivarcart::messages.account_notifications.welcome.body', ['store' => $store]) }}

<x-mail::button :url="$url">
{{ __('sivarcart::messages.account_notifications.welcome.cta') }}
</x-mail::button>

{{ __('sivarcart::messages.account_notifications.welcome.footer', ['store' => $store]) }}
</x-mail::message>
