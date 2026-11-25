@props([
    'label' => '',
    'value' => '',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'siv-stat']) }}>
    <span class="siv-stat__label">{{ $label }}</span>
    <span class="siv-stat__value">{{ $value }}</span>

    @if ($hint)
        <span class="siv-stat__hint">{{ $hint }}</span>
    @endif
</div>
