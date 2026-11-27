<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title data-inertia="title">{{ __('sivarcart::admin.title') }}</title>

    @include('sivarcart-panel::theme')
    @include('sivarcart-panel::assets')

    @foreach ((array) config('sivarcart.admin.stylesheets', []) as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach

    <x-inertia::head />
</head>
<body class="h-full bg-background text-foreground antialiased">
    <x-inertia::app id="sivarcart-admin" />
</body>
</html>
