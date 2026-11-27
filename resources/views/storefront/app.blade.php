<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title data-inertia="title">{{ config('sivarcart.store.name', 'SivarCart') }}</title>

    @php
        $favicon = \Tarkontech\SivarCart\Storefront\StorefrontPresenter::fileUrl(
            app(\Tarkontech\SivarCart\Services\StoreService::class)->current()?->favicon_path,
        );
    @endphp

    @if ($favicon !== null)
        <link rel="icon" href="{{ $favicon }}">
    @endif

    @include('sivarcart-storefront::theme')
    @include('sivarcart-storefront::assets')

    @foreach ((array) config('sivarcart.storefront.stylesheets', []) as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach

    <x-inertia::head />
</head>
<body class="h-full bg-background text-foreground antialiased">
    <x-inertia::app id="sivarcart-storefront" />
</body>
</html>
