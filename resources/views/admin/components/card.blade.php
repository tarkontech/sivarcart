@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'siv-card']) }}>
    @if ($title || $description)
        <header class="siv-card__header">
            @if ($title)
                <h2 class="siv-card__title">{{ $title }}</h2>
            @endif

            @if ($description)
                <p class="siv-card__description">{{ $description }}</p>
            @endif
        </header>
    @endif

    <div class="siv-card__body">
        {{ $slot }}
    </div>
</section>
