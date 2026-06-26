@props(['variant' => 'mark'])

<img
    {{ $attributes->merge(['class' => 'simdex-logo '.$variant]) }}
    src="{{ asset($variant === 'full' ? 'images/simdex-logo.png' : 'images/simdex-mark.png') }}"
    alt="{{ $variant === 'full' ? 'SIMDEX - Student Index Management' : 'SIMDEX' }}"
>
