<x-mail::message>
# {{ __('sivarcart-core::messages.account_notifications.verify_email.greeting', ['name' => $customer->first_name]) }}

{{ __('sivarcart-core::messages.account_notifications.verify_email.body', ['store' => $store]) }}

<x-mail::button :url="$url">
{{ __('sivarcart-core::messages.account_notifications.verify_email.cta') }}
</x-mail::button>

{{ __('sivarcart-core::messages.account_notifications.verify_email.footer') }}
</x-mail::message>
