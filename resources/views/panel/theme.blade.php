@php
    /**
     * The theme of the panel.
     *
     * The variables are written after the stylesheet of the package, so they
     * win over the ones it brings. The selector is ":root, .dark" because both
     * have the same specificity: the last one in the order of the source is
     * the one that wins, and the brand of the store is the same in the light
     * theme and in the dark one.
     */
    $variables = \Tarkontech\SivarCart\Admin\AdminTheme::variables();
    $dark = \Tarkontech\SivarCart\Admin\AdminTheme::darkVariables();
@endphp
@if ($variables !== [])
    <style>
        :root, .dark {
            @foreach ($variables as $name => $value)
                {!! $name !!}: {!! $value !!};
            @endforeach
        }
    </style>
@endif
@if ($dark !== [])
    <style>
        .dark {
            @foreach ($dark as $name => $value)
                {!! $name !!}: {!! $value !!};
            @endforeach
        }
    </style>
@endif
