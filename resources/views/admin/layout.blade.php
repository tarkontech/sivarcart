<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('sivarcart-core::admin.title')) · {{ $adminStore?->name ?? 'SivarCart' }}</title>

    <link rel="stylesheet" href="{{ route('sivarcart-core.admin.assets.css') }}?v={{ $adminAssetVersion }}">

    @if ($adminTheme !== [])
        <style>
            :root {
                @foreach ($adminTheme as $property => $value)
                    --sivarcart-{{ $property }}: {{ $value }};
                @endforeach
            }
        </style>
    @endif

    @foreach ((array) config('sivarcart-core.admin.stylesheets', []) as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach

    @livewireStyles
</head>
<body class="siv-body">
    <div class="siv-shell">
        <aside class="siv-sidebar">
            <a class="siv-brand" href="{{ route('sivarcart-core.admin.index') }}">
                {{ $adminStore?->name ?? 'SivarCart' }}
            </a>

            <nav class="siv-nav">
                @foreach ($adminPages as $adminPage)
                    <a
                        class="siv-nav__link{{ $adminPage->key === $adminCurrentPage ? ' is-active' : '' }}"
                        href="{{ $adminPage->url() }}"
                    >
                        @if ($adminPage->icon)
                            <span class="siv-nav__icon" aria-hidden="true">{{ $adminPage->icon }}</span>
                        @endif

                        <span>{{ __($adminPage->label) }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <main class="siv-main">
            @yield('content')
        </main>
    </div>

    @livewireScripts
</body>
</html>
