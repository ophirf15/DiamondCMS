@props([
    'variant' => 'currentColor', // currentColor|gold|white|black|gold-on-charcoal
    'class' => 'h-8 w-8',
])
@php
    $file = match ($variant) {
        'gold' => 'logo-primary-gold.svg',
        'white' => 'logo-white.svg',
        'black' => 'logo-black.svg',
        'gold-on-charcoal' => 'logo-gold-on-charcoal.svg',
        default => 'logo-currentColor.svg',
    };
@endphp
<img
    src="{{ asset('brand/'.$file) }}"
    alt="DiamondCMS"
    {{ $attributes->merge(['class' => $class]) }}
>
