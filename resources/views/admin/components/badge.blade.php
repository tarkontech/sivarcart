@props([
    'label' => '',
    'tone' => 'neutral',
])

<span {{ $attributes->merge(['class' => 'siv-badge siv-badge--'.$tone]) }}>{{ $label }}</span>
