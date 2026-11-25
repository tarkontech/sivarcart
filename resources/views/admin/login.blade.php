<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('sivarcart-core::admin.login.title') }}</title>

    <link rel="stylesheet" href="{{ route('sivarcart-core.admin.assets.css') }}?v={{ $adminAssetVersion }}">

    @livewireStyles
</head>
<body class="siv-body siv-body--centered">
    <main class="siv-auth">
        <h1 class="siv-auth__title">{{ __('sivarcart-core::admin.login.title') }}</h1>

        <form method="POST" action="{{ route('sivarcart-core.admin.login') }}" class="siv-form">
            @csrf

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.login.email') }}</span>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                    class="siv-input"
                >
                @error('email')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.login.password') }}</span>
                <input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="siv-input"
                >
            </label>

            <label class="siv-field siv-field--inline">
                <input type="checkbox" name="remember" value="1">
                <span>{{ __('sivarcart-core::admin.login.remember') }}</span>
            </label>

            <button type="submit" class="siv-button siv-button--primary">
                {{ __('sivarcart-core::admin.login.submit') }}
            </button>
        </form>
    </main>

    @livewireScripts
</body>
</html>
